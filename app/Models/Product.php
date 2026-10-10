<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * Boot the model and add event listeners for auto stock sync
     */
    protected static function boot()
    {
        parent::boot();

        // Auto-sync CurrentStock when Product is created (only for non-asset products)
        static::created(function ($product) {
            if (!$product->is_asset) {
                $defaultWarehouseId = \App\Models\Warehouse::getDefault()?->id;
                // Tanpa gudang default, jangan buat baris stok yatim (warehouse_id null)
                if (!$defaultWarehouseId) {
                    return;
                }
                \App\Models\CurrentStock::firstOrCreate(
                    ['product_id' => $product->id, 'warehouse_id' => $defaultWarehouseId],
                    ['quantity' => $product->stock ?? 0, 'last_updated' => now()]
                );
            }
        });

        // Auto-sync CurrentStock when Product stock is updated (only for non-asset products)
        static::updated(function ($product) {
            if (!$product->is_asset && $product->isDirty('stock')) {
                $defaultWarehouseId = \App\Models\Warehouse::getDefault()?->id;
                if (!$defaultWarehouseId) {
                    return;
                }
                $currentStock = \App\Models\CurrentStock::firstOrCreate(
                    ['product_id' => $product->id, 'warehouse_id' => $defaultWarehouseId],
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
        'subtitle',
        'module',
        'slug',
        'descriptions',
        'stock',
        'minimum_stock',
        'price',
        'warranty_period_months',
        'is_asset',
        'public_product_id',
        // Specs 1-20
        'spec1', 'spec2', 'spec3', 'spec4', 'spec5', 'spec6', 'spec7', 'spec8', 'spec9', 'spec10',
        'spec11', 'spec12', 'spec13', 'spec14', 'spec15', 'spec16', 'spec17', 'spec18', 'spec19', 'spec20',
        // Features 1-20
        'fitur1', 'fitur2', 'fitur3', 'fitur4', 'fitur5', 'fitur6', 'fitur7', 'fitur8', 'fitur9', 'fitur10',
        'fitur11', 'fitur12', 'fitur13', 'fitur14', 'fitur15', 'fitur16', 'fitur17', 'fitur18', 'fitur19', 'fitur20',
        // Wireless
        'wireless_standard', 'wireless_stream', 'wireless_stream1', 'wireless_stream2',
        'wireless_stream3', 'wireless_stream4', 'wireless_stream5',
        // Capacity & Performance
        'manageable_aps', 'ap_number', 'number_of_clients', 'capacity',
        'ip_rating', 'switching', 'throughput',
        // Memory
        'flash_memory', 'sdram_memory',
        // Interfaces
        'interface_main', 'interface1', 'interface2', 'interface3', 'interface4', 'interface5',
        'antena',
        // CU
        'cu', 'cu1', 'cu2', 'cu3', 'cu4', 'cu5',
        // Additional interfaces
        'additional_interface1', 'additional_interface2', 'additional_interface3', 'additional_interface4', 'additional_interface5',
        // Networking
        'mac_address', 'routing_table',
        // Environmental
        'dustproof_waterproof', 'noise', 'mtbf',
        'operating_temperature', 'storage_temperature', 'operating_humidity',
        // Power
        'power1', 'power2', 'power3', 'power4', 'power5', 'power_consumptions',
        'power_consumption_with_poe', 'power_consumption_excluding_poe', 'poe_budget',
        // Physical & Diagrams
        'dimensions', 'diagram', 'network_diagram',
    ];

    protected $casts = [
        'stock' => 'integer',
        'minimum_stock' => 'integer',
        'warranty_period_months' => 'integer',
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
     * Get all current stocks across warehouses
     */
    public function currentStocks()
    {
        return $this->hasMany(CurrentStock::class);
    }

    /**
     * Get the linked public product (website catalog)
     */
    public function publicProduct()
    {
        return $this->belongsTo(PublicProduct::class);
    }

    /**
     * Check if this product is published to the public website
     */
    public function isPublished(): bool
    {
        return !is_null($this->public_product_id);
    }

    /**
     * Get sale items referencing this product
     */
    public function saleItems()
    {
        return $this->hasMany(SaleItem::class);
    }

    /**
     * Get purchase items referencing this product
     */
    public function purchaseItems()
    {
        return $this->hasMany(PurchaseItem::class);
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

        // Sinkronkan kolom string agar filter dan sync-to-public selalu akurat
        $this->syncCategoryStrings();
    }

    /**
     * Sinkronkan kolom string category/sub_category dari entri pivot pertama.
     * Menjaga konsistensi antara pivot table dan kolom legacy string.
     */
    public function syncCategoryStrings(): void
    {
        $first = $this->productCategories()
            ->with('category', 'subCategory')
            ->first();

        $this->category     = $first?->category?->name ?? $this->category;
        $this->sub_category = $first?->subCategory?->name ?? $this->sub_category;
        $this->saveQuietly();
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
