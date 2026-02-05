<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CarouselSlide extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'text',
        'bg_image',
        'center_img',
        'img_left',
        'mid_left',
        'mid_right',
        'img_right',
        'use_component',
        'component_name',
        'order',
        'is_active',
    ];

    protected $casts = [
        'use_component' => 'boolean',
        'is_active' => 'boolean',
        'order' => 'integer',
    ];

    /**
     * Default query scope to order by order column
     */
    protected static function booted()
    {
        static::addGlobalScope('ordered', function ($builder) {
            $builder->orderBy('order');
        });
    }

    /**
     * Scope to get only active slides
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
