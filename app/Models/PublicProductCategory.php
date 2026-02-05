<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PublicProductCategory extends Model
{
    use HasFactory;

    protected $table = 'public_product_category';

    protected $fillable = [
        'public_product_id',
        'category_id',
        'sub_category_id',
    ];

    /**
     * Get the public product
     */
    public function publicProduct()
    {
        return $this->belongsTo(PublicProduct::class);
    }

    /**
     * Get the category
     */
    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * Get the subcategory
     */
    public function subCategory()
    {
        return $this->belongsTo(SubCategory::class);
    }
}
