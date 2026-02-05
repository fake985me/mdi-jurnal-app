<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class FixRmaConditionColumn extends Command
{
    protected $signature = 'fix:rma-condition';
    protected $description = 'Fix RMA condition column type from ENUM to VARCHAR';

    public function handle()
    {
        try {
            $this->info('Altering condition column to VARCHAR(255)...');
            
            DB::statement('ALTER TABLE rmas MODIFY COLUMN `condition` VARCHAR(255) NULL');
            
            $this->info('✅ Success! Condition column is now VARCHAR(255)');
            $this->info('Custom conditions can now be saved.');
            
            return 0;
        } catch (\Exception $e) {
            $this->error('Failed to alter column: ' . $e->getMessage());
            return 1;
        }
    }
}
