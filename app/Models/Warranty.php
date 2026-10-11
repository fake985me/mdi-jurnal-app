<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

class Warranty extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'warranty_code',
        'serial_number',
        'product_id',
        'sale_id',
        'user_id',
        'warranty_period_months',
        'start_date',
        'end_date',
        'status',
        'notes'
    ];

    protected $casts = [
        'warranty_period_months' => 'integer',
        'start_date' => 'date',
        'end_date' => 'date',
    ];

    /**
     * Get the product
     */
    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Get the sale
     */
    public function sale()
    {
        return $this->belongsTo(Sale::class);
    }

    /**
     * Get all RMAs created under this warranty
     */
    public function rmas()
    {
        return $this->hasMany(RMA::class);
    }

    public function isActiveOn(Carbon|string|null $purchaseDate = null): bool
    {
        if ($this->status === 'void') {
            return false;
        }

        $months = $this->warranty_period_months
            ?? $this->product?->warranty_period_months;
        $startDate = $purchaseDate ?? $this->start_date;

        if ($months !== null && $startDate) {
            $coverageEndDate = $this->coverageEndDate($purchaseDate);
            return (int) $months > 0
                && $coverageEndDate?->toDateString() >= today()->toDateString();
        }

        return !$this->end_date || $this->end_date->toDateString() >= today()->toDateString();
    }

    public function coverageEndDate(Carbon|string|null $purchaseDate = null): ?Carbon
    {
        $months = $this->warranty_period_months
            ?? $this->product?->warranty_period_months;
        $startDate = $purchaseDate ?? $this->start_date;

        if ($months === null || !$startDate || (int) $months <= 0) {
            return $this->end_date;
        }

        return Carbon::parse($startDate)->addMonthsNoOverflow((int) $months);
    }
}
