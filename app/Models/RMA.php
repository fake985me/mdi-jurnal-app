<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class RMA extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'rmas';

    protected $fillable = [
        'rma_code',
        'warranty_id',
        'sale_id',
        'sale_item_id',
        'msa_project_id',
        'product_id',
        'serial_number',
        'returned_serial_number',
        'warranty_claim_flow',
        'inspected_at',
        'customer_name',
        'customer_contact',
        'quantity',
        'reason',
        'status',
        'issue_date',
        'received_date',
        'condition',
        'condition_level',
        'rma_warehouse_id',
        'resolution',
        'replacement_requested',
        'notes',
        'evidence_files',
        'user_id'
    ];

    protected $casts = [
        'quantity' => 'integer',
        'warranty_claim_flow' => 'boolean',
        'inspected_at' => 'datetime',
        'issue_date' => 'date',
        'received_date' => 'date',
        'replacement_requested' => 'boolean',
        'evidence_files' => 'array',
    ];

    /**
     * Get the warranty (optional)
     */
    public function warranty()
    {
        return $this->belongsTo(Warranty::class);
    }

    /**
     * Get the original sale
     */
    public function sale()
    {
        return $this->belongsTo(Sale::class);
    }

    /**
     * Get the specific sale item
     */
    public function saleItem()
    {
        return $this->belongsTo(SaleItem::class);
    }

    /**
     * Get the MSA project (if applicable)
     */
    public function msaProject()
    {
        return $this->belongsTo(MSAProject::class);
    }

    /**
     * Get the product
     */
    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Get the user who created the RMA
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function rmaWarehouse()
    {
        return $this->belongsTo(Warehouse::class, 'rma_warehouse_id');
    }

    public function shipments()
    {
        return $this->hasMany(RMAShipment::class, 'rma_id');
    }

    public function shipment()
    {
        return $this->hasOne(RMAShipment::class, 'rma_id');
    }

    public static function validateEligibility($saleId, $saleItemId = null, $productId = null)
    {
        $sale = Sale::with(['invoice', 'warranties.product', 'items'])->find($saleId);
        
        if (!$sale) {
            return ['valid' => false, 'reason' => 'Sale not found'];
        }

        // Check for MSA first
        if ($saleItemId) {
            $saleItem = SaleItem::find($saleItemId);
            if ($saleItem) {
                // Check if there's an active MSA for this product
                $msaProject = MSAProject::where('product_id', $saleItem->product_id)
                    ->where('status', 'active')
                    ->first();
                
                if ($msaProject) {
                    return [
                        'valid' => true, 
                        'reason' => 'Covered by MSA contract: ' . $msaProject->msa_code,
                        'msa_project_id' => $msaProject->id
                    ];
                }
            }
        }

        // Check warranty
        $purchaseDate = $sale->invoice?->invoice_date ?? $sale->sale_date;
        $activeWarranties = $sale->warranties->filter(fn ($warranty) =>
            (!$productId || (int) $warranty->product_id === (int) $productId)
            && $warranty->isActiveOn($purchaseDate)
        );
        $activeWarranty = $activeWarranties->first();

        if ($activeWarranty) {
            return [
                'valid' => true, 
                'reason' => 'Covered by warranty until: ' . ($activeWarranty->coverageEndDate($purchaseDate)?->toDateString() ?? 'Unlimited'),
                'warranty_id' => $activeWarranty->id,
                'warranty_serial_numbers' => $activeWarranties->pluck('serial_number')->filter()->values()->all(),
            ];
        }

        return ['valid' => false, 'reason' => 'No active warranty or MSA contract found'];
    }
}
