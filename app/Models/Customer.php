<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Customer extends Model
{
    use HasFactory;

    protected $fillable = [
        'customer_code',
        'name',
        'company',
        'email',
        'phone',
        'phone_alt',
        'address',
        'city',
        'province',
        'postal_code',
        'npwp',
        'type',
        'is_active',
        'notes',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Get all sales for this customer
     */
    public function sales()
    {
        return $this->hasMany(Sale::class);
    }

    /**
     * Generate a unique customer code
     */
    public static function generateCode(): string
    {
        $prefix = 'CUST';
        $date = now()->format('Ymd');
        $last = static::where('customer_code', 'like', "{$prefix}-{$date}-%")
            ->orderBy('customer_code', 'desc')
            ->first();

        $next = $last ? ((int) substr($last->customer_code, -4)) + 1 : 1;

        return "{$prefix}-{$date}-" . str_pad($next, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Get display name (company name or personal name)
     */
    public function getDisplayNameAttribute(): string
    {
        if ($this->company) {
            return "{$this->company} ({$this->name})";
        }
        return $this->name;
    }

    /**
     * Get total sales amount
     */
    public function getTotalSalesAttribute(): float
    {
        return (float) $this->sales()->sum('grand_total');
    }

    /**
     * Scope for active customers
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
