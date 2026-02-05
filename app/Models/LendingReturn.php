<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LendingReturn extends Model
{
    use HasFactory;

    protected $fillable = [
        'lending_id',
        'return_date',
        'quantity_returned',
        'condition',
        'notes'
    ];

    protected $casts = [
        'return_date' => 'date',
        'quantity_returned' => 'integer',
    ];

    /**
     * Get the lending
     */
    public function lending()
    {
        return $this->belongsTo(Lending::class);
    }
}
