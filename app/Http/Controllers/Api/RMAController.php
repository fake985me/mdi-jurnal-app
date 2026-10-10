<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\RMA;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\Warranty;
use App\Models\MSAProject;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class RMAController extends Controller
{
    public function index(Request $request)
    {
        try {
            $query = RMA::with(['warranty', 'product', 'user']);

            // Filter by status
            if ($request->has('status') && !empty($request->status)) {
                $query->where('status', $request->status);
            }

            // Filter by reason
            if ($request->has('reason') && !empty($request->reason)) {
                $query->where('reason', $request->reason);
            }

            // Search
            if ($request->has('search') && !empty($request->search)) {
                $search = $request->search;
                $query->where(function($q) use ($search) {
                    $q->where('rma_code', 'like', "%{$search}%")
                      ->orWhere('customer_name', 'like', "%{$search}%")
                      ->orWhere('serial_number', 'like', "%{$search}%");
                });
            }

            $rmas = $query->orderBy('created_at', 'desc')
                ->paginate($request->per_page ?? 15);

            return response()->json($rmas);
        } catch (\Exception $e) {
            \Log::error('RMAs API Error: ' . $e->getMessage());
            return response()->json([
                'message' => 'Error loading RMAs',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'sale_id' => 'required|exists:sales,id',
            'sale_item_id' => 'nullable|exists:sale_items,id',
            'warranty_id' => 'nullable|exists:warranties,id',
            'msa_project_id' => 'nullable|exists:msa_projects,id',
            'product_id' => 'required|exists:products,id',
            'serial_number' => 'nullable|string|max:255',
            'customer_name' => 'required|string|max:255',
            'customer_contact' => 'nullable|string',
            'quantity' => 'required|integer|min:1',
            'reason' => 'required|in:warranty_claim,damaged_shipment,defective,dead_on_arrival',
            'issue_date' => 'required|date',
            'notes' => 'nullable|string',
            'evidence' => 'nullable|array|max:5',
            'evidence.*' => 'file|mimes:jpg,jpeg,png,webp,pdf|max:5120',
        ]);

        DB::beginTransaction();
        try {
            $stockService = new \App\Services\StockSyncService();
            
            // Validate eligibility (warranty or MSA)
            $eligibility = RMA::validateEligibility(
                $validated['sale_id'],
                $validated['sale_item_id'] ?? null,
                $validated['product_id']
            );

            if (!$eligibility['valid']) {
                return response()->json([
                    'message' => 'RMA tidak dapat dibuat: ' . $eligibility['reason']
                ], 422);
            }

            // Auto-fill warranty_id or msa_project_id from eligibility check
            if (isset($eligibility['warranty_id'])) {
                $validated['warranty_id'] = $eligibility['warranty_id'];
            }
            if (isset($eligibility['msa_project_id'])) {
                $validated['msa_project_id'] = $eligibility['msa_project_id'];
            }

            // Validate serial number against warranties
            if (isset($eligibility['warranty_id']) && !empty($validated['serial_number'])) {
                $validSns = Warranty::where('sale_id', $validated['sale_id'])
                    ->where('product_id', $validated['product_id'])
                    ->where(function ($q) {
                        $q->whereNull('end_date')
                          ->orWhere('end_date', '>=', now());
                    })
                    ->whereNotNull('serial_number')
                    ->pluck('serial_number')
                    ->map(fn($sn) => strtolower(trim($sn)))
                    ->toArray();

                if (!empty($validSns)) {
                    $inputSN = strtolower(trim($validated['serial_number']));
                    if (!in_array($inputSN, $validSns)) {
                        return response()->json([
                            'message' => 'Serial Number tidak valid. Valid SNs: ' . implode(', ', $validSns)
                        ], 422);
                    }
                }
            }

            // Validate quantity against sale item (minus existing RMAs)
            $saleItem = SaleItem::where('sale_id', $validated['sale_id'])
                ->where('product_id', $validated['product_id'])
                ->first();

            if ($saleItem) {
                // Calculate already claimed quantity from existing RMAs
                $existingRmaQty = RMA::where('sale_id', $validated['sale_id'])
                    ->where('product_id', $validated['product_id'])
                    ->whereNotIn('status', ['rejected'])
                    ->sum('quantity');

                $availableQty = $saleItem->quantity - $existingRmaQty;

                if ($validated['quantity'] > $availableQty) {
                    return response()->json([
                        'message' => "Quantity melebihi batas. Qty tersedia untuk RMA: {$availableQty} (dari {$saleItem->quantity} qty penjualan, sudah diklaim: {$existingRmaQty})"
                    ], 422);
                }
            }

            // Check if sufficient stock available
            if (!$stockService->hasStock($validated['product_id'], $validated['quantity'])) {
                $available = $stockService->getStockQuantity($validated['product_id']);
                return response()->json([
                    'message' => "Stock tidak mencukupi untuk RMA. Stock tersedia: {$available}"
                ], 422);
            }

            // Handle evidence file uploads
            $evidenceFiles = [];
            if ($request->hasFile('evidence')) {
                foreach ($request->file('evidence') as $file) {
                    $path = $file->store('rma-evidence', 'public');
                    $evidenceFiles[] = [
                        'path' => $path,
                        'original_name' => $file->getClientOriginalName(),
                        'mime_type' => $file->getMimeType(),
                        'size' => $file->getSize(),
                    ];
                }
            }

            // Auto-generate RMA code
            $rmaCode = 'RMA-' . date('Ymd') . '-' . str_pad(RMA::whereDate('created_at', today())->count() + 1, 4, '0', STR_PAD_LEFT);

            // Remove evidence from validated (it's handled separately)
            unset($validated['evidence']);

            $rma = RMA::create([
                ...$validated,
                'rma_code' => $rmaCode,
                'user_id' => auth()->id(),
                'status' => 'pending',
                'evidence_files' => !empty($evidenceFiles) ? $evidenceFiles : null,
            ]);

            // Reserve stock for RMA (deduct from available)
            $stockService->deductStock(
                $validated['product_id'],
                $validated['quantity'],
                'rma_pending',
                $rma->id,
                "RMA Created: {$rmaCode} - Stock reserved for return process"
            );

            // Auto-create shipment if replacement requested
            if (!empty($validated['replacement_requested']) && $validated['replacement_requested']) {
                $shipment = \App\Models\RMAShipment::create([
                    'shipment_code' => \App\Models\RMAShipment::generateCode(),
                    'rma_id' => $rma->id,
                    'product_id' => $validated['product_id'],
                    'quantity' => $validated['quantity'],
                    'recipient_name' => $validated['customer_name'],
                    'recipient_phone' => $validated['customer_contact'] ?? '',
                    'recipient_address' => $rma->sale->customer_address ?? 'Address to be provided',
                    'status' => 'pending',
                    'user_id' => auth()->id(),
                    'notes' => 'Auto-created from RMA replacement request'
                ]);
            }

            DB::commit();
            return response()->json($rma->load(['warranty', 'product', 'user', 'sale', 'msaProject']), 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    /**
     * Batch store - create RMAs for multiple products at once
     */
    public function batchStore(Request $request)
    {
        $validated = $request->validate([
            'sale_id' => 'required|exists:sales,id',
            'customer_name' => 'required|string|max:255',
            'customer_contact' => 'nullable|string',
            'reason' => 'required|in:warranty_claim,damaged_shipment,defective,dead_on_arrival',
            'issue_date' => 'required|date',
            'notes' => 'nullable|string',
            'items' => 'required|string', // JSON string of items array
            'evidence' => 'nullable|array|max:5',
            'evidence.*' => 'file|mimes:jpg,jpeg,png,webp,pdf|max:5120',
        ]);

        $items = json_decode($validated['items'], true);

        if (!is_array($items) || empty($items)) {
            return response()->json(['message' => 'Minimal pilih 1 produk'], 422);
        }

        DB::beginTransaction();
        try {
            // Handle evidence file uploads (shared across all RMAs)
            $evidenceFiles = [];
            if ($request->hasFile('evidence')) {
                foreach ($request->file('evidence') as $file) {
                    $path = $file->store('rma-evidence', 'public');
                    $evidenceFiles[] = [
                        'path' => $path,
                        'original_name' => $file->getClientOriginalName(),
                        'mime_type' => $file->getMimeType(),
                        'size' => $file->getSize(),
                    ];
                }
            }

            $createdRMAs = [];
            $errors = [];

            $stockService = new \App\Services\StockSyncService();

            foreach ($items as $index => $item) {
                $productId = $item['product_id'] ?? null;
                $serialNumber = $item['serial_number'] ?? '';
                $quantity = $item['quantity'] ?? 1;
                $productTitle = $item['product_title'] ?? "Product #{$productId}";
                $saleItemId = $item['sale_item_id'] ?? null;

                if (!$productId) {
                    $errors[] = "Item #{$index}: Product ID tidak valid";
                    continue;
                }

                // Validate eligibility with sale_item_id for better MSA validation
                $eligibility = RMA::validateEligibility(
                    $validated['sale_id'],
                    $saleItemId,
                    $productId
                );

                if (!$eligibility['valid']) {
                    $errors[] = "{$productTitle}: {$eligibility['reason']}";
                    continue;
                }

                // Validate serial numbers against warranties
                $warrantyId = $eligibility['warranty_id'] ?? null;
                $msaProjectId = $eligibility['msa_project_id'] ?? null;

                if ($warrantyId && $serialNumber) {
                    // Get all valid warranty SNs for this product
                    $validSns = Warranty::where('sale_id', $validated['sale_id'])
                        ->where('product_id', $productId)
                        ->where(function ($q) {
                            $q->whereNull('end_date')
                              ->orWhere('end_date', '>=', now());
                        })
                        ->whereNotNull('serial_number')
                        ->pluck('serial_number')
                        ->map(fn($sn) => strtolower(trim($sn)))
                        ->toArray();

                    if (!empty($validSns)) {
                        // Split comma-separated SNs
                        $inputSns = array_map(fn($sn) => strtolower(trim($sn)), explode(',', $serialNumber));
                        
                        // Check each input SN is valid
                        $invalidSns = array_filter($inputSns, fn($sn) => !in_array($sn, $validSns));
                        
                        if (!empty($invalidSns)) {
                            $errors[] = "{$productTitle}: SN tidak valid: " . implode(', ', $invalidSns) . ". Valid SNs: " . implode(', ', $validSns);
                            continue;
                        }
                    }
                }

                // Validate quantity
                $saleItem = SaleItem::where('sale_id', $validated['sale_id'])
                    ->where('product_id', $productId)
                    ->first();

                if ($saleItem) {
                    $existingRmaQty = RMA::where('sale_id', $validated['sale_id'])
                        ->where('product_id', $productId)
                        ->whereNotIn('status', ['rejected'])
                        ->sum('quantity');

                    $availableQty = $saleItem->quantity - $existingRmaQty;

                    if ($quantity > $availableQty) {
                        $errors[] = "{$productTitle}: Qty melebihi batas (tersedia: {$availableQty})";
                        continue;
                    }
                }

                // Check stock availability
                if (!$stockService->hasStock($productId, $quantity)) {
                    $available = $stockService->getStockQuantity($productId);
                    $errors[] = "{$productTitle}: Stock tidak mencukupi (tersedia: {$available})";
                    continue;
                }

                // Generate unique RMA code
                $rmaCode = 'RMA-' . date('Ymd') . '-' . str_pad(
                    RMA::whereDate('created_at', today())->count() + count($createdRMAs) + 1,
                    4, '0', STR_PAD_LEFT
                );

                $rma = RMA::create([
                    'rma_code' => $rmaCode,
                    'sale_id' => $validated['sale_id'],
                    'sale_item_id' => $saleItemId,
                    'product_id' => $productId,
                    'serial_number' => $serialNumber ?: null,
                    'warranty_id' => $warrantyId,
                    'msa_project_id' => $msaProjectId,
                    'customer_name' => $validated['customer_name'],
                    'customer_contact' => $validated['customer_contact'],
                    'quantity' => $quantity,
                    'reason' => $validated['reason'],
                    'issue_date' => $validated['issue_date'],
                    'notes' => $validated['notes'],
                    'user_id' => auth()->id(),
                    'status' => 'pending',
                    'evidence_files' => !empty($evidenceFiles) ? $evidenceFiles : null,
                ]);

                // Reserve stock for this RMA
                try {
                    $stockService->deductStock(
                        $productId,
                        $quantity,
                        'rma_pending',
                        $rma->id,
                        "RMA Created: {$rmaCode} - Stock reserved for return process"
                    );
                } catch (\Exception $stockError) {
                    $errors[] = "{$productTitle}: Gagal reserve stock - {$stockError->getMessage()}";
                    $rma->delete();
                    continue;
                }

                $createdRMAs[] = $rma;
            }

            if (empty($createdRMAs)) {
                DB::rollBack();
                return response()->json([
                    'message' => 'Tidak ada RMA yang berhasil dibuat. Error: ' . implode('; ', $errors)
                ], 422);
            }

            DB::commit();

            $message = count($createdRMAs) . ' RMA berhasil dibuat';
            if (!empty($errors)) {
                $message .= '. Beberapa produk gagal: ' . implode('; ', $errors);
            }

            return response()->json([
                'message' => $message,
                'data' => $createdRMAs,
                'errors' => $errors,
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    public function show($id)
    {
        $rma = RMA::with(['warranty', 'product', 'user'])->findOrFail($id);
        return response()->json($rma);
    }

    public function update(Request $request, $id)
    {
        $rma = RMA::findOrFail($id);

        $validated = $request->validate([
            'status' => 'sometimes|in:pending,approved,rejected,received,processed,completed',
            'resolution' => 'nullable|in:repair,replace,refund',
            'received_date' => 'nullable|date',
            'notes' => 'nullable|string',
        ]);

        DB::beginTransaction();
        try {
            $oldStatus = $rma->status;
            $newStatus = $validated['status'] ?? $oldStatus;

            if ($oldStatus !== 'rejected' && $newStatus === 'rejected') {
                $stockService = new \App\Services\StockSyncService();
                $stockService->addStock(
                    $rma->product_id,
                    $rma->quantity,
                    'rma_rejected',
                    $rma->id,
                    "RMA Rejected: {$rma->rma_code} - Stock returned to available"
                );
            }

            $rma->update($validated);

            DB::commit();
            return response()->json($rma->load(['warranty', 'product', 'user']));
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    public function destroy($id)
    {
        $rma = RMA::findOrFail($id);

        DB::beginTransaction();
        try {
            $stockService = new \App\Services\StockSyncService();

            if (!in_array($rma->status, ['rejected', 'completed'])) {
                $stockService->addStock(
                    $rma->product_id,
                    $rma->quantity,
                    'rma_deleted',
                    $rma->id,
                    "RMA Deleted: {$rma->rma_code} - Stock returned to available"
                );
            }

            if ($rma->evidence_files) {
                foreach ($rma->evidence_files as $file) {
                    Storage::disk('public')->delete($file['path']);
                }
            }

            $rma->delete();

            DB::commit();
            return response()->json(['message' => 'RMA deleted successfully']);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    /**
     * Mark RMA as received
     */
    public function markReceived(Request $request, $id)
    {
        $rma = RMA::findOrFail($id);
        
        $validated = $request->validate([
            'condition' => 'required|string|max:255',
            'condition_level' => 'required|in:working,repairable,damaged_total,parts_only'
        ]);
        
        DB::beginTransaction();
        try {
            $stockService = new \App\Services\StockSyncService();
            $rmaWarehouse = \App\Models\Warehouse::where('name', 'like', '%RMA%')->orWhere('code', 'RMA')->first();
            
            $conditionLevel = $validated['condition_level'];
            
            if ($conditionLevel === 'working') {
                $stockService->addStock(
                    $rma->product_id,
                    $rma->quantity,
                    'rma_return_working',
                    $rma->id,
                    "RMA Received: {$rma->rma_code} - Working (Returned to Stock)"
                );
            } elseif ($conditionLevel === 'repairable') {
                if ($rmaWarehouse) {
                    $stockService->addStock(
                        $rma->product_id,
                        $rma->quantity,
                        'rma_return_repairable',
                        $rma->id,
                        "RMA Received: {$rma->rma_code} - Repairable (To RMA Warehouse)",
                        $rmaWarehouse->id
                    );
                }
            }
            
            $rma->update([
                'status' => 'received',
                'received_date' => now(),
                'condition' => $validated['condition'],
                'condition_level' => $conditionLevel,
                'rma_warehouse_id' => ($conditionLevel === 'repairable' && $rmaWarehouse) ? $rmaWarehouse->id : null
            ]);

            DB::commit();
            return response()->json($rma->load(['warranty', 'product', 'user']));
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    /**
     * Process RMA with resolution
     */
    public function process(Request $request, $id)
    {
        $rma = RMA::findOrFail($id);

        $validated = $request->validate([
            'resolution' => 'required|in:repair,replace,refund',
            'notes' => 'nullable|string',
        ]);

        DB::beginTransaction();
        try {
            $stockService = new \App\Services\StockSyncService();

            switch ($validated['resolution']) {
                case 'repair':
                    if ($rma->rma_warehouse_id && $rma->condition_level === 'repairable') {
                        $stockService->deductStock(
                            $rma->product_id,
                            $rma->quantity,
                            'rma_repair',
                            $rma->id,
                            "RMA Repair: {$rma->rma_code} - Sent for repair",
                            $rma->rma_warehouse_id
                        );
                    }
                    
                    $rma->update([
                        'status' => 'processed',
                        'resolution' => 'repair',
                        'notes' => $validated['notes'] ?? $rma->notes,
                    ]);
                    break;

                case 'replace':
                    if (!$stockService->hasStock($rma->product_id, $rma->quantity)) {
                        throw new \Exception("Stock tidak mencukupi untuk replacement. Product: {$rma->product->title}");
                    }

                    $stockService->deductStock(
                        $rma->product_id,
                        $rma->quantity,
                        'rma_replacement',
                        $rma->id,
                        "RMA Replacement: {$rma->rma_code} - Product sent to customer"
                    );

                    if ($rma->rma_warehouse_id && $rma->condition_level === 'repairable') {
                        $stockService->deductStock(
                            $rma->product_id,
                            $rma->quantity,
                            'rma_replacement_remove',
                            $rma->id,
                            "RMA Replacement: {$rma->rma_code} - Removed damaged unit",
                            $rma->rma_warehouse_id
                        );
                    }

                    $rma->update([
                        'status' => 'processed',
                        'resolution' => 'replace',
                        'notes' => $validated['notes'] ?? $rma->notes,
                    ]);
                    break;

                case 'refund':
                    if ($rma->condition_level === 'working') {
                        $stockService->addStock(
                            $rma->product_id,
                            $rma->quantity,
                            'rma_refund_restocked',
                            $rma->id,
                            "RMA Refund: {$rma->rma_code} - Working item returned to stock"
                        );
                    }

                    $rma->update([
                        'status' => 'processed',
                        'resolution' => 'refund',
                        'notes' => $validated['notes'] ?? $rma->notes,
                    ]);
                    break;
            }

            DB::commit();
            return response()->json($rma->load(['warranty', 'product', 'user']));
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    /**
     * Get sales with active warranty or MSA for RMA creation
     * Includes warranty serial numbers for SN matching
     */
    public function getSalesWithWarranty(Request $request)
    {
        try {
            // Get sales that have warranties that haven't expired
            $sales = Sale::with(['items.product', 'warranties'])
                ->whereHas('warranties', function ($q) {
                    $q->where(function ($w) {
                        $w->whereNull('end_date')
                          ->orWhere('end_date', '>=', now());
                    });
                })
                ->orWhereHas('items', function ($q) {
                    // Also include sales whose items have active MSA
                    $q->whereHas('product', function ($p) {
                        $p->whereIn('id', function ($subquery) {
                            $subquery->select('product_id')
                                ->from('msa_projects')
                                ->where('status', 'active');
                        });
                    });
                })
                ->orderBy('sale_date', 'desc')
                ->limit(100)
                ->get();

            return response()->json($sales);
        } catch (\Exception $e) {
            \Log::error('getSalesWithWarranty error: ' . $e->getMessage());
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    /**
     * Check RMA eligibility for a specific sale/product
     * Also returns warranty serial number and available quantity
     */
    public function checkEligibility(Request $request)
    {
        $validated = $request->validate([
            'sale_id' => 'required|exists:sales,id',
            'product_id' => 'required|exists:products,id',
            'sale_item_id' => 'nullable|exists:sale_items,id',
        ]);

        $eligibility = RMA::validateEligibility(
            $validated['sale_id'],
            $validated['sale_item_id'] ?? null,
            $validated['product_id']
        );

        // Get all warranty serial numbers for this sale + product
        $warranties = Warranty::where('sale_id', $validated['sale_id'])
            ->where('product_id', $validated['product_id'])
            ->where(function ($q) {
                $q->whereNull('end_date')
                  ->orWhere('end_date', '>=', now());
            })
            ->pluck('serial_number')
            ->filter()
            ->toArray();

        if (!empty($warranties)) {
            $eligibility['warranty_serial_numbers'] = $warranties;
            $eligibility['warranty_serial_number'] = $warranties[0];
        }

        // Add available quantity info (sale item qty minus existing RMAs)
        $saleItem = SaleItem::where('sale_id', $validated['sale_id'])
            ->where('product_id', $validated['product_id'])
            ->first();

        if ($saleItem) {
            $existingRmaQty = RMA::where('sale_id', $validated['sale_id'])
                ->where('product_id', $validated['product_id'])
                ->whereNotIn('status', ['rejected'])
                ->sum('quantity');

            $eligibility['sale_item_quantity'] = $saleItem->quantity;
            $eligibility['existing_rma_quantity'] = (int) $existingRmaQty;
            $eligibility['available_quantity'] = $saleItem->quantity - $existingRmaQty;
        }

        return response()->json($eligibility);
    }

    /**
     * Get evidence files for an RMA
     */
    public function getEvidence($id)
    {
        $rma = RMA::findOrFail($id);
        
        $evidence = $rma->evidence_files ?? [];
        
        // Add full URL to each file
        $evidence = array_map(function ($file) {
            $file['url'] = Storage::disk('public')->url($file['path']);
            return $file;
        }, $evidence);

        return response()->json($evidence);
    }
}
