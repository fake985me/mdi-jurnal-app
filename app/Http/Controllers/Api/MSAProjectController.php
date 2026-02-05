<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MSAProject;
use App\Services\StockSyncService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MSAProjectController extends Controller
{
    public function index(Request $request)
    {
        try {
            $query = MSAProject::with(['project', 'product', 'user']);

            if ($request->has('status') && !empty($request->status)) {
                $query->where('status', $request->status);
            }

            if ($request->has('search') && !empty($request->search)) {
                $search = $request->search;
                $query->where(function($q) use ($search) {
                    $q->where('msa_code', 'like', "%{$search}%")
                      ->orWhereHas('product', function($pq) use ($search) {
                          $pq->where('title', 'like', "%{$search}%");
                      });
                });
            }

            $msas = $query->orderBy('created_at', 'desc')
                ->paginate($request->per_page ?? 15);

            return response()->json($msas);
        } catch (\Exception $e) {
            \Log::error('MSA Project API Error: ' . $e->getMessage());
            return response()->json(['message' => 'Error loading MSA projects', 'error' => $e->getMessage()], 500);
        }
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'project_investment_id' => 'nullable|exists:project_investments,id',
            'product_id' => 'required|exists:products,id',
            'quantity' => 'required|integer|min:1',
            'issue_type' => 'required|in:damaged,defective,malfunction,other',
            'issue_description' => 'nullable|string',
            'reported_date' => 'required|date',
        ]);

        $msaCode = 'MSA-' . date('Ymd') . '-' . str_pad(MSAProject::whereDate('created_at', today())->count() + 1, 4, '0', STR_PAD_LEFT);

        $msa = MSAProject::create([
            'msa_code' => $msaCode,
            'project_investment_id' => $validated['project_investment_id'] ?? null,
            'product_id' => $validated['product_id'],
            'quantity' => $validated['quantity'],
            'issue_type' => $validated['issue_type'],
            'issue_description' => $validated['issue_description'] ?? null,
            'reported_date' => $validated['reported_date'],
            'status' => 'pending',
            'user_id' => auth()->id(),
        ]);

        return response()->json($msa->load(['project', 'product', 'user']), 201);
    }

    public function show($id)
    {
        $msa = MSAProject::with(['project', 'product', 'user'])->findOrFail($id);
        return response()->json($msa);
    }

    public function update(Request $request, $id)
    {
        $msa = MSAProject::findOrFail($id);

        $validated = $request->validate([
            'issue_type' => 'sometimes|in:damaged,defective,malfunction,other',
            'issue_description' => 'nullable|string',
            'status' => 'sometimes|in:pending,in_repair,returned,replaced,closed',
        ]);

        $msa->update($validated);
        return response()->json($msa->load(['project', 'product', 'user']));
    }

    public function destroy($id)
    {
        $msa = MSAProject::findOrFail($id);
        $msa->delete();
        return response()->json(['message' => 'MSA deleted successfully']);
    }

    /**
     * Mark MSA as in repair
     */
    public function startRepair($id)
    {
        $msa = MSAProject::findOrFail($id);
        
        if ($msa->status !== 'pending') {
            return response()->json(['message' => 'Only pending MSAs can be sent for repair'], 422);
        }

        $msa->update(['status' => 'in_repair']);
        return response()->json($msa->load(['project', 'product', 'user']));
    }

    /**
     * Mark MSA item as returned (repaired)
     */
    public function markReturned(Request $request, $id)
    {
        $msa = MSAProject::findOrFail($id);
        
        $validated = $request->validate([
            'condition' => 'required|string|max:255'
        ]);

        $msa->update([
            'status' => 'returned',
            'resolved_date' => now(),
            'resolution' => 'repaired',
            'condition' => $validated['condition'],
        ]);

        return response()->json($msa->load(['project', 'product', 'user']));
    }

    /**
     * Replace item - deduct new stock
     */
    public function replaceItem(Request $request, $id)
    {
        $msa = MSAProject::findOrFail($id);
        
        $validated = $request->validate([
            'condition' => 'required|string|max:255'
        ]);

        DB::beginTransaction();
        try {
            $stockService = new StockSyncService();
            
            // Deduct replacement item from stock
            $stockService->deductStock(
                $msa->product_id,
                $msa->quantity,
                'msa_replacement',
                $msa->id,
                "MSA Replacement: {$msa->msa_code} - Old item condition: {$validated['condition']}"
            );

            $msa->update([
                'status' => 'replaced',
                'resolved_date' => now(),
                'resolution' => 'replaced',
                'condition' => $validated['condition'],
            ]);

            DB::commit();
            return response()->json($msa->load(['project', 'product', 'user']));
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    /**
     * Close MSA
     */
    public function close($id)
    {
        $msa = MSAProject::findOrFail($id);
        
        if (!in_array($msa->status, ['returned', 'replaced'])) {
            return response()->json(['message' => 'Only returned or replaced MSAs can be closed'], 422);
        }

        $msa->update(['status' => 'closed']);
        return response()->json($msa->load(['project', 'product', 'user']));
    }
}
