<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\RMAReplacementCorrectionService;

class CorrectRMAReplacement extends Command
{
    protected $signature = 'rma:correct-replacement {rma} {serials*} {--user= : User responsible for the correction (defaults to the RMA creator)}';
    protected $description = 'Correct replacement serials and reconcile a replacement incorrectly recorded as repair';
    public function handle(): int
    {
        $creator = \App\Models\RMA::findOrFail($this->argument('rma'))->user_id;
        if (!auth()->onceUsingId($this->option('user') ?: $creator)) {
            $this->error('A valid user is required for stock transaction audit.');
            return self::FAILURE;
        }
        $rma = app(RMAReplacementCorrectionService::class)->correct((int) $this->argument('rma'), $this->argument('serials'));
        $this->info($rma->rma_code.' -> '.$rma->serial_number);
        return self::SUCCESS;
    }
}
