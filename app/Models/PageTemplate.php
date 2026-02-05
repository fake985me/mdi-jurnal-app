<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PageTemplate extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'thumbnail',
        'default_sections',
        'is_active',
    ];

    protected $casts = [
        'default_sections' => 'array',
        'is_active' => 'boolean',
    ];

    public function pages()
    {
        return $this->hasMany(Page::class, 'template_id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
