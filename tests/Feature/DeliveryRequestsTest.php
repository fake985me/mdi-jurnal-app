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
            'products' => ['title', 'sku', 'deleted_at', 'stock'],
            'current_stocks' => ['product_id', 'warehouse_id', 'quantity', 'last_updated'],
            'delivery_fulfillments' => ['request_key', 'status', 'courier', 'tracking_number', 'destination', 'notes', 'shipped_at', 'delivered_at', 'attachments'],
            'sales' => ['status', 'invoice_number', 'po_number', 'customer_id', 'customer_name', 'customer_email', 'customer_phone', 'customer_address', 'sales_person_id', 'sale_date', 'due_date', 'memo', 'total_amount', 'subtotal', 'tax_type', 'tax_rate', 'tax_amount', 'discount_amount', 'grand_total', 'deleted_at', 'warehouse_id', 'notes'],
            'invoices' => ['sale_id', 'invoice_date', 'due_date', 'subtotal', 'tax_type', 'tax_rate', 'tax_amount', 'discount_amount', 'total', 'deleted_at'],
            'sale_items' => ['sale_id', 'product_id', 'quantity', 'unit_price', 'subtotal', 'description', 'unit', 'discount', 'tax_code'],
            'payments' => ['payable_type', 'payable_id', 'status', 'amount'],
            'customers' => ['name'],
            'sales_people' => ['name'],
            'deliveries' => ['sale_id', 'status', 'shipped_date', 'delivered_date', 'courier', 'tracking_number', 'destination', 'notes'],
            'lendings' => ['product_id', 'quantity', 'status', 'lending_code', 'borrower_name', 'actual_return_date', 'borrower_contact', 'lending_date', 'expected_return_date', 'notes', 'user_id'],
            'lending_returns' => ['lending_id', 'return_date', 'quantity_returned', 'condition', 'notes'],
            'rma_shipments' => ['rma_id', 'product_id', 'quantity', 'status', 'shipment_code', 'recipient_name', 'recipient_address', 'recipient_phone', 'user_id', 'serial_numbers', 'courier', 'tracking_number', 'shipped_date', 'delivered_date', 'notes'],
            'rmas' => ['sale_id', 'sale_item_id', 'warranty_id', 'rma_code', 'user_id', 'customer_name', 'customer_contact', 'reason', 'issue_date', 'received_date', 'condition', 'condition_level', 'notes', 'product_id', 'quantity', 'serial_number', 'returned_serial_number', 'replacement_requested', 'resolution', 'warranty_claim_flow', 'inspected_at', 'status', 'deleted_at'],
            'warranties' => ['sale_id', 'product_id', 'serial_number', 'status', 'warranty_period_months', 'start_date', 'deleted_at', 'end_date'],
            'delivery_items' => ['delivery_id', 'product_id', 'quantity', 'serial_number'],
            'returned_items' => ['source_key', 'source_type', 'source_id', 'source_code', 'product_id', 'serial_number', 'quantity', 'available_quantity', 'condition', 'book_value', 'notes', 'inspected_by', 'inspected_at'],
            'project_investments' => ['project_code', 'project_name', 'client_name', 'warehouse_id', 'status', 'total_value', 'delivery_progress', 'installation_progress', 'expected_end_date', 'actual_end_date'],
            'project_investment_items' => ['project_investment_id', 'product_id', 'quantity', 'unit_price', 'total_price', 'stock_deducted', 'serial_numbers', 'returned_item_id', 'notes'],
            'project_materials' => ['project_investment_id', 'total_price'],
            'project_item_changes' => ['project_investment_id', 'project_investment_item_id', 'type', 'quantity', 'old_serial_numbers', 'new_serial_numbers', 'notes', 'user_id'],
            'msa_projects' => ['msa_code', 'project_investment_id', 'product_id', 'quantity', 'serial_numbers', 'replacement_serial_numbers', 'issue_type', 'issue_description', 'status', 'reported_date', 'resolved_date', 'resolution', 'condition', 'user_id'],
            'users' => ['name'],
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
        foreach (['sale', 'lending', 'rma_shipment', 'msa_replacement'] as $type) {
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

    public function test_adjustments_remain_in_history_and_cannot_be_processed_as_deliveries(): void
    {
        $controller = new DeliveryRequestController;
        foreach (['adjustment', 'manual_adjustment', 'stock_adjustment'] as $index => $type) {
            $id = $index + 1;
            DB::table('stock_transactions')->insert(['id' => $id, 'transaction_type' => 'out', 'reference_type' => $type]);
            DB::table('delivery_fulfillments')->insert(['request_key' => 'stock-'.$id, 'status' => 'shipped']);
            $response = $controller->updateStatus(Request::create('/api/delivery-requests/stock-'.$id.'/status', 'PUT', ['status' => 'delivered']), 'stock-'.$id);
            $this->assertSame(422, $response->status());
        }
        $this->assertSame(0, $controller->index(Request::create('/api/delivery-requests'))->getData(true)['total']);
        $this->assertSame(3, DB::table('stock_transactions')->count());
        $this->assertSame(3, DB::table('delivery_fulfillments')->where('status', 'shipped')->count());
        DB::table('stock_transactions')->insert(['transaction_type' => 'out', 'reference_type' => 'msa_replacement']);
        $this->assertSame(1, $controller->index(Request::create('/api/delivery-requests'))->getData(true)['total']);
    }

    public function test_shipping_list_includes_requests_without_deliveries_and_existing_shipping_details(): void
    {
        DB::table('sales')->insert([
            ['id' => 1, 'invoice_number' => 'INV-1', 'status' => 'pending'],
            ['id' => 2, 'invoice_number' => 'INV-2', 'status' => 'completed'],
        ]);
        DB::table('deliveries')->insert([
            'id' => 10, 'sale_id' => 2, 'status' => 'shipped',
            'courier' => 'Internal', 'tracking_number' => 'SJ-010', 'destination' => 'Jakarta',
        ]);
        DB::table('lendings')->insert(['id' => 1, 'lending_code' => 'LND-1', 'status' => 'borrowed', 'quantity' => 1]);
        DB::table('delivery_fulfillments')->insert([
            'request_key' => 'lending-1', 'status' => 'shipped',
            'courier' => 'JNE', 'tracking_number' => 'SJ-011', 'destination' => 'Bandung',
        ]);

        $controller = new DeliveryRequestController;
        $response = $controller->index(Request::create('/api/delivery-requests'))->getData(true);
        $rows = collect($response['data'])->keyBy('id');
        $this->assertSame(3, $response['total']);
        $this->assertSame('preparing', $rows['sale-1']['status']);
        $this->assertNull($rows['sale-1']['delivery_id']);
        $this->assertSame(10, $rows['sale-2']['delivery_id']);
        $this->assertSame('SJ-010', $rows['sale-2']['tracking_number']);
        $this->assertSame('Jakarta', $rows['sale-2']['destination']);
        $this->assertSame('shipped', $rows['lending-1']['status']);
        $this->assertSame('SJ-011', $rows['lending-1']['tracking_number']);
        $filtered = $controller->index(Request::create('/api/delivery-requests', 'GET', ['search' => 'SJ-010']))->getData(true);
        $this->assertSame(1, $filtered['total']);
        $this->assertSame('sale-2', $filtered['data'][0]['id']);
        $this->assertSame(1, DB::table('deliveries')->count());
        $this->assertSame(0, DB::table('stock_transactions')->count());
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
        DB::table('stock_transactions')->insert(['id' => 1, 'product_id' => 1, 'quantity' => 2, 'transaction_type' => 'out', 'reference_type' => 'msa_replacement']);
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

    public function test_original_status_modal_accepts_only_status_tracking_and_notes_for_rma(): void
    {
        DB::table('products')->insert(['id' => 1, 'title' => 'Router']);
        DB::table('rmas')->insert(['id' => 1, 'product_id' => 1, 'quantity' => 1, 'status' => 'processed', 'resolution' => 'replace']);
        DB::table('rma_shipments')->insert(['id' => 1, 'rma_id' => 1, 'product_id' => 1, 'quantity' => 1, 'status' => 'pending', 'shipment_code' => 'SHIP-1']);
        $stock = \Mockery::mock(\App\Services\StockSyncService::class);
        $stock->shouldReceive('deductStock')->once()->with(1, 1, 'rma_shipment', 1, \Mockery::type('string'))->andReturn(true);
        $this->app->instance(\App\Services\StockSyncService::class, $stock);
        $controller = new DeliveryRequestController;
        foreach (['shipped', 'shipped', 'in_transit', 'delivered'] as $status) {
            $response = $controller->updateStatus(Request::create('/api/delivery-requests/rma-1/status', 'PUT', [
                'status' => $status, 'tracking_number' => 'SJ-RMA-1', 'notes' => 'Penggantian',
            ]), 'rma-1');
            $this->assertSame(200, $response->status(), $response->getContent());
        }
        $this->assertSame('completed', DB::table('rmas')->value('status'));
        $this->assertSame('delivered', DB::table('rma_shipments')->value('status'));
        $response = $controller->updateStatus(Request::create('/api/delivery-requests/rma-1/status', 'PUT', ['status' => 'preparing']), 'rma-1');
        $this->assertSame(422, $response->status());
    }

    public function test_replacement_serial_import_updates_only_returned_units_and_preserves_rma_history(): void
    {
        DB::table('products')->insert(['id' => 1, 'title' => 'Router', 'sku' => 'RTR']);
        DB::table('sales')->insert(['id' => 1, 'status' => 'completed']);
        DB::table('deliveries')->insert(['id' => 1, 'sale_id' => 1, 'status' => 'delivered']);
        DB::table('delivery_items')->insert([
            ['delivery_id' => 1, 'product_id' => 1, 'quantity' => 1, 'serial_number' => 'OLD-1'],
            ['delivery_id' => 1, 'product_id' => 1, 'quantity' => 1, 'serial_number' => 'OTHER-1'],
        ]);
        DB::table('warranties')->insert(['sale_id' => 1, 'product_id' => 1, 'serial_number' => 'OLD-1', 'end_date' => '2027-10-12']);
        DB::table('rmas')->insert(['id' => 1, 'sale_id' => 1, 'product_id' => 1, 'quantity' => 1, 'serial_number' => 'OLD-1', 'replacement_requested' => 1]);
        DB::table('rma_shipments')->insert(['id' => 1, 'rma_id' => 1, 'product_id' => 1, 'quantity' => 1, 'status' => 'pending']);
        $shipment = \App\Models\RMAShipment::find(1);
        $import = new \App\Imports\RMAReplacementSerialImport($shipment);
        $import->array([['sku' => 'RTR', 'serial_number' => 'NEW-1']]);
        $this->assertSame(['NEW-1'], $shipment->fresh()->serial_numbers);
        $this->assertSame('NEW-1', DB::table('warranties')->value('serial_number'));
        $this->assertSame('2027-10-12', DB::table('warranties')->value('end_date'));
        $this->assertSame(['NEW-1', 'OTHER-1'], DB::table('delivery_items')->orderBy('id')->pluck('serial_number')->all());
        $this->assertSame('NEW-1', DB::table('rmas')->value('serial_number'));
        $this->assertSame('OLD-1', DB::table('rmas')->value('returned_serial_number'));
        // Re-importing does not add units; correcting an imported SN follows its previous value.
        $import->array([['sku' => 'RTR', 'serial_number' => 'NEW-1']]);
        $import->array([['sku' => 'RTR', 'serial_number' => 'NEW-2']]);
        $this->assertSame('NEW-2', DB::table('warranties')->value('serial_number'));
        $this->assertSame(2, DB::table('delivery_items')->count());
        $this->assertSame(1, DB::table('warranties')->count());
        $this->assertSame(0, DB::table('stock_transactions')->count());
        try {
            $import->array([['sku' => 'RTR', 'serial_number' => 'NEW-3'], ['sku' => 'RTR', 'serial_number' => 'NEW-4']]);
            $this->fail('Excess serials must be rejected');
        } catch (\RuntimeException $e) {
            $this->assertSame('NEW-2', DB::table('warranties')->value('serial_number'));
        }
        $file = \Illuminate\Http\UploadedFile::fake()->createWithContent('replacement.csv', "sku,product_name,quantity,serial_number\nRTR,Router,1,NEW-5\n");
        $request = Request::create('/api/rma-shipments/1/import-serials', 'POST', [], [], ['file' => $file]);
        $response = app(\App\Http\Controllers\Api\RMAShipmentController::class)->importSerialNumbers($request, 1);
        $this->assertSame(200, $response->status(), $response->getContent());
        $this->assertSame(['NEW-5'], $response->getData(true)['shipment']['serial_numbers']);
        $this->assertSame('NEW-5', DB::table('warranties')->value('serial_number'));
        $this->assertSame('NEW-5', DB::table('rmas')->value('serial_number'));
        $this->assertSame('OLD-1', DB::table('rmas')->value('returned_serial_number'));
        $template = new \App\Exports\RMAReplacementSerialTemplate($shipment->fresh(['product']));
        $this->assertSame([['RTR', 'Router', 1, '']], $template->array());
    }

    public function test_status_attachments_are_saved_retained_listed_and_downloadable(): void
    {
        \Illuminate\Support\Facades\Storage::fake('local');
        DB::table('stock_transactions')->insert(['id' => 1, 'transaction_type' => 'out', 'reference_type' => 'msa_replacement']);
        $controller = new DeliveryRequestController;
        $request = Request::create('/api/delivery-requests/stock-1/status', 'POST', ['status' => 'preparing'], [], [
            'attachments' => [
                \Illuminate\Http\UploadedFile::fake()->image('photo.png'),
                \Illuminate\Http\UploadedFile::fake()->create('proof.pdf', 10, 'application/pdf'),
                \Illuminate\Http\UploadedFile::fake()->create('document.docx', 10, 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'),
                \Illuminate\Http\UploadedFile::fake()->create('sheet.xlsx', 10, 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'),
            ],
        ]);
        $response = $controller->updateStatus($request, 'stock-1');
        $this->assertSame(200, $response->status(), $response->getContent());
        $files = $response->getData(true)['attachments'];
        $this->assertCount(4, $files);
        $this->assertArrayNotHasKey('path', $files[0]);
        $stored = \App\Models\DeliveryFulfillment::first()->attachments;
        foreach ($stored as $file) \Illuminate\Support\Facades\Storage::disk('local')->assertExists($file['path']);
        $this->assertSame(200, $controller->downloadAttachment('stock-1', $files[0]['id'])->getStatusCode());
        $controller->updateStatus(Request::create('/api/delivery-requests/stock-1/status', 'PUT', ['status' => 'shipped']), 'stock-1');
        $listed = $controller->index(Request::create('/api/delivery-requests'))->getData(true)['data'][0];
        $this->assertCount(4, $listed['attachments']);
        $this->assertSame('photo.png', $listed['attachments'][0]['original_name']);
        $this->assertSame(1, DB::table('stock_transactions')->count());
        $request = Request::create('/api/delivery-requests/stock-1/status', 'POST', ['status' => 'delivered'], [], [
            'attachments' => [\Illuminate\Http\UploadedFile::fake()->image('extra1.png'), \Illuminate\Http\UploadedFile::fake()->image('extra2.png')],
        ]);
        $this->assertSame(422, $controller->updateStatus($request, 'stock-1')->status());
        $this->assertSame('shipped', \App\Models\DeliveryFulfillment::first()->status);
        $this->assertCount(4, \Illuminate\Support\Facades\Storage::disk('local')->allFiles());
    }

    public function test_invalid_attachment_is_rejected_before_status_changes(): void
    {
        \Illuminate\Support\Facades\Storage::fake('local');
        DB::table('stock_transactions')->insert(['id' => 1, 'transaction_type' => 'out', 'reference_type' => 'msa_replacement']);
        foreach ([
            \Illuminate\Http\UploadedFile::fake()->create('program.exe', 1, 'application/x-msdownload'),
            \Illuminate\Http\UploadedFile::fake()->create('large.pdf', 10241, 'application/pdf'),
        ] as $file) {
            try {
                (new DeliveryRequestController)->updateStatus(Request::create('/api/delivery-requests/stock-1/status', 'POST', ['status' => 'delivered'], [], ['attachments' => [$file]]), 'stock-1');
                $this->fail('Invalid attachment must be rejected');
            } catch (\Illuminate\Validation\ValidationException $e) {
                $this->assertArrayHasKey('attachments.0', $e->errors());
            }
        }
        $this->assertSame(0, DB::table('delivery_fulfillments')->count());
        $this->assertCount(0, \Illuminate\Support\Facades\Storage::disk('local')->allFiles());
    }

    public function test_sale_status_and_attachments_are_saved_together(): void
    {
        \Illuminate\Support\Facades\Storage::fake('local');
        DB::table('sales')->insert(['id' => 1, 'status' => 'pending']);
        DB::table('deliveries')->insert(['id' => 1, 'sale_id' => 1, 'status' => 'preparing']);
        $request = Request::create('/api/delivery-requests/sale-1/status', 'POST', ['status' => 'delivered'], [], [
            'attachments' => [\Illuminate\Http\UploadedFile::fake()->create('receipt.pdf', 10, 'application/pdf')],
        ]);
        $response = (new DeliveryRequestController)->updateStatus($request, 'sale-1');
        $this->assertSame(200, $response->status(), $response->getContent());
        $this->assertSame('delivered', DB::table('deliveries')->value('status'));
        $this->assertSame('completed', DB::table('sales')->value('status'));
        $this->assertSame('receipt.pdf', $response->getData(true)['attachments'][0]['original_name']);
    }

    public function test_warranty_claim_and_normal_inspection_create_return_delivery_without_deducting_stock(): void
    {
        $this->actingAs(new \App\Models\User(['id' => 1, 'role' => 'superadmin']));
        DB::table('products')->insert(['id' => 1, 'title' => 'Router', 'stock' => 0]);
        DB::table('sales')->insert(['id' => 1, 'status' => 'completed', 'customer_name' => 'Customer', 'sale_date' => now()->toDateString()]);
        DB::table('sale_items')->insert(['id' => 1, 'sale_id' => 1, 'product_id' => 1, 'quantity' => 1]);
        DB::table('warranties')->insert(['id' => 1, 'sale_id' => 1, 'product_id' => 1, 'serial_number' => 'OLD-1', 'status' => 'active', 'warranty_period_months' => 12]);
        $controller = new \App\Http\Controllers\Api\WarrantyClaimController;
        $response = $controller->store(Request::create('/api/warranties/1/claim', 'POST', ['issue_date' => now()->toDateString(), 'notes' => 'Tidak menyala']), 1);
        $this->assertSame(201, $response->status());
        $id = $response->getData()->id;
        $this->assertSame('pending', $response->getData()->status);
        $this->assertSame(0, DB::table('stock_transactions')->count());
        $this->assertSame(0, DB::table('rma_shipments')->count());
        $inspection = Request::create('/api/rmas/'.$id.'/inspect', 'POST', ['result' => 'normal', 'condition' => 'Sudah diuji, berfungsi normal']);
        $this->assertSame(200, $controller->inspect($inspection, $id)->status());
        $this->assertSame(422, $controller->inspect($inspection, $id)->status());
        $this->assertSame(1, DB::table('rma_shipments')->count());
        $shipment = \App\Models\RMAShipment::first();
        $this->assertSame(['OLD-1'], $shipment->serial_numbers);
        $this->assertSame(1, (new DeliveryRequestController)->index(Request::create('/api/delivery-requests', 'GET', ['search' => $shipment->shipment_code]))->getData(true)['total']);
        $stock = \Mockery::mock(\App\Services\StockSyncService::class);
        $stock->shouldNotReceive('deductStock');
        $shipping = new \App\Http\Controllers\Api\RMAShipmentController($stock);
        $this->assertSame(200, $shipping->updateStatus(Request::create('/api/status', 'PUT', ['status' => 'delivered']), $shipment->id)->status());
        $this->assertSame('completed', \App\Models\RMA::find($id)->status);
        $this->assertSame(0, DB::table('stock_transactions')->count());
    }

    public function test_claim_rejects_duplicate_active_claims_and_inactive_warranties(): void
    {
        DB::table('products')->insert(['id' => 1, 'title' => 'Router']);
        DB::table('sales')->insert(['id' => 1, 'status' => 'completed', 'customer_name' => 'Customer', 'sale_date' => now()->toDateString()]);
        DB::table('sale_items')->insert(['sale_id' => 1, 'product_id' => 1, 'quantity' => 1]);
        DB::table('warranties')->insert(['id' => 1, 'sale_id' => 1, 'product_id' => 1, 'serial_number' => 'SN-1', 'status' => 'active', 'end_date' => now()->addYear()->toDateString()]);
        $controller = new \App\Http\Controllers\Api\WarrantyClaimController;
        $request = Request::create('/api/warranties/1/claim', 'POST', ['issue_date' => now()->toDateString(), 'notes' => 'Keluhan']);
        $this->assertSame(201, $controller->store($request, 1)->status());
        foreach (['duplicate', 'void'] as $case) {
            if ($case === 'void') DB::table('warranties')->where('id', 1)->update(['status' => 'void']);
            try {
                $controller->store($request, 1);
                $this->fail('Claim must be rejected');
            } catch (\Symfony\Component\HttpKernel\Exception\HttpException $e) {
                $this->assertSame(422, $e->getStatusCode());
            }
        }
        $this->assertSame(1, DB::table('rmas')->count());
        $this->assertSame(0, DB::table('stock_transactions')->count());
    }

    public function test_replacement_inspection_requires_matching_serials_and_updates_rma_sale_and_warranty(): void
    {
        DB::table('products')->insert(['id' => 1, 'title' => 'Router']);
        DB::table('sales')->insert(['id' => 1, 'status' => 'completed']);
        DB::table('deliveries')->insert(['id' => 1, 'sale_id' => 1, 'status' => 'delivered']);
        DB::table('delivery_items')->insert(['delivery_id' => 1, 'product_id' => 1, 'serial_number' => 'OLD-1', 'quantity' => 1]);
        DB::table('warranties')->insert(['sale_id' => 1, 'product_id' => 1, 'serial_number' => 'OLD-1']);
        DB::table('rmas')->insert(['id' => 1, 'sale_id' => 1, 'product_id' => 1, 'serial_number' => 'OLD-1', 'quantity' => 1, 'status' => 'pending', 'warranty_claim_flow' => 1, 'rma_code' => 'RMA-TEST']);
        $controller = new \App\Http\Controllers\Api\WarrantyClaimController;
        $request = fn ($sns) => Request::create('/api/rmas/1/inspect', 'POST', ['result' => 'replace', 'condition' => 'Rusak total', 'replacement_serial_numbers' => $sns]);
        $this->assertSame(422, $controller->inspect($request(['NEW-1', 'NEW-2']), 1)->status());
        $this->assertSame('pending', \App\Models\RMA::find(1)->status);
        $this->assertSame(0, DB::table('rma_shipments')->count());
        $this->assertSame(200, $controller->inspect($request(['NEW-1']), 1)->status());
        $this->assertSame('NEW-1', DB::table('rmas')->value('serial_number'));
        $this->assertSame('OLD-1', DB::table('rmas')->value('returned_serial_number'));
        $this->assertSame('NEW-1', DB::table('warranties')->value('serial_number'));
        $this->assertSame('NEW-1', DB::table('delivery_items')->value('serial_number'));
        $this->assertSame('pending', DB::table('rma_shipments')->value('status'));
        $this->assertSame(0, DB::table('stock_transactions')->count());
    }

    public function test_returned_items_remain_defective_until_repaired_then_become_zero_value_rma_stock(): void
    {
        DB::table('products')->insert(['id' => 1, 'title' => 'Router', 'stock' => 10]);
        $service = new \App\Services\ReturnedInventoryService;
        $service->register('rma', 1, 'RMA-1', 1, 1, ['OLD-1']);
        $service->register('rma', 1, 'RMA-1', 1, 1, ['OLD-1']);
        $this->assertSame(1, DB::table('returned_items')->count());
        $stock = new \App\Http\Controllers\Api\StockController;
        $this->assertCount(1, $stock->defectiveStock()->getData(true));
        $this->assertCount(0, $stock->rmaStock()->getData(true));
        $item = \App\Models\ReturnedItem::first();
        $controller = new \App\Http\Controllers\Api\ReturnedItemController;
        $request = fn ($condition) => Request::create('/api/returned-items/1/inspect', 'POST', ['condition' => $condition, 'notes' => 'Hasil pengujian']);
        $controller->inspect($request('unrepairable'), $item->id);
        $this->assertCount(1, $stock->defectiveStock()->getData(true));
        $this->assertCount(0, $stock->rmaStock()->getData(true));
        $controller->inspect($request('limited'), $item->id);
        $this->assertCount(0, $stock->defectiveStock()->getData(true));
        $rows = $stock->rmaStock()->getData(true);
        $this->assertCount(1, $rows);
        $this->assertSame('0.00', $rows[0]['book_value']);
        $this->assertSame(1, $rows[0]['total_quantity']);
        $this->assertEquals(10, DB::table('products')->value('stock'));
        $this->assertSame(0, DB::table('stock_transactions')->count());
    }

    public function test_project_addition_allocates_regular_stock_and_updates_total_and_history(): void
    {
        DB::table('products')->insert(['id' => 1, 'title' => 'Router']);
        DB::table('warehouses')->insert(['id' => 1, 'is_default' => 1]);
        DB::table('project_investments')->insert(['id' => 1, 'status' => 'active', 'warehouse_id' => 2, 'project_code' => 'PRJ-1']);
        $transfer = \Mockery::mock(\App\Services\StockTransferService::class);
        $transfer->shouldReceive('transferNow')->once()->with(1, 2, 1, 2, \Mockery::type('string'), null)->andReturn(new \App\Models\StockTransfer);
        $this->app->instance(\App\Services\StockTransferService::class, $transfer);
        $controller = new \App\Http\Controllers\Api\ProjectItemController;
        $response = $controller->store(Request::create('/api/project-investments/1/items', 'POST', ['product_id' => 1, 'quantity' => 2, 'unit_price' => 100, 'serial_numbers' => ['SN-1', 'SN-2']]), 1);
        $this->assertSame(201, $response->status(), $response->getContent());
        $this->assertEquals(200, DB::table('project_investments')->value('total_value'));
        $this->assertSame(['SN-1', 'SN-2'], $response->getData(true)['serial_numbers']);
        $this->assertSame(1, DB::table('project_item_changes')->count());
        $detail = $controller->show(1)->getData(true);
        $this->assertCount(1, $detail['project']['items']);
        $this->assertCount(1, $detail['changes']);
    }

    public function test_recovered_project_allocation_has_no_value_and_cannot_be_used_twice(): void
    {
        DB::table('products')->insert(['id' => 1, 'title' => 'Router', 'stock' => 10]);
        DB::table('project_investments')->insert(['id' => 1, 'status' => 'active', 'project_code' => 'PRJ-1']);
        $returned = \App\Models\ReturnedItem::create(['source_key' => 'rma-1-0', 'product_id' => 1, 'serial_number' => 'REC-1', 'condition' => 'working', 'quantity' => 1, 'available_quantity' => 1]);
        $controller = new \App\Http\Controllers\Api\ProjectItemController;
        $request = Request::create('/api/project-investments/1/items', 'POST', ['product_id' => 1, 'quantity' => 1, 'unit_price' => 999, 'returned_item_id' => $returned->id]);
        $response = $controller->store($request, 1);
        $this->assertSame(201, $response->status(), $response->getContent());
        $this->assertEquals(0, $response->getData()->total_price);
        $this->assertSame(['REC-1'], $response->getData(true)['serial_numbers']);
        $this->assertEquals(0, DB::table('project_investments')->value('total_value'));
        $this->assertSame(422, $controller->store($request, 1)->status());
        $this->assertSame(1, DB::table('project_investment_items')->count());
        $this->assertEquals(10, DB::table('products')->value('stock'));
    }

    public function test_project_replacement_tracks_old_unit_as_defective_and_updates_only_its_serial(): void
    {
        DB::table('products')->insert(['id' => 1, 'title' => 'Router']);
        DB::table('warehouses')->insert(['id' => 1, 'is_default' => 1]);
        DB::table('project_investments')->insert(['id' => 1, 'status' => 'active', 'warehouse_id' => 2, 'project_code' => 'PRJ-1']);
        $item = \App\Models\ProjectInvestmentItem::create(['project_investment_id' => 1, 'product_id' => 1, 'quantity' => 2, 'stock_deducted' => true, 'serial_numbers' => ['OLD-1', 'KEEP-1']]);
        $stock = \Mockery::mock(\App\Services\StockSyncService::class);
        $stock->shouldReceive('deductStock')->once()->with(1, 1, 'project_defective_return', 1, 'Rusak', '2')->andReturn(true);
        $this->app->instance(\App\Services\StockSyncService::class, $stock);
        $transfer = \Mockery::mock(\App\Services\StockTransferService::class);
        $transfer->shouldReceive('transferNow')->once()->andReturn(new \App\Models\StockTransfer);
        $this->app->instance(\App\Services\StockTransferService::class, $transfer);
        $controller = new \App\Http\Controllers\Api\ProjectItemController;
        $request = Request::create('/api/project-investments/1/items/1/replace', 'POST', ['old_serial_numbers' => ['OLD-1'], 'new_serial_numbers' => ['NEW-1'], 'notes' => 'Rusak']);
        $response = $controller->replace($request, 1, $item->id);
        $this->assertSame(200, $response->status(), $response->getContent());
        $this->assertSame(['NEW-1', 'KEEP-1'], $item->fresh()->serial_numbers);
        $this->assertSame('OLD-1', DB::table('returned_items')->value('serial_number'));
        $this->assertSame(422, $controller->replace($request, 1, $item->id)->status());
        $this->assertSame(1, DB::table('returned_items')->count());
    }

    public function test_replacement_correction_updates_the_actual_warranty_and_deducts_stock_once(): void
    {
        DB::table('products')->insert(['id' => 1, 'title' => 'Router']);
        DB::table('sales')->insert(['id' => 1, 'status' => 'completed']);
        DB::table('deliveries')->insert(['id' => 1, 'sale_id' => 1]);
        DB::table('delivery_items')->insert(['delivery_id' => 1, 'product_id' => 1, 'serial_number' => 'OLD-5']);
        DB::table('warranties')->insert([
            ['id' => 1, 'sale_id' => 1, 'product_id' => 1, 'serial_number' => 'OTHER-1'],
            ['id' => 5, 'sale_id' => 1, 'product_id' => 1, 'serial_number' => 'OLD-5'],
        ]);
        DB::table('rmas')->insert(['id' => 1, 'sale_id' => 1, 'warranty_id' => 1, 'product_id' => 1, 'quantity' => 1, 'serial_number' => 'OLD-5', 'status' => 'completed', 'resolution' => 'repair', 'inspected_at' => now()]);
        DB::table('rma_shipments')->insert(['rma_id' => 1, 'product_id' => 1, 'quantity' => 1, 'serial_numbers' => '["OLD-5"]', 'status' => 'delivered']);
        $stock = \Mockery::mock(\App\Services\StockSyncService::class);
        $stock->shouldReceive('deductStock')->once()->with(1, 1, 'rma_shipment', 1, \Mockery::type('string'))->andReturn(true);
        $this->app->instance(\App\Services\StockSyncService::class, $stock);
        $service = new \App\Services\RMAReplacementCorrectionService;
        $service->correct(1, ['NEW-151']);
        $service->correct(1, ['NEW-151']);
        $this->assertSame('NEW-151', DB::table('warranties')->where('id', 5)->value('serial_number'));
        $this->assertSame('OTHER-1', DB::table('warranties')->where('id', 1)->value('serial_number'));
        $this->assertEquals(5, DB::table('rmas')->value('warranty_id'));
        $this->assertSame('OLD-5', DB::table('returned_items')->value('serial_number'));
        $this->assertSame('NEW-151', DB::table('delivery_items')->value('serial_number'));
        $this->assertSame(1, DB::table('returned_items')->count());
        try {
            $service->correct(1, ['OLD-5']);
            $this->fail('SN retur cannot be reused as a replacement');
        } catch (\RuntimeException $e) {
            $this->assertSame('NEW-151', DB::table('rmas')->value('serial_number'));
        }
    }

    public function test_msa_replacement_updates_project_serials_and_registers_old_units_without_duplicate_stock(): void
    {
        DB::table('products')->insert(['id' => 1, 'title' => 'Router']);
        DB::table('project_investments')->insert(['id' => 1, 'status' => 'active']);
        $item = \App\Models\ProjectInvestmentItem::create(['project_investment_id' => 1, 'product_id' => 1, 'quantity' => 1, 'serial_numbers' => ['MSA-OLD']]);
        DB::table('msa_projects')->insert(['id' => 1, 'project_investment_id' => 1, 'product_id' => 1, 'quantity' => 1, 'msa_code' => 'MSA-1', 'status' => 'in_repair']);
        $stock = \Mockery::mock(\App\Services\StockSyncService::class);
        $stock->shouldReceive('deductStock')->once()->with(1, 1, 'msa_replacement', 1, \Mockery::type('string'))->andReturn(true);
        $controller = new \App\Http\Controllers\Api\MSAProjectController($stock);
        $request = Request::create('/api/msa-projects/1/replace', 'POST', ['condition' => 'Rusak', 'serial_numbers' => ['MSA-OLD'], 'replacement_serial_numbers' => ['MSA-NEW']]);
        $response = $controller->replaceItem($request, 1);
        $this->assertSame(200, $response->status(), $response->getContent());
        $this->assertSame(['MSA-NEW'], $item->fresh()->serial_numbers);
        $this->assertSame('MSA-OLD', DB::table('returned_items')->value('serial_number'));
        $this->assertSame(422, $controller->replaceItem($request, 1)->status());
        $this->assertSame(1, DB::table('project_item_changes')->count());
    }

    public function test_sale_edit_saves_form_fields_items_totals_and_adjusts_only_stock_difference(): void
    {
        DB::table('products')->insert(['id' => 1, 'title' => 'Router']);
        DB::table('warehouses')->insert(['id' => 1, 'is_default' => 1]);
        DB::table('sales')->insert(['id' => 1, 'invoice_number' => 'INV-EDIT', 'status' => 'pending', 'customer_name' => 'Lama', 'warehouse_id' => 1, 'grand_total' => 200]);
        DB::table('sale_items')->insert(['id' => 1, 'sale_id' => 1, 'product_id' => 1, 'quantity' => 2, 'unit_price' => 100, 'subtotal' => 200]);
        DB::table('payments')->insert([
            ['payable_type' => \App\Models\Sale::class, 'payable_id' => 1, 'status' => 'paid', 'amount' => 50],
            ['payable_type' => \App\Models\Sale::class, 'payable_id' => 1, 'status' => 'unpaid', 'amount' => 150],
        ]);
        DB::table('invoices')->insert(['sale_id' => 1]);
        DB::table('deliveries')->insert(['sale_id' => 1, 'status' => 'preparing']);
        $stock = \Mockery::mock(\App\Services\StockSyncService::class);
        $stock->shouldReceive('deductStock')->once()->with(1, 1, 'sale', 1, \Mockery::type('string'), '1')->andReturn(true);
        $stock->shouldNotReceive('addStock');
        $controller = new \App\Http\Controllers\Api\SaleController($stock);
        $request = Request::create('/api/sales/1', 'PUT', [
            'status' => 'completed', 'invoice_number' => 'INV-EDIT', 'customer_name' => 'Pelanggan Baru', 'customer_phone' => '0812345',
            'po_number' => 'PO-REF-15',
            'sale_date' => '2026-10-15', 'due_date' => '2026-11-15', 'memo' => 'Memo edit', 'notes' => 'Pesan edit', 'discount_amount' => 10,
            'delivery_info' => ['courier' => 'JNE', 'tracking_number' => 'SJ-EDIT'],
            'items' => [['id' => 1, 'product_id' => 1, 'quantity' => 3, 'unit_price' => 120, 'description' => 'Deskripsi baru', 'unit' => 'unit', 'discount' => 0]],
        ]);
        foreach ([1, 2] as $save) {
            $response = $controller->update($request, 1);
            $this->assertSame(200, $response->status(), $response->getContent());
        }
        $this->assertSame('Pelanggan Baru', DB::table('sales')->value('customer_name'));
        $this->assertSame('PO-REF-15', DB::table('sales')->value('po_number'));
        $print = $controller->printInvoice(1);
        $this->assertStringContainsString('PO-REF-15', $print->getContent());
        $this->assertSame('2026-11-15', DB::table('sales')->value('due_date'));
        $this->assertSame('Memo edit', DB::table('sales')->value('memo'));
        $this->assertSame('SJ-EDIT', DB::table('deliveries')->value('tracking_number'));
        $this->assertSame('Deskripsi baru', DB::table('sale_items')->value('description'));
        $this->assertSame(1, DB::table('sale_items')->count());
        $this->assertEquals(350, DB::table('sales')->value('grand_total'));
        $this->assertEquals(300, DB::table('payments')->where('status', 'unpaid')->value('amount'));
        $this->assertEquals(50, DB::table('payments')->where('status', 'paid')->value('amount'));
        $this->assertEquals(350, DB::table('invoices')->value('total'));
    }

    public function test_sale_item_changes_preserve_issued_serials_but_allow_price_edits(): void
    {
        DB::table('products')->insert(['id' => 1, 'title' => 'Router']);
        DB::table('sales')->insert(['id' => 1, 'status' => 'completed', 'grand_total' => 100]);
        DB::table('sale_items')->insert(['id' => 1, 'sale_id' => 1, 'product_id' => 1, 'quantity' => 1, 'unit_price' => 100, 'subtotal' => 100]);
        DB::table('warranties')->insert(['sale_id' => 1, 'product_id' => 1, 'serial_number' => 'ISSUED-1']);
        $stock = \Mockery::mock(\App\Services\StockSyncService::class);
        $stock->shouldNotReceive('deductStock');
        $stock->shouldNotReceive('addStock');
        $controller = new \App\Http\Controllers\Api\SaleController($stock);
        $request = fn ($quantity) => Request::create('/api/sales/1', 'PUT', ['status' => 'completed', 'items' => [['id' => 1, 'product_id' => 1, 'quantity' => $quantity, 'unit_price' => 120]]]);
        $this->assertSame(422, $controller->update($request(2), 1)->status());
        $this->assertSame(200, $controller->update($request(1), 1)->status());
        $this->assertEquals(120, DB::table('sales')->value('grand_total'));
        $this->assertSame('ISSUED-1', DB::table('warranties')->value('serial_number'));
    }

    public function test_edit_below_paid_amount_rolls_back_all_sale_changes(): void
    {
        DB::table('products')->insert(['id' => 1, 'title' => 'Router']);
        DB::table('sales')->insert(['id' => 1, 'status' => 'completed', 'customer_name' => 'Lama', 'grand_total' => 200]);
        DB::table('sale_items')->insert(['id' => 1, 'sale_id' => 1, 'product_id' => 1, 'quantity' => 2, 'unit_price' => 100, 'subtotal' => 200]);
        DB::table('payments')->insert(['payable_type' => \App\Models\Sale::class, 'payable_id' => 1, 'status' => 'paid', 'amount' => 200]);
        $controller = new \App\Http\Controllers\Api\SaleController(\Mockery::mock(\App\Services\StockSyncService::class));
        $response = $controller->update(Request::create('/api/sales/1', 'PUT', ['status' => 'completed', 'customer_name' => 'Baru', 'items' => [['id' => 1, 'product_id' => 1, 'quantity' => 2, 'unit_price' => 50]]]), 1);
        $this->assertSame(422, $response->status());
        $this->assertSame('Lama', DB::table('sales')->value('customer_name'));
        $this->assertEquals(100, DB::table('sale_items')->value('unit_price'));
        $this->assertEquals(200, DB::table('sales')->value('grand_total'));
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
