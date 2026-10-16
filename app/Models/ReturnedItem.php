<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReturnedItem extends Model
{
    protected $guarded = ['id'];
    protected $casts = ['quantity' => 'integer', 'available_quantity' => 'integer', 'book_value' => 'decimal:2', 'inspected_at' => 'datetime'];
    public function product() { return $this->belongsTo(Product::class); }
}
