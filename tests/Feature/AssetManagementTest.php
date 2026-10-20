<?php

namespace Tests\Feature;

use App\Models\{Asset, User};
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\{DB, Schema};
use Tests\TestCase;

class AssetManagementTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:']);
        DB::purge('sqlite');
        $this->travelTo(now()->setDate(2026, 10, 20));
        foreach (['users' => ['name', 'role'], 'products' => ['title', 'sku', 'category', 'brand', 'deleted_at'],
            'purchases' => ['po_number', 'status', 'received_date', 'order_date'],
            'purchase_items' => ['purchase_id', 'product_id', 'quantity', 'unit_price']] as $name => $columns) {
            Schema::create($name, function (Blueprint $table) use ($columns) {
                $table->id(); foreach ($columns as $column) $table->string($column)->nullable(); $table->timestamps();
            });
        }
        (require database_path('migrations/2025_12_14_000001_create_assets_table.php'))->up();
        (require database_path('migrations/2026_10_20_000002_add_asset_depreciation_management.php'))->up();
        DB::table('users')->insert(['id' => 1, 'name' => 'Asset Tester', 'role' => 'staff']);
        $this->actingAs(User::find(1), 'sanctum');
    }

    private function createAsset(array $extra = []): array
    {
        return $this->postJson('/api/assets', array_replace([
            'name' => 'Laptop', 'purchase_price' => 1200, 'purchase_date' => '2026-01-15',
            'depreciation_method' => 'straight_line', 'depreciation_start_date' => '2026-01-15',
            'useful_life_months' => 12, 'residual_value' => 0, 'category' => 'IT', 'location' => 'Office',
        ], $extra))->assertCreated()->json();
    }

    public function test_month_end_depreciation_is_exact_and_repeated_posting_is_idempotent(): void
    {
        $asset = $this->createAsset();
        $id = $asset['id'];
        $this->assertSame('1200.00', $asset['current_value']);
        $this->postJson("/api/assets/$id/depreciate", ['as_of' => '2026-01-30'])->assertOk()->assertJsonPath('posted', 0);
        $this->postJson("/api/assets/$id/depreciate", ['as_of' => '2026-09-30'])->assertOk()->assertJsonPath('posted', 9)
            ->assertJsonPath('asset.accumulated_depreciation', '900.00')->assertJsonPath('asset.current_value', '300.00');
        $this->postJson("/api/assets/$id/depreciate", ['as_of' => '2026-10-20'])->assertOk()->assertJsonPath('posted', 0);
        $this->assertSame(9, DB::table('asset_depreciations')->count());
        $this->postJson("/api/assets/$id/depreciate", ['as_of' => '2026-10-31'])->assertUnprocessable();
    }

    public function test_fractional_rounding_ends_at_residual_and_financial_history_is_locked(): void
    {
        $id = $this->createAsset(['purchase_price' => 100, 'residual_value' => 1, 'useful_life_months' => 7])['id'];
        $this->postJson("/api/assets/$id/depreciate", ['as_of' => '2026-09-30'])->assertOk()->assertJsonPath('posted', 7)
            ->assertJsonPath('asset.current_value', '1.00')->assertJsonPath('asset.accumulated_depreciation', '99.00');
        $this->assertEqualsWithDelta(99, DB::table('asset_depreciations')->sum('amount'), 0.0001);
        $this->putJson("/api/assets/$id", ['useful_life_months' => 12])->assertUnprocessable();
        $this->putJson("/api/assets/$id", ['depreciation_start_date' => null])->assertUnprocessable();
        $this->putJson("/api/assets/$id", ['location' => 'New office', 'current_value' => 999])->assertOk()->assertJsonPath('current_value', '1.00');
        $this->deleteJson("/api/assets/$id")->assertUnprocessable();
        $this->assertSame(7, DB::table('asset_depreciations')->count());
    }

    public function test_disposal_posts_due_months_and_preserves_cost_and_history(): void
    {
        $id = $this->createAsset()['id'];
        $this->postJson("/api/assets/$id/dispose", ['disposal_date' => '2026-10-15', 'disposal_proceeds' => 250, 'disposal_reason' => 'Sold'])
            ->assertOk()->assertJsonPath('status', 'disposed')->assertJsonPath('disposal_book_value', '300.00')
            ->assertJsonPath('current_value', '0.00')->assertJsonPath('purchase_price', '1200.00')->assertJsonCount(9, 'depreciations');
        $this->postJson("/api/assets/$id/depreciate", ['as_of' => '2026-10-20'])->assertUnprocessable();
        $this->putJson("/api/assets/$id", ['status' => 'active'])->assertUnprocessable();
        $this->deleteJson("/api/assets/$id")->assertUnprocessable();
        $this->getJson('/api/assets?status=disposed')->assertOk()->assertJsonPath('summary.book_value', 0)->assertJsonPath('total', 1);
    }

    public function test_invalid_settings_and_backdated_disposal_are_rejected(): void
    {
        foreach (['residual_value' => 1300, 'useful_life_months' => 0, 'depreciation_start_date' => '2025-12-01'] as $field => $value) {
            $this->postJson('/api/assets', ['name' => 'Invalid', 'purchase_price' => 1200, 'purchase_date' => '2026-01-01',
                'depreciation_method' => 'straight_line', 'useful_life_months' => 12, 'residual_value' => 0,
                'depreciation_start_date' => '2026-01-01', $field => $value])->assertUnprocessable();
        }
        $id = $this->createAsset()['id'];
        $this->postJson("/api/assets/$id/depreciate", ['as_of' => '2026-09-30'])->assertOk();
        $this->postJson("/api/assets/$id/dispose", ['disposal_date' => '2026-09-15', 'disposal_proceeds' => 0, 'disposal_reason' => 'Scrapped'])->assertUnprocessable();
        $this->assertSame('active', Asset::find($id)->status);
        $this->assertSame(9, DB::table('asset_depreciations')->count());
    }

    public function test_existing_manual_values_summary_filters_and_paid_purchase_sources(): void
    {
        $manual = $this->createAsset(['depreciation_method' => 'none', 'current_value' => 800]);
        $this->assertSame('800.00', $manual['current_value']);
        $this->createAsset(['name' => 'Second', 'category' => 'Furniture', 'purchase_price' => 600]);
        $this->getJson('/api/assets?per_page=1')->assertOk()->assertJsonPath('summary.count', 2)
            ->assertJsonPath('summary.cost', 1800)->assertJsonPath('summary.book_value', 1400)->assertJsonCount(1, 'data');
        $this->getJson('/api/assets?category=IT')->assertOk()->assertJsonPath('total', 1);
        DB::table('products')->insert(['id' => 1, 'title' => 'Desk', 'sku' => 'DSK']);
        DB::table('purchases')->insert(['id' => 1, 'po_number' => 'PO-PAID', 'status' => 'paid', 'order_date' => '2026-10-01']);
        DB::table('purchase_items')->insert(['id' => 1, 'purchase_id' => 1, 'product_id' => 1, 'quantity' => 1, 'unit_price' => 500]);
        $this->getJson('/api/assets/filter-options')->assertOk()->assertJsonPath('purchase_items.0.product_title', 'Desk');
        $this->deleteJson('/api/assets/'.$manual['id'])->assertOk();
        $old = Asset::create(['asset_code' => 'AST-OLD', 'name' => 'Legacy disposed asset', 'status' => 'disposed', 'purchase_price' => 700, 'current_value' => 600]);
        $this->getJson('/api/assets?status=disposed')->assertOk()->assertJsonPath('summary.book_value', 0);
        $this->assertEquals(600, $old->fresh()->current_value);
    }

    public function test_viewer_can_read_but_cannot_change_asset_values(): void
    {
        $id = $this->createAsset()['id'];
        $this->actingAs(new User(['role' => 'viewer']), 'sanctum');
        $this->getJson("/api/assets/$id")->assertOk();
        $this->postJson("/api/assets/$id/depreciate", ['as_of' => '2026-09-30'])->assertForbidden();
        $this->postJson("/api/assets/$id/dispose", ['disposal_date' => '2026-10-15', 'disposal_proceeds' => 0, 'disposal_reason' => 'Test'])->assertForbidden();
    }
}
