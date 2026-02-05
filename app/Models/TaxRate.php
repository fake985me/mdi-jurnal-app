<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TaxRate extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'code',
        'rate',
        'description',
        'is_active',
    ];

    protected $casts = [
        'rate' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public static function getPPN()
    {
        return self::where('code', 'ppn')->where('is_active', true)->first();
    }

    public static function getPPh()
    {
        return self::where('code', 'pph23')->where('is_active', true)->first();
    }

    public static function getByCode($code)
    {
        return self::where('code', $code)->where('is_active', true)->first();
    }

    public function calculateTax($amount)
    {
        return round($amount * ($this->rate / 100), 2);
    }
}
