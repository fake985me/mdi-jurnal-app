<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Lending extends Model
{
    use HasFactory;

    protected $fillable = [
        'lending_code',
        'product_id',
        'borrower_name',
        'borrower_contact',
        'borrower_organization',
        'quantity',
        'lending_date',
        'expected_return_date',
        'actual_return_date',
        'status',
        'user_id',
        'notes'
    ];

    protected $casts = [
        'quantity' => 'integer',
        'lending_date' => 'date',
        'expected_return_date' => 'date',
        'actual_return_date' => 'date',
    ];

    protected $appends = ['remaining_quantity'];

    public function getRemainingQuantityAttribute()
    {
        $returned = $this->relationLoaded('returns')
            ? $this->returns->sum('quantity_returned')
            : ($this->returns_sum_quantity_returned ?? $this->returns()->sum('quantity_returned'));
        return max(0, $this->quantity - $returned);
    }

    /**
     * Get the product
     */
    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Get the user who created the lending
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get lending returns
     */
    public function returns()
    {
        return $this->hasMany(LendingReturn::class);
    }

    /**
     * Get stock transactions morphed from this lending
     */
    public function stockTransactions()
    {
        return $this->morphMany(StockTransaction::class, 'reference');
    }
}
