<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Delivery extends Model
{
    use HasFactory;

    protected $fillable = [
        'sale_id',
        'rma_id',
        'msa_project_id',
        'lending_id',
        'stock_transfer_id',
        'stock_transaction_id',
        'product_id',
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

    public function rma()
    {
        return $this->belongsTo(RMA::class);
    }

    public function msaProject()
    {
        return $this->belongsTo(MSAProject::class, 'msa_project_id');
    }

    public function lending()
    {
        return $this->belongsTo(Lending::class);
    }

    public function stockTransfer()
    {
        return $this->belongsTo(StockTransfer::class);
    }

    public function stockTransaction()
    {
        return $this->belongsTo(StockTransaction::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
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
