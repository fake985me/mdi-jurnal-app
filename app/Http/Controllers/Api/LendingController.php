<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Lending;
use App\Models\LendingReturn;
use App\Models\CurrentStock;
use App\Models\StockTransaction;
use App\Services\StockSyncService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LendingController extends Controller
{
    public function index(Request $request)
    {
        try {
            $query = Lending::with(['product', 'user']);

            // Filter by status
            if ($request->has('status') && !empty($request->status)) {
                $query->where('status', $request->status);
            }

            // Search
            if ($request->has('search') && !empty($request->search)) {
                $search = $request->search;
                $query->where(function($q) use ($search) {
                    $q->where('borrower_name', 'like', "%{$search}%")
                      ->orWhere('lending_code', 'like', "%{$search}%");
                });
            }

            $lendings = $query->orderBy('created_at', 'desc')
                ->paginate($request->per_page ?? 15);

            return response()->json($lendings);
        } catch (\Exception $e) {
            \Log::error('Lendings API Error: ' . $e->getMessage());
            return response()->json([
                'message' => 'Error loading lendings',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'lending_code' => 'required|unique:lendings,lending_code',
            'product_id' => 'required|exists:products,id',
            'borrower_name' => 'required|string|max:255',
            'borrower_contact' => 'nullable|string',
            'quantity' => 'required|integer|min:1',
            'lending_date' => 'required|date',
            'expected_return_date' => 'required|date|after:lending_date',
            'notes' => 'nullable|string',
        ]);

        DB::beginTransaction();
        try {
            $stockService = new StockSyncService();
            
            // Check stock availability and deduct
            $stockService->deductStock(
                $validated['product_id'],
                $validated['quantity'],
                'lending',
                0, // Will be updated after lending created
                "Lending to {$validated['borrower_name']}"
            );

            // Create lending
            $lending = Lending::create([
                ...$validated,
                'user_id' => auth()->id(),
                'status' => 'borrowed',
            ]);
            
            // Update the stock transaction with actual lending ID
            $transaction = StockTransaction::where('reference_type', 'lending')
                ->where('product_id', $validated['product_id'])
                ->whereNull('reference_id')
                ->latest()
                ->first();
                
            if ($transaction) {
                $transaction->update(['reference_id' => $lending->id]);
            }

            DB::commit();
            return response()->json($lending->load(['product', 'user']), 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    public function show($id)
    {
        $lending = Lending::with(['product', 'user', 'returns'])->findOrFail($id);
        return response()->json($lending);
    }

    public function processReturn(Request $request, $id)
    {
        $lending = Lending::findOrFail($id);

        if ($lending->status === 'returned') {
            return response()->json(['message' => 'Lending already returned'], 422);
        }

        $validated = $request->validate([
            'return_date' => 'required|date',
            'quantity_returned' => 'required|integer|min:1|max:' . $lending->quantity,
            'condition' => 'required|in:good,damaged,lost',
            'notes' => 'nullable|string',
        ]);

        DB::beginTransaction();
        try {
            $stockService = new StockSyncService();
            
            // Create return record
            LendingReturn::create([
                'lending_id' => $lending->id,
                'return_date' => $validated['return_date'],
                'quantity_returned' => $validated['quantity_returned'],
                'condition' => $validated['condition'],
                'notes' => $validated['notes'] ?? null,
            ]);

            // Update lending status
            $lending->status = 'returned';
            $lending->actual_return_date = $validated['return_date'];
            $lending->save();

            // Add stock back if condition is good
            if ($validated['condition'] === 'good') {
                $stockService->addStock(
                    $lending->product_id,
                    $validated['quantity_returned'],
                    'lending_return',
                    $lending->id,
                    "Return from lending #{$lending->lending_code} - good condition"
                );
            }

            DB::commit();
            return response()->json($lending->load(['product', 'user', 'returns']));
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    public function destroy($id)
    {
        $lending = Lending::findOrFail($id);

        if ($lending->status === 'borrowed') {
            return response()->json(['message' => 'Cannot delete active lending'], 422);
        }

        $lending->delete();
        return response()->json(['message' => 'Lending deleted successfully']);
    }
}
