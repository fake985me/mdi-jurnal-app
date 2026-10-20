<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ExpenseItem extends Model
{
    protected $guarded = ['id'];
    protected $casts = ['amount' => 'decimal:2', 'tax_rate' => 'decimal:2', 'tax_amount' => 'decimal:2', 'total' => 'decimal:2'];
    public function category() { return $this->belongsTo(ExpenseCategory::class, 'expense_category_id'); }
}
