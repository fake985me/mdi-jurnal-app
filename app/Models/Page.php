<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Page extends Model
{
    use HasFactory;

    protected $fillable = [
        'slug',
        'title',
        'meta_description',
        'meta_keywords',
        'is_published',
        'published_at',
        'template_id',
        'created_by',
        'updated_by',
        'show_in_nav',
        'nav_order',
        'nav_parent',
        'nav_label',
    ];

    protected $casts = [
        'is_published' => 'boolean',
        'show_in_nav' => 'boolean',
        'published_at' => 'datetime',
    ];

    public function sections()
    {
        return $this->hasMany(PageSection::class)->orderBy('order');
    }

    public function template()
    {
        return $this->belongsTo(PageTemplate::class, 'template_id');
    }

    public function scopePublished($query)
    {
        return $query->where('is_published', true);
    }

    public function scopeBySlug($query, $slug)
    {
        return $query->where('slug', $slug);
    }

    public function scopeForNavigation($query)
    {
        return $query->where('show_in_nav', true)
            ->where('is_published', true)
            ->orderBy('nav_order');
    }
}
