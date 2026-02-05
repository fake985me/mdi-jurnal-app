<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProjectMaterial extends Model
{
    use HasFactory;

    protected $fillable = [
        'project_investment_id',
        'name',
        'unit',
        'specification',
        'quantity_planned',
        'quantity_ordered',
        'quantity_received',
        'quantity_installed',
        'unit_price',
        'total_price',
        'status',
        'expected_delivery_date',
        'actual_delivery_date',
        'notes',
        'sort_order',
    ];

    protected $casts = [
        'quantity_planned' => 'integer',
        'quantity_ordered' => 'integer',
        'quantity_received' => 'integer',
        'quantity_installed' => 'integer',
        'unit_price' => 'decimal:2',
        'total_price' => 'decimal:2',
        'expected_delivery_date' => 'date',
        'actual_delivery_date' => 'date',
        'sort_order' => 'integer',
    ];

    const STATUS_PENDING = 'pending';
    const STATUS_ORDERED = 'ordered';
    const STATUS_PARTIAL = 'partial';
    const STATUS_RECEIVED = 'received';
    const STATUS_INSTALLED = 'installed';

    public function project()
    {
        return $this->belongsTo(ProjectInvestment::class, 'project_investment_id');
    }

    public function getDeliveryProgressAttribute()
    {
        if ($this->quantity_planned <= 0) return 0;
        return round(($this->quantity_received / $this->quantity_planned) * 100);
    }

    public function getInstallationProgressAttribute()
    {
        if ($this->quantity_planned <= 0) return 0;
        return round(($this->quantity_installed / $this->quantity_planned) * 100);
    }

    public function updateStatus()
    {
        if ($this->quantity_installed >= $this->quantity_planned) {
            $this->status = self::STATUS_INSTALLED;
        } elseif ($this->quantity_received >= $this->quantity_planned) {
            $this->status = self::STATUS_RECEIVED;
        } elseif ($this->quantity_received > 0) {
            $this->status = self::STATUS_PARTIAL;
        } elseif ($this->quantity_ordered > 0) {
            $this->status = self::STATUS_ORDERED;
        } else {
            $this->status = self::STATUS_PENDING;
        }
        $this->save();
    }

    public function calculateTotalPrice()
    {
        $this->total_price = $this->quantity_planned * $this->unit_price;
        $this->save();
    }
}
