<?php

namespace App\Services;

use App\Models\Asset;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

class AssetDepreciationService
{
    public function post(Asset $asset, string $asOf, ?int $userId = null): int
    {
        return DB::transaction(function () use ($asset, $asOf, $userId) {
            $asset = Asset::lockForUpdate()->findOrFail($asset->id);
            abort_unless($asset->status !== 'disposed' && $asset->depreciation_method === 'straight_line' && $asset->depreciation_start_date, 422, 'Asset cannot be depreciated.');
            $start = CarbonImmutable::parse($asset->depreciation_start_date)->startOfMonth();
            $cutoff = CarbonImmutable::parse($asOf)->startOfDay();
            $cost = (int) round((float) $asset->purchase_price * 100);
            $residual = (int) round((float) $asset->residual_value * 100);
            $life = (int) $asset->useful_life_months;
            abort_unless($life > 0 && $residual <= $cost, 422, 'Invalid depreciation settings.');
            $existing = $asset->depreciations()->get()->keyBy(fn ($row) => $row->period->toDateString());
            $count = 0;
            for ($month = 1; $month <= $life; $month++) {
                $period = $start->addMonths($month - 1)->endOfMonth()->startOfDay();
                if ($period->greaterThan($cutoff)) break;
                if ($existing->has($period->toDateString())) continue;
                // Cumulative rounding ensures the final balance equals residual value.
                $previous = (int) round(($cost - $residual) * ($month - 1) / $life);
                $cumulative = (int) round(($cost - $residual) * $month / $life);
                $asset->depreciations()->create([
                    'period' => $period->toDateString(), 'amount' => ($cumulative - $previous) / 100,
                    'book_value_after' => ($cost - $cumulative) / 100, 'user_id' => $userId,
                ]);
                $count++;
            }
            $accumulated = round((float) $asset->depreciations()->sum('amount'), 2);
            $asset->update(['accumulated_depreciation' => $accumulated,
                'current_value' => max($residual / 100, round($cost / 100 - $accumulated, 2))]);
            return $count;
        });
    }
}
