<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SalesPerson extends Model
{
    use HasFactory;

    protected $table = 'sales_people';

    protected $fillable = [
        'user_id',
        'name',
        'phone',
        'email',
        'commission_rate',
        'active'
    ];

    protected $casts = [
        'commission_rate' => 'decimal:2',
        'active' => 'boolean',
    ];

    /**
     * Get the associated user
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get sales
     */
    public function sales()
    {
        return $this->hasMany(Sale::class, 'sales_person_id');
    }
}
