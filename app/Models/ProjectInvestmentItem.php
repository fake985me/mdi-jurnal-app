<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProjectInvestmentItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'project_investment_id',
        'product_id',
        'quantity',
        'unit_price',
        'total_price',
        'stock_deducted',
        'notes',
        'serial_numbers',
        'returned_item_id',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'serial_numbers' => 'array',
        'unit_price' => 'decimal:2',
        'total_price' => 'decimal:2',
        'stock_deducted' => 'boolean',
    ];

    public function project()
    {
        return $this->belongsTo(ProjectInvestment::class, 'project_investment_id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function transfers() { return $this->hasMany(StockTransfer::class); }
}
