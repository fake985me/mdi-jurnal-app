<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Purchase extends Model
{
    use HasFactory;

    protected $fillable = [
        'po_number',
        'supplier_id',
        'supplier_name',
        'supplier_address',
        'supplier_phone',
        'warehouse_id',
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
     * Get the supplier for this purchase
     */
    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    /**
     * Get the user who created the purchase
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the warehouse for this purchase (stock destination)
     */
    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class);
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

    /**
     * Payments for this purchase
     */
    public function payments()
    {
        return $this->morphMany(Payment::class, 'payable');
    }
}
