<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StockAdjustment extends Model
{
    use HasFactory;

    protected $fillable = [
        'adjustment_code',
        'product_id',
        'adjustment_type',
        'reason',
        'quantity',
        'before_qty',
        'after_qty',
        'related_type',
        'related_id',
        'notes',
        'user_id',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'before_qty' => 'integer',
        'after_qty' => 'integer',
    ];

    /**
     * Available adjustment reasons
     */
    public const REASONS = [
        'damaged' => 'Damaged',
        'expired' => 'Expired',
        'lost' => 'Lost',
        'found' => 'Found',
        'correction' => 'Correction',
        'audit' => 'Stock Opname/Audit',
        'theft' => 'Theft',
        'donation' => 'Donation',
        'return_from_lending' => 'Return from Lending',
        'warranty_replacement' => 'Warranty Replacement',
        'other' => 'Other',
    ];

    /**
     * Get the product
     */
    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Get the user who created the adjustment
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the related entity (polymorphic)
     */
    public function related()
    {
        return $this->morphTo();
    }

    /**
     * Scope by reason
     */
    public function scopeByReason($query, $reason)
    {
        return $query->where('reason', $reason);
    }

    /**
     * Scope by type
     */
    public function scopeByType($query, $type)
    {
        return $query->where('adjustment_type', $type);
    }

    /**
     * Generate unique adjustment code
     */
    public static function generateCode()
    {
        $date = now()->format('Ymd');
        $lastAdjustment = static::whereDate('created_at', now()->toDateString())
            ->orderBy('id', 'desc')
            ->first();
        
        $sequence = 1;
        if ($lastAdjustment) {
            $lastSequence = intval(substr($lastAdjustment->adjustment_code, -4));
            $sequence = $lastSequence + 1;
        }
        
        return 'ADJ-' . $date . '-' . str_pad($sequence, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Get reason label
     */
    public function getReasonLabelAttribute()
    {
        return self::REASONS[$this->reason] ?? $this->reason;
    }
}
