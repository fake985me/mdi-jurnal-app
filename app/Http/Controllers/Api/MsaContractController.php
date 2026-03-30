<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MsaContract;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MsaContractController extends Controller
{
    public function index(Request $request)
    {
        $query = MsaContract::with('project');

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where('msa_code', 'like', "%{$search}%")
                ->orWhereHas('project', function ($q) use ($search) {
                    $q->where('project_name', 'like', "%{$search}%")
                      ->orWhere('project_code', 'like', "%{$search}%");
                });
        }

        return response()->json($query->orderBy('created_at', 'desc')->paginate($request->per_page ?? 15));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'project_investment_id' => 'nullable|exists:project_investments,id',
            'msa_code' => 'nullable|unique:msa_contracts,msa_code',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'sharing_profit_rate' => 'nullable|numeric|min:0|max:100',
            'status' => 'nullable|in:draft,active,expired,cancelled',
            'notes' => 'nullable|string',
        ]);

        $msaCode = $validated['msa_code'] ?? MsaContract::generateMsaCode();

        $msa = MsaContract::create([
            'project_investment_id' => $validated['project_investment_id'] ?? null,
            'msa_code' => $msaCode,
            'start_date' => $validated['start_date'] ?? null,
            'end_date' => $validated['end_date'] ?? null,
            'sharing_profit_rate' => $validated['sharing_profit_rate'] ?? null,
            'status' => $validated['status'] ?? 'active',
            'notes' => $validated['notes'] ?? null,
            'user_id' => auth()->id(),
        ]);

        return response()->json($msa->load('project'), 201);
    }

    public function show(MsaContract $msaContract)
    {
        return response()->json($msaContract->load('project'));
    }

    public function update(Request $request, MsaContract $msaContract)
    {
        $validated = $request->validate([
            'msa_code' => 'nullable|unique:msa_contracts,msa_code,' . $msaContract->id,
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'sharing_profit_rate' => 'nullable|numeric|min:0|max:100',
            'status' => 'nullable|in:draft,active,expired,cancelled',
            'notes' => 'nullable|string',
        ]);

        $msaContract->update($validated);

        return response()->json($msaContract->load('project'));
    }

    public function destroy(MsaContract $msaContract)
    {
        $msaContract->delete();
        return response()->json(['message' => 'MSA contract deleted successfully']);
    }
}
