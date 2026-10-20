<?php

namespace Tests\Feature;

use App\Models\{BankAccount, Expense, User};
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\{DB, Schema, Storage};
use Tests\TestCase;

class ExpensesTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:']);
        DB::purge('sqlite');
        Storage::fake('local');
        Schema::create('users', function (Blueprint $table) { $table->id(); $table->string('name'); $table->string('role'); $table->timestamps(); });
        Schema::create('bank_accounts', function (Blueprint $table) {
            $table->id(); $table->string('bank_name'); $table->string('account_name')->nullable(); $table->string('account_number');
            $table->string('currency')->default('IDR'); $table->boolean('is_active')->default(true); $table->boolean('is_default')->default(false);
            $table->decimal('initial_balance', 15, 2)->default(1000); $table->timestamps();
        });
        Schema::create('payments', function (Blueprint $table) {
            $table->id(); $table->morphs('payable'); $table->string('payment_type'); $table->decimal('amount', 15, 2);
            $table->decimal('tax_amount', 15, 2)->default(0); $table->decimal('tax_rate', 5, 2)->nullable(); $table->string('tax_type')->nullable();
            $table->date('payment_date')->nullable(); $table->string('method')->nullable(); $table->string('status');
            $table->string('reference_number')->nullable(); $table->text('notes')->nullable(); $table->unsignedBigInteger('bank_account_id')->nullable();
            $table->unsignedBigInteger('user_id')->nullable(); $table->timestamps();
        });
        (require database_path('migrations/2026_10_20_000001_create_expenses_tables.php'))->up();
        DB::table('users')->insert(['id' => 1, 'name' => 'Expense Tester', 'role' => 'staff']);
        DB::table('bank_accounts')->insert(['id' => 1, 'bank_name' => 'Test Bank', 'account_number' => '123', 'initial_balance' => 1000]);
        $this->actingAs(User::find(1), 'sanctum');
    }

    private function payload(array $changes = []): array
    {
        return array_replace([
            'expense_date' => '2026-10-20', 'due_date' => '2026-10-30', 'vendor_name' => 'Vendor Test', 'reference' => 'REF-EXP',
            'status' => 'unpaid', 'notes' => 'Test expense',
            'items' => [
                ['expense_category_id' => 1, 'description' => 'Internet', 'amount' => 100, 'tax_rate' => 11],
                ['expense_category_id' => 2, 'description' => 'Transport', 'amount' => 50, 'tax_rate' => 0],
            ],
        ], $changes);
    }

    public function test_totals_are_calculated_server_side_and_categories_can_be_managed(): void
    {
        $this->getJson('/api/expense-categories')->assertOk()->assertJsonCount(8);
        $this->postJson('/api/expense-categories', ['name' => 'Custom Category'])->assertCreated();
        $this->postJson('/api/expense-categories', ['name' => 'Custom Category'])->assertUnprocessable();
        $response = $this->postJson('/api/expenses', $this->payload(['total_amount' => 1]));
        $response->assertCreated()->assertJsonPath('total_amount', '161.00')->assertJsonPath('tax_amount', '11.00')
            ->assertJsonPath('subtotal', '150.00')->assertJsonCount(2, 'items')->assertJsonPath('payment.status', 'unpaid');
        $this->assertSame(1, DB::table('payments')->count());
        $this->assertEquals(1000, BankAccount::find(1)->current_balance);
        $this->getJson('/api/expenses/'.$response->json('id'))->assertOk()->assertJsonPath('items.0.category.id', 1);
    }

    public function test_paid_expenses_change_bank_balance_once_and_cancellation_reverses_them(): void
    {
        $response = $this->postJson('/api/expenses', $this->payload(['status' => 'paid', 'bank_account_id' => 1,
            'payment_date' => '2026-10-20', 'method' => 'transfer']));
        $response->assertCreated()->assertJsonPath('payment.status', 'paid');
        $id = $response->json('id');
        $this->assertEquals(839, BankAccount::find(1)->current_balance);
        $this->getJson('/api/bank-accounts/summary')->assertOk()->assertJsonPath('totals.total_outgoing', 161)
            ->assertJsonPath('totals.total_balance', 839);
        $this->postJson("/api/expenses/$id/pay", ['bank_account_id' => 1, 'payment_date' => '2026-10-20', 'method' => 'cash'])->assertUnprocessable();
        $this->putJson("/api/expenses/$id", $this->payload())->assertUnprocessable();
        $this->deleteJson("/api/expenses/$id")->assertUnprocessable();
        $this->postJson("/api/expenses/$id/cancel")->assertOk()->assertJsonPath('status', 'cancelled');
        $this->postJson("/api/expenses/$id/cancel")->assertOk();
        $this->assertEquals(1000, BankAccount::find(1)->current_balance);
        $this->assertSame(1, DB::table('payments')->count());
    }

    public function test_edit_pay_and_delete_keep_payment_records_consistent(): void
    {
        $id = $this->postJson('/api/expenses', $this->payload())->assertCreated()->json('id');
        $paymentId = Expense::find($id)->payment->id;
        $this->putJson("/api/expenses/$id", $this->payload(['items' => [['expense_category_id' => 1, 'amount' => 200, 'tax_rate' => 0]]]))
            ->assertOk()->assertJsonPath('total_amount', '200.00');
        $this->assertEquals(200, Expense::find($id)->payment->amount);
        $this->postJson("/api/expenses/$id/pay", ['bank_account_id' => 1, 'payment_date' => '2026-10-21', 'method' => 'cash'])
            ->assertOk()->assertJsonPath('status', 'paid');
        $this->assertSame($paymentId, Expense::find($id)->payment->id);
        $this->assertEquals(800, BankAccount::find(1)->current_balance);
        $this->putJson('/api/payments/'.$paymentId, ['amount' => 1])->assertUnprocessable();
        $this->deleteJson('/api/payments/'.$paymentId)->assertUnprocessable();
        $this->getJson('/api/payments?payable_type=expense')->assertOk()->assertJsonPath('data.0.payable_type_label', 'expense')
            ->assertJsonPath('data.0.payable_party', 'Vendor Test');
        $draft = $this->postJson('/api/expenses', $this->payload())->assertCreated()->json('id');
        $this->deleteJson("/api/expenses/$draft")->assertOk();
        $this->assertSame(1, DB::table('payments')->count());
        $this->assertSame(1, DB::table('expense_items')->count());
    }

    public function test_filters_and_summaries_cover_all_matching_pages(): void
    {
        $this->postJson('/api/expenses', $this->payload())->assertCreated();
        $this->postJson('/api/expenses', $this->payload())->assertCreated();
        $this->postJson('/api/expenses', $this->payload(['expense_date' => '2026-09-01', 'reference' => 'OTHER']))->assertCreated();
        $this->getJson('/api/expenses?search=REF-EXP&start_date=2026-10-01&end_date=2026-10-31&per_page=1&category_id=1')
            ->assertOk()->assertJsonPath('total', 2)->assertJsonCount(1, 'data')->assertJsonPath('summary.unpaid', 322);
        $this->getJson('/api/expenses?status=paid')->assertOk()->assertJsonPath('total', 0);
        $this->getJson('/api/expenses?end_date=2026-09-30')->assertOk()->assertJsonPath('total', 1);
    }

    public function test_receipts_are_private_and_removed_with_deleted_drafts(): void
    {
        $response = $this->post('/api/expenses', $this->payload(['attachments' => [UploadedFile::fake()->create('receipt.pdf', 2, 'application/pdf')]]), ['Accept' => 'application/json']);
        $response->assertCreated()->assertJsonPath('files.0.name', 'receipt.pdf')->assertJsonMissingPath('files.0.path')->assertJsonMissingPath('attachments');
        $id = $response->json('id'); $expense = Expense::find($id); $file = $expense->attachments[0];
        Storage::disk('local')->assertExists($file['path']);
        $this->get("/api/expenses/$id/attachments/{$file['id']}")->assertOk()->assertDownload('receipt.pdf');
        $this->post("/api/expenses/$id", $this->payload(['_method' => 'PUT', 'attachments' => [UploadedFile::fake()->create('extra.pdf', 2, 'application/pdf')]]), ['Accept' => 'application/json'])
            ->assertOk()->assertJsonCount(2, 'files')->assertJsonPath('files.0.name', 'receipt.pdf');
        $paths = collect(Expense::find($id)->attachments)->pluck('path')->all();
        $other = $this->postJson('/api/expenses', $this->payload())->assertCreated()->json('id');
        $this->getJson("/api/expenses/$other/attachments/{$file['id']}")->assertNotFound();
        $this->deleteJson("/api/expenses/$id")->assertOk();
        Storage::disk('local')->assertMissing($file['path']);
        foreach ($paths as $path) Storage::disk('local')->assertMissing($path);
    }

    public function test_invalid_values_inactive_accounts_and_viewer_writes_are_rejected(): void
    {
        $this->postJson('/api/expenses', $this->payload(['items' => [['expense_category_id' => 1, 'amount' => -1, 'tax_rate' => 0]]]))->assertUnprocessable();
        $this->postJson('/api/expenses', $this->payload(['items' => [['expense_category_id' => 1, 'amount' => 1, 'tax_rate' => 0, 'expense_id' => 999]]]))->assertUnprocessable();
        DB::table('bank_accounts')->where('id', 1)->update(['is_active' => false]);
        $this->postJson('/api/expenses', $this->payload(['status' => 'paid', 'bank_account_id' => 1, 'payment_date' => '2026-10-20', 'method' => 'cash']))->assertUnprocessable();
        $this->assertSame(0, DB::table('expenses')->count());
        $this->actingAs(new User(['role' => 'viewer']), 'sanctum');
        $this->getJson('/api/expenses')->assertOk();
        $this->postJson('/api/expenses', $this->payload())->assertForbidden();
        $this->postJson('/api/expense-categories', ['name' => 'Denied'])->assertForbidden();
    }
}
