<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SubCategory extends Model
{
    use HasFactory;

    protected $fillable = [
        'category_id',
        'name',
        'description',
        'parent_id',
    ];

    /**
     * Get the category that owns the sub category
     */
    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * Parent subcategory (for multi-level subcategory tree)
     */
    public function parent()
    {
        return $this->belongsTo(SubCategory::class, 'parent_id');
    }

    /**
     * Child subcategories (for multi-level subcategory tree)
     */
    public function children()
    {
        return $this->hasMany(SubCategory::class, 'parent_id');
    }

    public function products()
    {
        return $this->belongsToMany(
            Product::class,
            'product_category',
            'sub_category_id',
            'product_id'
        );
    }
}
