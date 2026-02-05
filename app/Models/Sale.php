<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Sale extends Model
{
    use HasFactory;

    protected $fillable = [
        'invoice_number',
        'customer_name',
        'customer_email',
        'customer_phone',
        'customer_address',
        'total_amount',
        'subtotal',
        'tax_type',
        'tax_rate',
        'tax_amount',
        'discount_amount',
        'grand_total',
        'status',
        'sales_person_id',
        'user_id',
        'sale_date',
        'notes'
    ];

    protected $casts = [
        'total_amount' => 'decimal:2',
        'subtotal' => 'decimal:2',
        'tax_rate' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'grand_total' => 'decimal:2',
        'sale_date' => 'date',
    ];

    /**
     * Get the user who created the sale
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the sales person
     */
    public function salesPerson()
    {
        return $this->belongsTo(SalesPerson::class, 'sales_person_id');
    }

    /**
     * Get sale items
     */
    public function items()
    {
        return $this->hasMany(SaleItem::class);
    }

    /**
     * Get warranties
     */
    public function warranties()
    {
        return $this->hasMany(Warranty::class);
    }

    /**
     * Get RMAs for this sale
     */
    public function rmas()
    {
        return $this->hasMany(RMA::class);
    }

    /**
     * Check if sale has active warranty for a specific product
     */
    public function hasActiveWarranty($productId = null)
    {
        $query = $this->warranties()
            ->where(function ($q) {
                $q->whereNull('warranty_end')
                  ->orWhere('warranty_end', '>=', now());
            });

        if ($productId) {
            $query->where('product_id', $productId);
        }

        return $query->exists();
    }

    /**
     * Get stock transactions
     */
    public function stockTransactions()
    {
        return $this->hasMany(StockTransaction::class, 'reference_id')
            ->where('reference_type', 'sale');
    }

    /**
     * Get the delivery for this sale
     */
    public function delivery()
    {
        return $this->hasOne(Delivery::class);
    }

    /**
     * Get the invoice for this sale
     */
    public function invoice()
    {
        return $this->hasOne(Invoice::class);
    }

    /**
     * Calculate subtotal from items
     */
    public function calculateSubtotal()
    {
        return $this->items()->sum(\DB::raw('quantity * unit_price'));
    }

    /**
     * Calculate tax amount based on subtotal and tax type
     */
    public function calculateTax($taxType = 'ppn')
    {
        $taxRate = TaxRate::getByCode($taxType);
        if (!$taxRate) return 0;
        
        $subtotal = $this->subtotal ?: $this->calculateSubtotal();
        return $taxRate->calculateTax($subtotal);
    }

    /**
     * Calculate grand total (subtotal + tax - discount)
     */
    public function calculateGrandTotal()
    {
        $subtotal = $this->subtotal ?: 0;
        $tax = $this->tax_amount ?: 0;
        $discount = $this->discount_amount ?: 0;
        
        return $subtotal + $tax - $discount;
    }

    /**
     * Apply tax calculations to the sale
     */
    public function applyTax($taxType = 'ppn')
    {
        $this->subtotal = $this->calculateSubtotal();
        $this->tax_type = $taxType;
        
        $taxRate = TaxRate::getByCode($taxType);
        if ($taxRate) {
            $this->tax_rate = $taxRate->rate;
            $this->tax_amount = $taxRate->calculateTax($this->subtotal);
        }
        
        $this->grand_total = $this->calculateGrandTotal();
        
        return $this;
    }
}

