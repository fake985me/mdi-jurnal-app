<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Delivery extends Model
{
    use HasFactory;

    protected $fillable = [
        'sale_id',
        'from_warehouse_id',
        'status',
        'tracking_number',
        'courier',
        'destination',
        'shipped_date',
        'delivered_date',
        'user_id',
        'notes'
    ];

    protected $casts = [
        'shipped_date' => 'date',
        'delivered_date' => 'date',
    ];

    /**
     * Get the sale this delivery belongs to
     */
    public function sale()
    {
        return $this->belongsTo(Sale::class);
    }

    /**
     * Get the warehouse this delivery ships from
     */
    public function fromWarehouse()
    {
        return $this->belongsTo(Warehouse::class, 'from_warehouse_id');
    }

    /**
     * Get the user who created the delivery
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get delivery items
     */
    public function items()
    {
        return $this->hasMany(DeliveryItem::class);
    }
}
