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

            // Validate serial number against warranty
            if (isset($eligibility['warranty_id'])) {
                $warranty = Warranty::find($eligibility['warranty_id']);
                if ($warranty && $warranty->serial_number) {
                    $inputSN = $validated['serial_number'] ?? '';
                    if (strtolower(trim($inputSN)) !== strtolower(trim($warranty->serial_number))) {
                        return response()->json([
                            'message' => 'Serial Number tidak cocok dengan data warranty. SN yang terdaftar: ' . $warranty->serial_number
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

            foreach ($items as $index => $item) {
                $productId = $item['product_id'] ?? null;
                $serialNumber = $item['serial_number'] ?? '';
                $quantity = $item['quantity'] ?? 1;
                $productTitle = $item['product_title'] ?? "Product #{$productId}";

                if (!$productId) {
                    $errors[] = "Item #{$index}: Product ID tidak valid";
                    continue;
                }

                // Validate eligibility
                $eligibility = RMA::validateEligibility(
                    $validated['sale_id'],
                    null,
                    $productId
                );

                if (!$eligibility['valid']) {
                    $errors[] = "{$productTitle}: {$eligibility['reason']}";
                    continue;
                }

                // Validate serial number against warranty
                $warrantyId = $eligibility['warranty_id'] ?? null;
                $msaProjectId = $eligibility['msa_project_id'] ?? null;

                if ($warrantyId) {
                    $warranty = Warranty::find($warrantyId);
                    if ($warranty && $warranty->serial_number) {
                        if (strtolower(trim($serialNumber)) !== strtolower(trim($warranty->serial_number))) {
                            $errors[] = "{$productTitle}: SN tidak cocok. SN terdaftar: {$warranty->serial_number}";
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

                // Generate unique RMA code
                $rmaCode = 'RMA-' . date('Ymd') . '-' . str_pad(
                    RMA::whereDate('created_at', today())->count() + count($createdRMAs) + 1,
                    4, '0', STR_PAD_LEFT
                );

                $rma = RMA::create([
                    'rma_code' => $rmaCode,
                    'sale_id' => $validated['sale_id'],
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

        $rma->update($validated);

        return response()->json($rma->load(['warranty', 'product', 'user']));
    }

    public function destroy($id)
    {
        $rma = RMA::findOrFail($id);

        // Delete associated evidence files
        if ($rma->evidence_files) {
            foreach ($rma->evidence_files as $file) {
                Storage::disk('public')->delete($file['path']);
            }
        }

        $rma->delete();

        return response()->json(['message' => 'RMA deleted successfully']);
    }

    /**
     * Mark RMA as received
     */
    public function markReceived(Request $request, $id)
    {
        $rma = RMA::findOrFail($id);
        
        // Validate condition
        $validated = $request->validate([
            'condition' => 'required|string|max:255'
        ]);
        
        DB::beginTransaction();
        try {
            $stockService = new \App\Services\StockSyncService();
            
            // Check if condition is "working" - if yes, add back to stock
            // Otherwise, deduct from stock (damaged/broken/parts_only/custom)
            if (strtolower($validated['condition']) === 'working') {
                // Item is functional, add back to available stock
                $stockService->addStock(
                    $rma->product_id,
                    $rma->quantity,
                    'rma_return_working',
                    $rma->id,
                    "RMA Received: {$rma->rma_code} - Condition: Working (Returned to Stock)"
                );
            } else {
                // Item is damaged/broken/etc, deduct from available stock
                $stockService->deductStock(
                    $rma->product_id,
                    $rma->quantity,
                    'rma_return_defective',
                    $rma->id,
                    "RMA Received: {$rma->rma_code} - Condition: {$validated['condition']} (Removed from Stock)"
                );
            }
            
            // Update RMA status and condition
            $rma->update([
                'status' => 'received',
                'received_date' => now(),
                'condition' => $validated['condition']
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
        ]);

        $rma->update([
            'status' => 'processed',
            'resolution' => $validated['resolution'],
        ]);

        // TODO: Handle resolution actions
        // - repair: track repair process
        // - replace: create new delivery
        // - refund: process refund

        return response()->json($rma->load(['warranty', 'product', 'user']));
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

        // Add warranty serial number info
        if (isset($eligibility['warranty_id'])) {
            $warranty = Warranty::find($eligibility['warranty_id']);
            $eligibility['warranty_serial_number'] = $warranty?->serial_number;
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
