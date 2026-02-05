<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('project_investments', function (Blueprint $table) {
            // PO (Purchase Order) fields
            $table->string('po_number')->nullable()->after('project_code');
            $table->decimal('po_value', 15, 2)->default(0)->after('po_number');
            $table->date('po_date')->nullable()->after('po_value');
            
            // Progress tracking fields
            $table->integer('delivery_progress')->default(0)->after('status'); // 0-100
            $table->integer('installation_progress')->default(0)->after('delivery_progress'); // 0-100
            
            // Timeline fields
            $table->integer('duration_days')->nullable()->after('expected_end_date');
            $table->text('scope_of_work')->nullable()->after('description');
            
            // Additional info
            $table->string('project_location')->nullable()->after('client_contact');
            $table->string('pic_name')->nullable()->after('project_location'); // Person in charge
            $table->string('pic_phone')->nullable()->after('pic_name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('project_investments', function (Blueprint $table) {
            $table->dropColumn([
                'po_number', 'po_value', 'po_date',
                'delivery_progress', 'installation_progress',
                'duration_days', 'scope_of_work',
                'project_location', 'pic_name', 'pic_phone'
            ]);
        });
    }
};
