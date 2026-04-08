<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private function foreignKeyExists(string $table, string $keyName): bool
    {
        $result = DB::select("
            SELECT COUNT(*) as cnt FROM information_schema.TABLE_CONSTRAINTS
            WHERE TABLE_SCHEMA = DATABASE()
            AND TABLE_NAME = ?
            AND CONSTRAINT_NAME = ?
            AND CONSTRAINT_TYPE = 'FOREIGN KEY'
        ", [$table, $keyName]);

        return $result[0]->cnt > 0;
    }

    private function indexExists(string $table, string $keyName): bool
    {
        $result = DB::select("
            SELECT COUNT(*) as cnt FROM information_schema.STATISTICS
            WHERE TABLE_SCHEMA = DATABASE()
            AND TABLE_NAME = ?
            AND INDEX_NAME = ?
        ", [$table, $keyName]);

        return $result[0]->cnt > 0;
    }

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Step 1: Drop the foreign key on product_id if it exists
        if ($this->foreignKeyExists('current_stocks', 'current_stocks_product_id_foreign')) {
            Schema::table('current_stocks', function (Blueprint $table) {
                $table->dropForeign(['product_id']);
            });
        }

        // Step 2: Drop the single-column unique index if it exists
        if ($this->indexExists('current_stocks', 'current_stocks_product_id_unique')) {
            Schema::table('current_stocks', function (Blueprint $table) {
                $table->dropUnique(['product_id']);
            });
        }

        // Step 3: Add composite unique constraint if not already present
        if (!$this->indexExists('current_stocks', 'current_stocks_product_warehouse_unique')) {
            Schema::table('current_stocks', function (Blueprint $table) {
                $table->unique(['product_id', 'warehouse_id'], 'current_stocks_product_warehouse_unique');
            });
        }

        // Step 4: Re-add the foreign key constraint if not present
        if (!$this->foreignKeyExists('current_stocks', 'current_stocks_product_id_foreign')) {
            Schema::table('current_stocks', function (Blueprint $table) {
                $table->foreign('product_id')->references('id')->on('products')->onDelete('cascade');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove duplicate product_id rows before restoring single-column unique constraint
        DB::statement('
            DELETE cs1 FROM current_stocks cs1
            INNER JOIN current_stocks cs2
            WHERE cs1.product_id = cs2.product_id AND cs1.id < cs2.id
        ');

        // Drop foreign key if it exists
        if ($this->foreignKeyExists('current_stocks', 'current_stocks_product_id_foreign')) {
            Schema::table('current_stocks', function (Blueprint $table) {
                $table->dropForeign(['product_id']);
            });
        }

        // Drop composite unique if it exists
        if ($this->indexExists('current_stocks', 'current_stocks_product_warehouse_unique')) {
            Schema::table('current_stocks', function (Blueprint $table) {
                $table->dropUnique('current_stocks_product_warehouse_unique');
            });
        }

        // Restore old unique on product_id only if not already present
        if (!$this->indexExists('current_stocks', 'current_stocks_product_id_unique')) {
            Schema::table('current_stocks', function (Blueprint $table) {
                $table->unique('product_id');
            });
        }

        // Re-add foreign key if not present
        if (!$this->foreignKeyExists('current_stocks', 'current_stocks_product_id_foreign')) {
            Schema::table('current_stocks', function (Blueprint $table) {
                $table->foreign('product_id')->references('id')->on('products')->onDelete('cascade');
            });
        }
    }
};
