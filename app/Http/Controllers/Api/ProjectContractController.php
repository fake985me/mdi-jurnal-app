<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ProjectContract;
use App\Models\ProjectInvestment;
use App\Models\Payment;
use App\Models\Warehouse;
use App\Services\StockTransferService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProjectContractController extends Controller
{
    public function index(Request $request)
    {
        $query = ProjectContract::with(['project', 'project.warehouse']);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where('contract_number', 'like', "%{$search}%")
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
            'project_investment_id' => 'required|exists:project_investments,id|unique:project_contracts,project_investment_id',
            'contract_number' => 'nullable|unique:project_contracts,contract_number',
            'contract_date' => 'required|date',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'contract_value' => 'nullable|numeric|min:0',
            'status' => 'nullable|in:draft,active,completed,cancelled',
            'notes' => 'nullable|string',
            'transfer_stock' => 'sometimes|boolean',
        ]);

        DB::beginTransaction();
        try {
            $project = ProjectInvestment::with('items.product')->findOrFail($validated['project_investment_id']);
            $contractNumber = $validated['contract_number'] ?? ProjectContract::generateContractNumber();

            $contract = ProjectContract::create([
                'project_investment_id' => $project->id,
                'contract_number' => $contractNumber,
                'contract_date' => $validated['contract_date'],
                'start_date' => $validated['start_date'] ?? null,
                'end_date' => $validated['end_date'] ?? null,
                'contract_value' => $validated['contract_value'] ?? 0,
                'status' => $validated['status'] ?? 'active',
                'notes' => $validated['notes'] ?? null,
                'user_id' => auth()->id(),
            ]);

            // Ensure project warehouse exists (Invest Warehouse)
            if (!$project->warehouse_id) {
                $warehouse = Warehouse::create([
                    'code' => 'WH-' . $project->project_code,
                    'name' => $project->project_name,
                    'description' => 'Gudang khusus untuk project: ' . $project->project_name,
                    'is_active' => true,
                    'is_default' => false,
                ]);

                $project->warehouse_id = $warehouse->id;
                $project->saveQuietly();
            }

            // Optional: transfer stock from default to project warehouse
            $transferStock = $validated['transfer_stock'] ?? true;
            if ($transferStock) {
                $defaultWarehouseId = Warehouse::getDefault()?->id;
                if ($defaultWarehouseId && $project->warehouse_id) {
                    $transferService = new StockTransferService();
                    foreach ($project->items as $item) {
                        if (!$item->stock_deducted) {
                            $transferService->transferNow(
                                $defaultWarehouseId,
                                $project->warehouse_id,
                                $item->product_id,
                                $item->quantity,
                                "Contract allocation: {$contract->contract_number}",
                                auth()->id()
                            );
                            $item->update(['stock_deducted' => true]);
                        }
                    }
                }
            }

            // Auto-create payment record if contract has a value
            $contractValue = (float) ($contract->contract_value ?? 0);
            if ($contractValue > 0) {
                Payment::create([
                    'payable_type' => ProjectContract::class,
                    'payable_id' => $contract->id,
                    'payment_type' => 'full',
                    'amount' => $contractValue,
                    'payment_date' => $validated['contract_date'],
                    'method' => null,
                    'status' => 'unpaid',
                    'reference_number' => $contract->contract_number,
                    'notes' => "Auto-created from Contract #{$contract->contract_number}",
                    'user_id' => auth()->id(),
                ]);
            }

            DB::commit();
            return response()->json($contract->load(['project', 'project.warehouse']), 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    public function show(ProjectContract $projectContract)
    {
        return response()->json($projectContract->load(['project', 'project.warehouse']));
    }

    public function update(Request $request, ProjectContract $projectContract)
    {
        $validated = $request->validate([
            'contract_number' => 'nullable|unique:project_contracts,contract_number,' . $projectContract->id,
            'contract_date' => 'nullable|date',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'contract_value' => 'nullable|numeric|min:0',
            'status' => 'nullable|in:draft,active,completed,cancelled',
            'notes' => 'nullable|string',
        ]);

        $projectContract->update($validated);

        return response()->json($projectContract->load(['project', 'project.warehouse']));
    }

    public function destroy(ProjectContract $projectContract)
    {
        $projectContract->delete();
        return response()->json(['message' => 'Contract deleted successfully']);
    }
}
