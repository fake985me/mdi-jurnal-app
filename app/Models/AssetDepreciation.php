<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AssetDepreciation extends Model
{
    protected $guarded = ['id'];
    protected $casts = ['period' => 'date', 'amount' => 'decimal:2', 'book_value_after' => 'decimal:2'];
}
