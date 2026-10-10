<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

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
}
