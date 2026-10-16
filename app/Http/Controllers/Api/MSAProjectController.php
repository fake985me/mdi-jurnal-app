<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MSAProject;
use App\Services\StockSyncService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MSAProjectController extends Controller
{
    public function __construct(private StockSyncService $stockService) {}
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
            'serial_numbers' => 'nullable|array',
            'serial_numbers.*' => 'required|string|max:255|distinct:ignore_case',
        ]);

        if (!empty($validated['serial_numbers']) && count($validated['serial_numbers']) !== (int) $validated['quantity']) {
            return response()->json(['message' => 'Jumlah SN harus sesuai quantity.'], 422);
        }
        return DB::transaction(function () use ($validated) {
        $msaCode = 'MSA-' . date('Ymd') . '-' . strtoupper(\Illuminate\Support\Str::random(6));

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
            'serial_numbers' => $validated['serial_numbers'] ?? null,
        ]);
        return response()->json($msa->load(['project', 'product', 'user']), 201);
        });
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
        if (!in_array($msa->status, ['pending', 'in_repair'], true)) {
            return response()->json(['message' => 'MSA sudah diselesaikan atau diganti.'], 422);
        }
        
        $validated = $request->validate([
            'condition' => 'required|string|max:255'
        ]);

        $msa->update([
            'status' => 'returned',
            'resolved_date' => now(),
            'resolution' => 'repaired',
            'condition' => $validated['condition'],
        ]);
        \App\Models\ReturnedItem::where('source_type', 'msa')->where('source_id', $msa->id)
            ->whereIn('condition', ['defective', 'unrepairable'])->update(['condition' => 'returned', 'available_quantity' => 0]);

        return response()->json($msa->load(['project', 'product', 'user']));
    }

    /**
     * Replace item - deduct new stock
     */
    public function replaceItem(Request $request, $id)
    {
        $msa = MSAProject::findOrFail($id);
        
        $validated = $request->validate([
            'condition' => 'required|string|max:255',
            'serial_numbers' => 'required|array|min:1',
            'serial_numbers.*' => 'required|string|max:255|distinct:ignore_case',
            'replacement_serial_numbers' => 'required|array|min:1',
            'replacement_serial_numbers.*' => 'required|string|max:255|distinct:ignore_case',
        ]);

        DB::beginTransaction();
        try {
            $msa = MSAProject::whereKey($id)->lockForUpdate()->firstOrFail();
            if (!in_array($msa->status, ['pending', 'in_repair'], true)) throw new \RuntimeException('MSA sudah diproses.');
            $old = array_map('trim', $validated['serial_numbers']);
            $new = array_map('trim', $validated['replacement_serial_numbers']);
            if (count($old) !== $msa->quantity || count($new) !== $msa->quantity || array_intersect(array_map('strtolower', $old), array_map('strtolower', $new))) throw new \RuntimeException('Isi SN lama dan SN baru yang berbeda sesuai quantity.');
            if ($msa->serial_numbers && array_map('strtolower', $msa->serial_numbers) !== array_map('strtolower', $old)) throw new \RuntimeException('SN retur tidak sesuai laporan MSA.');
            if ($msa->project_investment_id) {
                foreach (\App\Models\ProjectInvestmentItem::where('project_investment_id', $msa->project_investment_id)->where('product_id', $msa->product_id)->get() as $projectItem) {
                    if (array_intersect(array_map('strtolower', $new), array_map('strtolower', $projectItem->serial_numbers ?? []))) throw new \RuntimeException('SN pengganti sudah digunakan pada proyek ini.');
                }
                $item = \App\Models\ProjectInvestmentItem::where('project_investment_id', $msa->project_investment_id)->where('product_id', $msa->product_id)->lockForUpdate()->get()->first(fn ($item) => !array_diff(array_map('strtolower', $old), array_map('strtolower', $item->serial_numbers ?? [])));
                if (!$item) throw new \RuntimeException('Catat SN lama pada detail proyek sebelum penggantian MSA.');
                $mapping = array_combine(array_map('strtolower', $old), $new);
                $item->update(['serial_numbers' => array_map(fn ($sn) => $mapping[strtolower($sn)] ?? $sn, $item->serial_numbers)]);
                \App\Models\ProjectItemChange::create(['project_investment_id' => $msa->project_investment_id, 'project_investment_item_id' => $item->id, 'type' => 'msa_replace', 'quantity' => $msa->quantity, 'old_serial_numbers' => $old, 'new_serial_numbers' => $new, 'notes' => $validated['condition'], 'user_id' => auth()->id()]);
            }
            app(\App\Services\ReturnedInventoryService::class)->register('msa', $msa->id, $msa->msa_code, $msa->product_id, $msa->quantity, $old, $validated['condition']);
            $stockService = $this->stockService;
            
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
                'serial_numbers' => $old,
                'replacement_serial_numbers' => $new,
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
