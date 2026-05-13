<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BankAccount extends Model
{
    use HasFactory;

    protected $fillable = [
        'bank_name',
        'account_name',
        'account_number',
        'branch',
        'currency',
        'initial_balance',
        'is_active',
        'is_default',
        'notes',
    ];

    protected $casts = [
        'initial_balance' => 'decimal:2',
        'is_active' => 'boolean',
        'is_default' => 'boolean',
    ];

    /**
     * Get all payments linked to this bank account
     */
    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    /**
     * Get the default bank account
     */
    public static function getDefault()
    {
        return static::where('is_default', true)->where('is_active', true)->first();
    }

    /**
     * Set this account as default (unset others)
     */
    public function setAsDefault()
    {
        static::where('is_default', true)->update(['is_default' => false]);
        $this->update(['is_default' => true]);
    }

    /**
     * Calculate the current balance based on initial balance and payment movements
     */
    public function getCurrentBalanceAttribute(): float
    {
        $incoming = $this->payments()
            ->where('status', 'paid')
            ->whereIn('payable_type', [
                Sale::class,
                ProjectContract::class,
                MsaContract::class,
            ])
            ->sum('amount');

        $outgoing = $this->payments()
            ->where('status', 'paid')
            ->where('payable_type', Purchase::class)
            ->sum('amount');

        return (float) $this->initial_balance + $incoming - $outgoing;
    }

    /**
     * Scope for active accounts only
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
