<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Invoice extends Model
{
    use HasFactory;

    protected $fillable = [
        'sale_id',
        'invoice_number',
        'invoice_date',
        'due_date',
        'subtotal',
        'tax_type',
        'tax_rate',
        'tax_amount',
        'discount_amount',
        'total',
        'status',
        'notes',
    ];

    protected $casts = [
        'invoice_date' => 'date',
        'due_date' => 'date',
        'subtotal' => 'decimal:2',
        'tax_rate' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'total' => 'decimal:2',
    ];

    public function sale()
    {
        return $this->belongsTo(Sale::class);
    }

    public static function generateInvoiceNumber()
    {
        $prefix = 'INV';
        $year = date('Y');
        $month = date('m');
        
        $lastInvoice = self::whereYear('created_at', $year)
            ->whereMonth('created_at', $month)
            ->orderBy('id', 'desc')
            ->first();
        
        $sequence = $lastInvoice ? intval(substr($lastInvoice->invoice_number, -4)) + 1 : 1;
        
        return sprintf('%s-%s%s-%04d', $prefix, $year, $month, $sequence);
    }

    public function calculateFromSale(Sale $sale, $taxType = 'ppn')
    {
        $taxRate = TaxRate::getByCode($taxType);
        
        $this->sale_id = $sale->id;
        $this->subtotal = $sale->total_amount;
        $this->tax_type = $taxType;
        $this->tax_rate = $taxRate ? $taxRate->rate : 0;
        $this->tax_amount = $taxRate ? $taxRate->calculateTax($this->subtotal) : 0;
        $this->discount_amount = $sale->discount_amount ?? 0;
        $this->total = $this->subtotal + $this->tax_amount - $this->discount_amount;
        
        return $this;
    }

    public function isPaid()
    {
        return $this->status === 'paid';
    }

    public function isOverdue()
    {
        if ($this->status === 'paid') return false;
        return $this->due_date && now()->gt($this->due_date);
    }
}
