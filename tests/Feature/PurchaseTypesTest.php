<?php

namespace Tests\Feature;

use App\Http\Controllers\Api\PurchaseController;
use App\Http\Controllers\Api\PaymentController;
use App\Models\Purchase;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{DB, Schema};
use Tests\TestCase;

class PurchaseTypesTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:']);
        DB::purge('sqlite');
        foreach ([
            'purchases' => ['po_number', 'transaction_type', 'supplier_id', 'supplier_name', 'supplier_address', 'supplier_phone', 'warehouse_id', 'order_date', 'received_date', 'status', 'is_for_asset', 'notes', 'user_id', 'total_amount'],
            'purchase_items' => ['purchase_id', 'product_id', 'quantity', 'unit_price', 'subtotal', 'is_for_asset'],
            'payments' => ['payable_type', 'payable_id', 'payment_type', 'amount', 'payment_date', 'method', 'status', 'reference_number', 'notes', 'user_id'],
            'products' => ['title', 'deleted_at'],
            'suppliers' => ['name', 'company'],
            'warehouses' => ['name', 'is_default'],
            'users' => ['name'],
        ] as $table => $columns) {
            Schema::create($table, function (Blueprint $schema) use ($columns) {
                $schema->id();
                foreach ($columns as $column) {
                    if (in_array($column, ['amount', 'total_amount', 'quantity', 'unit_price', 'subtotal'])) {
                        $schema->decimal($column, 15, 2)->nullable();
                    } else {
                        $schema->string($column)->nullable();
                    }
                }
                $schema->timestamps();
            });
        }
        DB::table('products')->insert(['id' => 1, 'title' => 'Router']);
        $user = new User;
        $user->id = 1;
        $this->actingAs($user);
    }

    private function createPurchase(?string $type, string $number): array
    {
        $data = ['po_number' => $number, 'supplier_name' => 'Supplier', 'order_date' => '2026-10-19',
            'items' => [['product_id' => 1, 'quantity' => 2, 'unit_price' => 500]]];
        if ($type !== null) $data['transaction_type'] = $type;
        $response = app(PurchaseController::class)->store(Request::create('/api/purchases', 'POST', $data));
        $this->assertSame(201, $response->getStatusCode(), $response->getContent());
        return $response->getData(true);
    }

    public function test_four_types_persist_and_only_orders_and_invoices_create_payments(): void
    {
        foreach (['request', 'quotation', 'order', 'invoice'] as $type) {
            $purchase = $this->createPurchase($type, $type.'-1');
            $this->assertSame($type, $purchase['transaction_type']);
            $this->assertEquals(1000, $purchase['total_amount']);
            $this->assertSame(in_array($type, ['order', 'invoice']) ? 1 : 0,
                DB::table('payments')->where('payable_id', $purchase['id'])->count());
        }
        $this->assertSame('order', $this->createPurchase(null, 'legacy')['transaction_type']);
    }

    public function test_type_filter_and_summary_cover_all_filtered_pages(): void
    {
        $this->createPurchase('request', 'PR-1');
        $this->createPurchase('request', 'PR-2');
        $this->createPurchase('order', 'PO-1');
        $data = app(PurchaseController::class)->index(Request::create('/api/purchases', 'GET', [
            'transaction_type' => 'request', 'per_page' => 1, 'end_date' => '2026-10-19',
        ]))->getData(true);
        $this->assertSame(2, $data['total']);
        $this->assertCount(1, $data['data']);
        $this->assertEquals(2, $data['summary']['count']);
        $this->assertEquals(2000, $data['summary']['total']);
    }

    public function test_status_filters_match_payment_summary_and_exclude_preliminary_documents(): void
    {
        foreach (['unpaid', 'partial', 'paid', 'cancelled'] as $status) {
            $p = $this->createPurchase('invoice', $status);
            if ($status === 'cancelled') Purchase::find($p['id'])->update(['status' => 'cancelled']);
            if (in_array($status, ['partial', 'paid'])) {
                DB::table('payments')->where('payable_id', $p['id'])->update(['status' => 'paid', 'amount' => $status === 'partial' ? 250 : 1000]);
            }
        }
        $this->createPurchase('request', 'PR-1');
        foreach (['unpaid', 'partial', 'paid', 'cancelled', 'recorded'] as $status) {
            $response = app(PurchaseController::class)->index(Request::create('/api/purchases', 'GET', ['status' => $status]));
            $this->assertSame(200, $response->getStatusCode(), $response->getContent());
            $data = $response->getData(true);
            $this->assertSame(1, $data['total'], $status);
            if (in_array($status, ['unpaid', 'partial', 'paid'])) $this->assertSame($status, $data['data'][0]['payment_status']);
        }
    }

    public function test_preliminary_documents_cannot_receive_payments(): void
    {
        foreach (['request', 'quotation'] as $type) {
            $purchase = $this->createPurchase($type, $type);
            $response = app(PaymentController::class)->store(Request::create('/api/payments', 'POST', [
                'payable_type' => 'purchase', 'payable_id' => $purchase['id'], 'payment_type' => 'full', 'amount' => 1000,
            ]));
            $this->assertSame(422, $response->getStatusCode());
        }
        $this->assertSame(0, DB::table('payments')->count());
    }
}
