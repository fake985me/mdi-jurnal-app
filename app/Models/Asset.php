<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Asset extends Model
{
    use HasFactory;

    protected $fillable = [
        'asset_code',
        'name',
        'product_id',
        'purchase_item_id',
        'category',
        'brand',
        'model',
        'serial_number',
        'location',
        'condition',
        'status',
        'purchase_date',
        'purchase_price',
        'current_value',
        'assigned_to',
        'warranty_end_date',
        'notes',
        'image',
    ];

    protected $casts = [
        'purchase_date' => 'date',
        'warranty_end_date' => 'date',
        'purchase_price' => 'decimal:2',
        'current_value' => 'decimal:2',
    ];

    /**
     * Get the product associated with this asset
     */
    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Get the purchase item (for traceability)
     */
    public function purchaseItem()
    {
        return $this->belongsTo(PurchaseItem::class);
    }

    /**
     * Get purchase info through purchaseItem
     */
    public function getPurchaseAttribute()
    {
        return $this->purchaseItem?->purchase;
    }

    /**
     * Scopes for filtering
     */
    public function scopeByCategory($query, $category)
    {
        return $query->where('category', $category);
    }

    public function scopeByLocation($query, $location)
    {
        return $query->where('location', $location);
    }

    public function scopeByCondition($query, $condition)
    {
        return $query->where('condition', $condition);
    }

    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    public function scopeActive($query)
    {
        return $query->whereIn('status', ['active', 'in_use']);
    }

    /**
     * Generate unique asset code
     */
    public static function generateAssetCode()
    {
        $lastAsset = static::orderBy('id', 'desc')->first();
        $lastNumber = $lastAsset ? intval(substr($lastAsset->asset_code, 4)) : 0;
        return 'AST-' . str_pad($lastNumber + 1, 4, '0', STR_PAD_LEFT);
    }
}
