<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Supplier extends Model
{
    use HasFactory;

    protected $fillable = [
        'supplier_code',
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
        'bank_name',
        'bank_account_number',
        'bank_account_name',
        'is_active',
        'notes',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Get all purchases for this supplier
     */
    public function purchases()
    {
        return $this->hasMany(Purchase::class);
    }

    /**
     * Generate a unique supplier code
     */
    public static function generateCode(): string
    {
        $prefix = 'SUP';
        $date = now()->format('Ymd');
        $last = static::where('supplier_code', 'like', "{$prefix}-{$date}-%")
            ->orderBy('supplier_code', 'desc')
            ->first();

        $next = $last ? ((int) substr($last->supplier_code, -4)) + 1 : 1;

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
     * Get total purchases amount
     */
    public function getTotalPurchasesAttribute(): float
    {
        return (float) $this->purchases()->sum('total_amount');
    }

    /**
     * Scope for active suppliers
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
