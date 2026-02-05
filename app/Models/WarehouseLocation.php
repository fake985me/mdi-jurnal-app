<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WarehouseLocation extends Model
{
    use HasFactory;

    protected $fillable = [
        'warehouse_id',
        'code',
        'name',
        'zone',
        'aisle',
        'rack',
        'shelf',
        'capacity',
        'is_active',
    ];

    protected $casts = [
        'capacity' => 'integer',
        'is_active' => 'boolean',
    ];

    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function stocks()
    {
        return $this->hasMany(CurrentStock::class, 'location_id');
    }

    public function getCurrentStockAttribute()
    {
        return $this->stocks()->sum('quantity');
    }

    public function getAvailableCapacityAttribute()
    {
        if (!$this->capacity) {
            return null;
        }
        return $this->capacity - $this->current_stock;
    }

    public function getFullPathAttribute()
    {
        $parts = array_filter([
            $this->zone,
            $this->aisle,
            $this->rack,
            $this->shelf
        ]);
        
        return $parts ? implode('-', $parts) : $this->code;
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($location) {
            if (empty($location->code)) {
                $warehouse = Warehouse::find($location->warehouse_id);
                $count = $warehouse ? $warehouse->locations()->count() + 1 : 1;
                $location->code = 'LOC-' . str_pad($count, 3, '0', STR_PAD_LEFT);
            }
        });
    }
}
