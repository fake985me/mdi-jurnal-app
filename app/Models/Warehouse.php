<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Warehouse extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
        'address',
        'phone',
        'email',
        'description',
        'is_active',
        'is_default',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_default' => 'boolean',
    ];

    public function locations()
    {
        return $this->hasMany(WarehouseLocation::class);
    }

    public function stocks()
    {
        return $this->hasMany(CurrentStock::class);
    }

    public function outgoingTransfers()
    {
        return $this->hasMany(StockTransfer::class, 'from_warehouse_id');
    }

    public function incomingTransfers()
    {
        return $this->hasMany(StockTransfer::class, 'to_warehouse_id');
    }

    public static function getDefault()
    {
        return static::where('is_default', true)->first();
    }

    public function setAsDefault()
    {
        static::where('is_default', true)->update(['is_default' => false]);
        $this->is_default = true;
        $this->save();
    }

    public function getTotalStockAttribute()
    {
        return $this->stocks()->sum('quantity');
    }

    public function getActiveLocationsCountAttribute()
    {
        return $this->locations()->where('is_active', true)->count();
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($warehouse) {
            if (empty($warehouse->code)) {
                $warehouse->code = 'WH-' . str_pad(static::count() + 1, 3, '0', STR_PAD_LEFT);
            }
        });
    }
}
