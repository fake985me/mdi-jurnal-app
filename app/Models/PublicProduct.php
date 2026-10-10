<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PublicProduct extends Model
{
    use HasFactory;

    protected $fillable = [
        'sku',
        'module',
        'slug',
        'image',
        'category',
        'sub_category',
        'brand',
        'stock',
        'title',
        'subtitle',
        'descriptions',
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
        // Physical
        'dimensions',
        // Diagram
        'diagram', 'network_diagram',
        // Visibility
        'is_active', 'sort_order',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'sort_order' => 'integer',
        'stock' => 'integer',
    ];

    /**
     * Boot the model and add event listeners
     */
    protected static function boot()
    {
        parent::boot();

        // Auto-generate slug when creating/updating if slug is empty
        static::saving(function ($product) {
            if (empty($product->slug) && !empty($product->title)) {
                $slug = str($product->title)
                    ->lower()
                    ->replace(' ', '-')
                    ->replace('/', '-')
                    ->replaceMatches('/[^a-z0-9\-]/', '')
                    ->replaceMatches('/-+/', '-')
                    ->trim('-')
                    ->toString();

                // Ensure unique slug
                $originalSlug = $slug;
                $counter = 1;

                while (static::where('slug', $slug)->where('id', '!=', $product->id)->exists()) {
                    $slug = $originalSlug . '-' . $counter;
                    $counter++;
                }

                $product->slug = $slug;
            }
        });
    }

    /**
     * Scope for active products
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to order by sort order
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('created_at', 'desc');
    }

    /**
     * Get all category-subcategory pairs for this product
     */
    public function productCategories()
    {
        return $this->hasMany(PublicProductCategory::class);
    }

    /**
     * Get the internal (inventory) product linked to this public product
     */
    public function internalProduct()
    {
        return $this->hasOne(Product::class, 'public_product_id');
    }

    /**
     * Get all categories for this product (many-to-many)
     */
    public function categories()
    {
        return $this->belongsToMany(Category::class, 'public_product_category')
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

        // Selalu sinkronkan kolom string setelah update pivot
        $this->syncCategoryStrings();
    }

    /**
     * Sinkronkan kolom string category/sub_category dari entri pivot pertama.
     * Diperlukan agar filter di frontend (yang masih memakai kolom string)
     * tetap berfungsi meskipun data dikelola via pivot table.
     */
    public function syncCategoryStrings(): void
    {
        $first = $this->productCategories()
            ->with('category', 'subCategory')
            ->first();

        $this->category     = $first?->category?->name ?? $this->category;
        $this->sub_category = $first?->subCategory?->name ?? $this->sub_category;
        $this->saveQuietly(); // saveQuietly agar tidak trigger event loop
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

