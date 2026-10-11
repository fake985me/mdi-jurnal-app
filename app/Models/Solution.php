<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Solution extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'description',
        'image_url',
        'order',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'order' => 'integer',
    ];

    public function getImageUrlAttribute($value)
    {
        if (!$value) {
            return $value;
        }

        $imagePath = parse_url($value, PHP_URL_PATH);
        if (is_string($imagePath) && str_starts_with($imagePath, '/storage/solutions/')) {
            return $imagePath;
        }

        return $value;
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('order', 'asc');
    }
}
