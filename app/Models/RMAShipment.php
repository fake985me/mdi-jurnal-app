<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RMAShipment extends Model
{
    use HasFactory;

    protected $table = 'rma_shipments';

    protected $fillable = [
        'shipment_code',
        'rma_id',
        'product_id',
        'quantity',
        'serial_numbers',
        'tracking_number',
        'courier',
        'status',
        'shipped_date',
        'delivered_date',
        'recipient_name',
        'recipient_address',
        'recipient_phone',
        'notes',
        'user_id'
    ];

    protected $casts = [
        'quantity' => 'integer',
        'serial_numbers' => 'array',
        'shipped_date' => 'date',
        'delivered_date' => 'date',
    ];

    public function rma()
    {
        return $this->belongsTo(RMA::class, 'rma_id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public static function generateCode()
    {
        $date = now()->format('Ymd');
        $count = self::whereDate('created_at', today())->count() + 1;
        return 'SHIP-RMA-' . $date . '-' . str_pad($count, 4, '0', STR_PAD_LEFT);
    }
}
