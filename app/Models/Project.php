<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Project extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'description',
        'image_url',
        'url',
        'category',
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
        if (is_string($imagePath) && str_starts_with($imagePath, '/storage/projects/')) {
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

    public function scopeByCategory($query, $category)
    {
        return $query->where('category', $category);
    }
}
