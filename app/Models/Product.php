<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;

    /**
     * Boot the model and add event listeners for auto stock sync
     */
    protected static function boot()
    {
        parent::boot();

        // Auto-sync CurrentStock when Product is created (only for non-asset products)
        static::created(function ($product) {
            if (!$product->is_asset) {
                \App\Models\CurrentStock::firstOrCreate(
                    ['product_id' => $product->id],
                    ['quantity' => $product->stock ?? 0, 'last_updated' => now()]
                );
            }
        });

        // Auto-sync CurrentStock when Product stock is updated (only for non-asset products)
        static::updated(function ($product) {
            if (!$product->is_asset && $product->isDirty('stock')) {
                $currentStock = \App\Models\CurrentStock::firstOrCreate(
                    ['product_id' => $product->id],
                    ['quantity' => 0, 'last_updated' => now()]
                );
                $currentStock->quantity = $product->stock ?? 0;
                $currentStock->last_updated = now();
                $currentStock->save();
            }
        });

        // Auto-delete CurrentStock when Product is deleted
        static::deleted(function ($product) {
            \App\Models\CurrentStock::where('product_id', $product->id)->delete();
        });
    }

    protected $fillable = [
        'sku',
        'image',
        'category',
        'sub_category',
        'brand',
        'title',
        'descriptions',
        'stock',
        'minimum_stock',
        'is_asset',
    ];

    protected $casts = [
        'stock' => 'integer',
        'minimum_stock' => 'integer',
        'is_asset' => 'boolean',
    ];

    /**
     * Stock management scopes
     */
    public function scopeLowStock($query)
    {
        return $query->whereColumn('stock', '<=', 'minimum_stock');
    }

    public function scopeInStock($query)
    {
        return $query->where('stock', '>', 0);
    }

    public function scopeOutOfStock($query)
    {
        return $query->where('stock', '<=', 0);
    }

    /**
     * Scope for asset products (not counted as sales stock)
     */
    public function scopeAssetProducts($query)
    {
        return $query->where('is_asset', true);
    }

    /**
     * Scope for sales products (regular stock)
     */
    public function scopeSalesProducts($query)
    {
        return $query->where('is_asset', false);
    }

    /**
     * Get all category-subcategory pairs for this product
     */
    public function productCategories()
    {
        return $this->hasMany(ProductCategory::class);
    }

    /**
     * Get all categories for this product (many-to-many)
     */
    public function categories()
    {
        return $this->belongsToMany(Category::class, 'product_category')
            ->withPivot('sub_category_id')
            ->withTimestamps();
    }

    /**
     * Sync categories with subcategories
     * @param array $categoryData Array of ['category_id' => x, 'sub_category_id' => y]
     */
    public function syncCategories(array $categoryData)
    {
        // Delete existing
        $this->productCategories()->delete();

        // Insert new
        foreach ($categoryData as $item) {
            if (!empty($item['category_id'])) {
                $this->productCategories()->create([
                    'category_id' => $item['category_id'],
                    'sub_category_id' => $item['sub_category_id'] ?? null,
                ]);
            }
        }
    }

    /**
     * Get formatted category-subcategory pairs
     */
    public function getCategoryPairsAttribute()
    {
        return $this->productCategories->map(function ($pc) {
            return [
                'category_id' => $pc->category_id,
                'category_name' => $pc->category?->name,
                'sub_category_id' => $pc->sub_category_id,
                'sub_category_name' => $pc->subCategory?->name,
            ];
        });
    }
}

