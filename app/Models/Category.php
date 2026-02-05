<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
    ];

    /**
     * Get products that match this category name
     * Note: Products use VARCHAR category field, not foreign key
     */
    public function getProductsCountAttribute()
    {
        return \DB::table('products')
            ->where('category', $this->name)
            ->count();
    }

    /**
     * Get subcategories for this category
     */
    public function subCategories()
    {
        return $this->hasMany(SubCategory::class);
    }

    /**
     * Get products through pivot table
     */
    public function products()
    {
        return $this->belongsToMany(Product::class, 'product_category')
            ->withPivot('sub_category_id')
            ->withTimestamps();
    }

    /**
     * Get public products through pivot table
     */
    public function publicProducts()
    {
        return $this->belongsToMany(PublicProduct::class, 'public_product_category')
            ->withPivot('sub_category_id')
            ->withTimestamps();
    }
}

