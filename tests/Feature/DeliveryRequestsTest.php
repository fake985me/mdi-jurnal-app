<?php

namespace Tests\Feature;

use App\Http\Controllers\Api\DeliveryRequestController;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{DB, Schema};
use Tests\TestCase;

class DeliveryRequestsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:']);
        DB::purge('sqlite');
        foreach ([
            'products' => ['title', 'deleted_at', 'stock'],
            'current_stocks' => ['product_id', 'warehouse_id', 'quantity', 'last_updated'],
            'delivery_fulfillments' => ['request_key', 'status', 'courier', 'tracking_number', 'destination', 'notes', 'shipped_at', 'delivered_at'],
            'sales' => ['status', 'invoice_number', 'customer_name', 'deleted_at', 'warehouse_id', 'notes'],
            'sale_items' => ['sale_id', 'product_id', 'quantity'],
            'deliveries' => ['sale_id', 'status', 'delivered_date'],
            'lendings' => ['product_id', 'quantity', 'status', 'lending_code', 'borrower_name', 'actual_return_date', 'borrower_contact', 'lending_date', 'expected_return_date', 'notes', 'user_id'],
            'lending_returns' => ['lending_id', 'return_date', 'quantity_returned', 'condition', 'notes'],
            'rma_shipments' => ['product_id', 'quantity', 'status', 'shipment_code', 'recipient_name'],
            'stock_transfers' => ['product_id', 'quantity', 'status', 'transfer_code', 'from_warehouse_id', 'to_warehouse_id'],
            'warehouses' => ['name', 'is_default'],
            'stock_transactions' => ['product_id', 'quantity', 'transaction_type', 'reference_type', 'reference_id', 'notes', 'user_id'],
        ] as $table => $columns) {
            Schema::create($table, function (Blueprint $schema) use ($columns) {
                $schema->id();
                foreach ($columns as $column) {
                    $schema->string($column)->nullable();
                }
                $schema->timestamps();
            });
        }
    }

    public function test_sources_are_automatically_listed_without_duplicate_stock_movements(): void
    {
        $date = '2026-10-11 10:00:00';
        DB::table('products')->insert(['id' => 1, 'title' => 'Router']);
        DB::table('sales')->insert(['id' => 1, 'invoice_number' => 'INV-1', 'status' => 'pending', 'created_at' => $date]);
        DB::table('sale_items')->insert(['sale_id' => 1, 'product_id' => 1, 'quantity' => 2]);
        DB::table('deliveries')->insert(['sale_id' => 1, 'status' => 'shipped']);
        DB::table('lendings')->insert(['product_id' => 1, 'quantity' => 1, 'lending_code' => 'LND-1', 'status' => 'borrowed', 'created_at' => $date]);
        DB::table('rma_shipments')->insert(['product_id' => 1, 'quantity' => 1, 'shipment_code' => 'RMA-1', 'status' => 'pending', 'created_at' => $date]);
        DB::table('stock_transfers')->insert(['product_id' => 1, 'quantity' => 3, 'transfer_code' => 'TRF-1', 'status' => 'pending', 'created_at' => $date]);
        foreach (['sale', 'lending', 'rma_shipment', 'manual_adjustment'] as $type) {
            DB::table('stock_transactions')->insert(['product_id' => 1, 'quantity' => 1, 'transaction_type' => 'out', 'reference_type' => $type, 'reference_id' => 1, 'created_at' => $date]);
        }
        DB::table('stock_transactions')->insert(['transaction_type' => 'in', 'reference_type' => 'purchase']);
        $controller = new DeliveryRequestController;
        $response = $controller->index(Request::create('/api/delivery-requests'))->getData(true);
        $this->assertSame(5, $response['total']);
        $sale = collect($response['data'])->firstWhere('id', 'sale-1');
        $this->assertSame('shipped', $sale['status']);
        $this->assertSame('Router', $sale['items'][0]['product']);
        $filtered = $controller->index(Request::create('/api/delivery-requests', 'GET', ['search' => 'TRF-1', 'per_page' => 1]))->getData(true);
        $this->assertSame(1, $filtered['total']);
        $this->assertSame('Transfer Gudang', $filtered['data'][0]['type']);
        // A newly saved outgoing transaction appears immediately without a backfill.
        DB::table('stock_transactions')->insert(['product_id' => 1, 'quantity' => 1, 'transaction_type' => 'out', 'reference_type' => 'msa_replacement']);
        $this->assertSame(6, $controller->index(Request::create('/api/delivery-requests'))->getData(true)['total']);
    }

    public function test_cancelled_and_deleted_sales_are_excluded_from_delivery_requests(): void
    {
        DB::table('sales')->insert([
            ['id' => 1, 'status' => 'cancelled', 'deleted_at' => null],
            ['id' => 2, 'status' => 'pending', 'deleted_at' => '2026-10-11 10:00:00'],
        ]);
        foreach ([1, 2] as $id) {
            DB::table('stock_transactions')->insert(['transaction_type' => 'out', 'reference_type' => 'sale', 'reference_id' => $id]);
        }
        $this->assertSame(0, (new DeliveryRequestController)->index(Request::create('/api/delivery-requests'))->getData(true)['total']);
    }

    public function test_reactivating_and_recancelling_a_sale_updates_stock_once_per_transition(): void
    {
        DB::table('products')->insert(['id' => 1, 'title' => 'Router']);
        DB::table('sales')->insert(['id' => 1, 'status' => 'cancelled', 'invoice_number' => 'INV-1']);
        DB::table('sale_items')->insert(['sale_id' => 1, 'product_id' => 1, 'quantity' => 2]);
        DB::table('deliveries')->insert(['sale_id' => 1, 'status' => 'cancelled']);
        $stock = \Mockery::mock(\App\Services\StockSyncService::class);
        $stock->shouldReceive('deductStock')->once()->with(1, 2, 'sale', 1, \Mockery::type('string'), null)->andReturn(true);
        $stock->shouldReceive('addStock')->once()->with(1, 2, 'sale_cancelled', 1, \Mockery::type('string'), null)->andReturn(true);
        $controller = new \App\Http\Controllers\Api\SaleController($stock);
        foreach (['pending', 'pending', 'cancelled', 'cancelled'] as $status) {
            $this->assertSame(200, $controller->update(Request::create('/api/sales/1', 'PUT', ['status' => $status]), 1)->status());
        }
        $this->assertSame('cancelled', DB::table('deliveries')->value('status'));
    }

    public function test_cancelled_sale_cannot_be_completed_by_delivery(): void
    {
        DB::table('sales')->insert(['id' => 1, 'status' => 'cancelled']);
        DB::table('deliveries')->insert(['id' => 1, 'sale_id' => 1, 'status' => 'preparing']);
        $response = (new \App\Http\Controllers\Api\DeliveryController)->update(Request::create('/api/deliveries/1', 'PUT', ['status' => 'delivered']), 1);
        $this->assertSame(422, $response->status());
        $this->assertSame('cancelled', DB::table('sales')->value('status'));
        $this->assertSame('preparing', DB::table('deliveries')->value('status'));
    }

    public function test_partial_returns_keep_lending_open_and_reject_excess_returns(): void
    {
        DB::table('products')->insert(['id' => 1, 'title' => 'Router']);
        DB::table('lendings')->insert(['id' => 1, 'product_id' => 1, 'quantity' => 10, 'status' => 'borrowed', 'lending_code' => 'LND-1']);
        $stock = \Mockery::mock(\App\Services\StockSyncService::class);
        $stock->shouldReceive('addStock')->once()->with(1, 2, 'lending_return', 1, \Mockery::type('string'))->andReturn(true);
        $controller = new \App\Http\Controllers\Api\LendingController($stock);
        $request = fn ($qty, $condition) => Request::create('/api/lendings/1/return', 'POST', ['return_date' => '2026-10-11', 'quantity_returned' => $qty, 'condition' => $condition]);
        $response = $controller->processReturn($request(2, 'good'), 1);
        $this->assertSame(200, $response->status());
        $this->assertSame('borrowed', $response->getData()->status);
        $this->assertSame(8, $response->getData()->remaining_quantity);
        $this->assertSame(422, $controller->processReturn($request(9, 'good'), 1)->status());
        $this->assertSame(1, DB::table('lending_returns')->count());
        $response = $controller->processReturn($request(8, 'damaged'), 1);
        $this->assertSame('returned', $response->getData()->status);
        $this->assertSame(0, $response->getData()->remaining_quantity);
    }

    public function test_api_permissions_and_user_management_match_roles(): void
    {
        foreach (['guest', 'viewer'] as $role) {
            $this->actingAs(new \App\Models\User(['role' => $role]), 'sanctum');
            $this->postJson('/api/stock/transaction')->assertForbidden();
        }
        foreach (['sales', 'staff', 'admin'] as $role) {
            $this->actingAs(new \App\Models\User(['role' => $role]), 'sanctum');
            $this->postJson('/api/users')->assertForbidden();
        }
        $this->actingAs(new \App\Models\User(['role' => 'sales']), 'sanctum');
        $this->postJson('/api/pages')->assertForbidden();
        $this->actingAs(new \App\Models\User(['role' => 'superadmin']), 'sanctum');
        $this->postJson('/api/pages')->assertUnprocessable();
    }

    public function test_every_registered_controller_action_exists(): void
    {
        foreach (\Illuminate\Support\Facades\Route::getRoutes() as $route) {
            $action = $route->getActionName();
            if (str_contains($action, '@')) {
                [$class, $method] = explode('@', $action);
                $this->assertTrue(method_exists($class, $method), $action);
            }
        }
    }

    public function test_stock_transaction_accepts_omitted_notes(): void
    {
        DB::table('products')->insert(['id' => 1, 'title' => 'Router', 'stock' => 10]);
        DB::table('warehouses')->insert(['id' => 1, 'name' => 'Default', 'is_default' => 1]);
        $controller = new \App\Http\Controllers\Api\StockController;
        foreach (['out', 'in'] as $direction) {
            $response = $controller->store(Request::create('/api/stock/transaction', 'POST', [
                'product_id' => 1, 'quantity' => 2, 'transaction_type' => $direction,
            ]));
            $this->assertSame(201, $response->status(), $response->getContent());
        }
        $this->assertEquals(10, DB::table('products')->value('stock'));
        $this->assertEquals(10, DB::table('current_stocks')->value('quantity'));
    }

    public function test_lending_stock_transaction_keeps_the_actual_document_id(): void
    {
        DB::table('products')->insert(['id' => 1, 'title' => 'Router', 'stock' => 10]);
        DB::table('warehouses')->insert(['id' => 1, 'name' => 'Default', 'is_default' => 1]);
        $controller = new \App\Http\Controllers\Api\LendingController(new \App\Services\StockSyncService);
        $response = $controller->store(Request::create('/api/lendings', 'POST', [
            'lending_code' => 'LND-1', 'product_id' => 1, 'quantity' => 2,
            'borrower_name' => 'Customer', 'lending_date' => '2026-10-11', 'expected_return_date' => '2026-10-12',
        ]));
        $this->assertSame(201, $response->status(), $response->getContent());
        $this->assertEquals($response->getData()->id, DB::table('stock_transactions')->value('reference_id'));
        $this->assertEquals(8, DB::table('current_stocks')->value('quantity'));
    }

    public function test_outgoing_request_can_be_shipped_and_received_without_deducting_stock_twice(): void
    {
        DB::table('products')->insert(['id' => 1, 'title' => 'Router', 'stock' => 8]);
        DB::table('stock_transactions')->insert(['id' => 1, 'product_id' => 1, 'quantity' => 2, 'transaction_type' => 'out', 'reference_type' => 'manual_adjustment']);
        $controller = new DeliveryRequestController;
        $request = fn ($status) => Request::create('/api/delivery-requests/stock-1', 'PUT', [
            'status' => $status, 'courier' => 'Internal', 'tracking_number' => 'SJ-001', 'destination' => 'Alamat penerima',
        ]);
        $this->assertSame(422, $controller->update($request('delivered'), 'stock-1')->status());
        $this->assertSame(0, DB::table('delivery_fulfillments')->count());
        $this->assertSame(200, $controller->update($request('shipped'), 'stock-1')->status());
        $this->assertSame(422, $controller->update($request('shipped'), 'stock-1')->status());
        $row = $controller->index(Request::create('/api/delivery-requests'))->getData(true)['data'][0];
        $this->assertSame('shipped', $row['status']);
        $this->assertSame('SJ-001', $row['tracking_number']);
        $this->assertSame(200, $controller->update($request('delivered'), 'stock-1')->status());
        $this->assertSame('delivered', DB::table('delivery_fulfillments')->value('status'));
        $this->assertEquals(8, DB::table('products')->value('stock'));
        $this->assertSame(1, DB::table('stock_transactions')->count());
    }

    public function test_lending_shipping_status_is_separate_from_borrowing_status(): void
    {
        DB::table('lendings')->insert(['id' => 1, 'quantity' => 2, 'status' => 'borrowed', 'lending_code' => 'LND-1']);
        $controller = new DeliveryRequestController;
        foreach (['shipped', 'delivered'] as $status) {
            $response = $controller->update(Request::create('/api/delivery-requests/lending-1', 'PUT', [
                'status' => $status, 'courier' => 'Internal', 'tracking_number' => 'SJ-002', 'destination' => 'Peminjam',
            ]), 'lending-1');
            $this->assertSame(200, $response->status());
        }
        $this->assertSame('borrowed', DB::table('lendings')->value('status'));
        $row = $controller->index(Request::create('/api/delivery-requests'))->getData(true)['data'][0];
        $this->assertSame('delivered', $row['status']);
    }
}
