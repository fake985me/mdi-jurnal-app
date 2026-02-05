<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Purchase extends Model
{
    use HasFactory;

    protected $fillable = [
        'po_number',
        'supplier_name',
        'supplier_address',
        'supplier_phone',
        'total_amount',
        'status',
        'is_for_asset',
        'user_id',
        'order_date',
        'received_date',
        'notes'
    ];

    protected $casts = [
        'total_amount' => 'decimal:2',
        'order_date' => 'date',
        'received_date' => 'date',
    ];

    /**
     * Get the user who created the purchase
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get purchase items
     */
    public function items()
    {
        return $this->hasMany(PurchaseItem::class);
    }

    /**
     * Get stock transactions morphed from this purchase
     */
    public function stockTransactions()
    {
        return $this->morphMany(StockTransaction::class, 'reference');
    }
}
