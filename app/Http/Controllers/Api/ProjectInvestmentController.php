<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ProjectInvestment;
use App\Models\ProjectInvestmentItem;
use App\Services\StockSyncService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class ProjectInvestmentController extends Controller
{
    public function index(Request $request)
    {
        try {
            $query = ProjectInvestment::with(['items.product', 'user', 'approver']);

            if ($request->has('status') && !empty($request->status)) {
                $query->where('status', $request->status);
            }

            if ($request->has('search') && !empty($request->search)) {
                $search = $request->search;
                $query->where(function($q) use ($search) {
                    $q->where('project_name', 'like', "%{$search}%")
                      ->orWhere('project_code', 'like', "%{$search}%")
                      ->orWhere('client_name', 'like', "%{$search}%");
                });
            }

            $projects = $query->orderBy('created_at', 'desc')
                ->paginate($request->per_page ?? 15);

            return response()->json($projects);
        } catch (\Exception $e) {
            \Log::error('Project Investment API Error: ' . $e->getMessage());
            return response()->json(['message' => 'Error loading projects', 'error' => $e->getMessage()], 500);
        }
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'project_name' => 'required|string|max:255',
            'client_name' => 'required|string|max:255',
            'client_contact' => 'nullable|string',
            'project_location' => 'nullable|string|max:500',
            'pic_name' => 'nullable|string|max:255',
            'pic_phone' => 'nullable|string|max:50',
            'description' => 'nullable|string',
            'scope_of_work' => 'nullable|string',
            'start_date' => 'required|date',
            'expected_end_date' => 'nullable|date|after_or_equal:start_date',
            'duration_days' => 'nullable|integer|min:1',
            'po_number' => 'nullable|string|max:100',
            'po_value' => 'nullable|numeric|min:0',
            'po_date' => 'nullable|date',
            'items' => 'array',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.unit_price' => 'required|numeric|min:0',
        ]);

        DB::beginTransaction();
        try {
            $projectCode = 'PRJ-' . date('Ymd') . '-' . str_pad(ProjectInvestment::whereDate('created_at', today())->count() + 1, 4, '0', STR_PAD_LEFT);

            $project = ProjectInvestment::create([
                'project_code' => $projectCode,
                'po_number' => $validated['po_number'] ?? null,
                'po_value' => $validated['po_value'] ?? 0,
                'po_date' => $validated['po_date'] ?? null,
                'project_name' => $validated['project_name'],
                'client_name' => $validated['client_name'],
                'client_contact' => $validated['client_contact'] ?? null,
                'project_location' => $validated['project_location'] ?? null,
                'pic_name' => $validated['pic_name'] ?? null,
                'pic_phone' => $validated['pic_phone'] ?? null,
                'description' => $validated['description'] ?? null,
                'scope_of_work' => $validated['scope_of_work'] ?? null,
                'start_date' => $validated['start_date'],
                'expected_end_date' => $validated['expected_end_date'] ?? null,
                'duration_days' => $validated['duration_days'] ?? null,
                'status' => 'pending',
                'delivery_progress' => 0,
                'installation_progress' => 0,
                'user_id' => auth()->id(),
            ]);

            // Add items (stock not deducted yet - pending approval)
            if (!empty($validated['items'])) {
                foreach ($validated['items'] as $item) {
                    ProjectInvestmentItem::create([
                        'project_investment_id' => $project->id,
                        'product_id' => $item['product_id'],
                        'quantity' => $item['quantity'],
                        'unit_price' => $item['unit_price'],
                        'total_price' => $item['quantity'] * $item['unit_price'],
                        'stock_deducted' => false,
                    ]);
                }
                $project->calculateTotalValue();
            }

            DB::commit();
            return response()->json($project->load(['items.product', 'user']), 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }


    public function show($id)
    {
        $project = ProjectInvestment::with([
            'items.product', 
            'materials', 
            'tasks.assignee', 
            'progressLogs.updatedBy', 
            'user', 
            'approver'
        ])->findOrFail($id);
        return response()->json($project);
    }

    public function update(Request $request, $id)
    {
        $project = ProjectInvestment::findOrFail($id);

        $validated = $request->validate([
            'project_name' => 'sometimes|string|max:255',
            'client_name' => 'sometimes|string|max:255',
            'client_contact' => 'nullable|string',
            'project_location' => 'nullable|string|max:500',
            'pic_name' => 'nullable|string|max:255',
            'pic_phone' => 'nullable|string|max:50',
            'description' => 'nullable|string',
            'scope_of_work' => 'nullable|string',
            'expected_end_date' => 'nullable|date',
            'duration_days' => 'nullable|integer|min:1',
            'po_number' => 'nullable|string|max:100',
            'po_value' => 'nullable|numeric|min:0',
            'po_date' => 'nullable|date',
        ]);

        $project->update($validated);
        return response()->json($project->load(['items.product', 'materials', 'user']));
    }

    /**
     * Update project progress (delivery/installation)
     */
    public function updateProgress(Request $request, $id)
    {
        $project = ProjectInvestment::findOrFail($id);

        $validated = $request->validate([
            'delivery_progress' => 'nullable|integer|min:0|max:100',
            'installation_progress' => 'nullable|integer|min:0|max:100',
            'notes' => 'nullable|string',
        ]);

        if (isset($validated['delivery_progress'])) {
            $project->updateProgress('delivery_progress', $validated['delivery_progress'], $validated['notes'] ?? null);
        }

        if (isset($validated['installation_progress'])) {
            $project->updateProgress('installation_progress', $validated['installation_progress'], $validated['notes'] ?? null);
        }

        return response()->json($project->load(['progressLogs.updatedBy']));
    }

    /**
     * Get progress history
     */
    public function progressHistory($id)
    {
        $project = ProjectInvestment::findOrFail($id);
        $logs = $project->progressLogs()->with('updatedBy')->orderBy('created_at', 'desc')->get();
        return response()->json($logs);
    }

    public function destroy($id)
    {
        $project = ProjectInvestment::findOrFail($id);
        
        // Return stock if items were deducted
        if ($project->status !== 'pending') {
            $this->returnItemsToStock($project);
        }
        
        $project->delete();
        return response()->json(['message' => 'Project deleted successfully']);
    }

    /**
     * Approve project and deduct stock
     */
    public function approve($id)
    {
        $project = ProjectInvestment::with('items.product')->findOrFail($id);
        
        if ($project->status !== 'pending') {
            return response()->json(['message' => 'Only pending projects can be approved'], 422);
        }

        DB::beginTransaction();
        try {
            $stockService = new StockSyncService();

            // Deduct stock for all items
            foreach ($project->items as $item) {
                if (!$item->stock_deducted) {
                    $stockService->deductStock(
                        $item->product_id,
                        $item->quantity,
                        'project_allocation',
                        $project->id,
                        "Project: {$project->project_code} - {$project->project_name}"
                    );
                    $item->update(['stock_deducted' => true]);
                }
            }

            $project->update([
                'status' => 'approved',
                'approved_by' => auth()->id(),
                'approved_at' => now(),
            ]);

            DB::commit();
            return response()->json($project->load(['items.product', 'user', 'approver']));
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    /**
     * Start project (change to active)
     */
    public function start($id)
    {
        $project = ProjectInvestment::findOrFail($id);
        
        if ($project->status !== 'approved') {
            return response()->json(['message' => 'Only approved projects can be started'], 422);
        }

        $project->update(['status' => 'active']);
        return response()->json($project->load(['items.product', 'user', 'approver']));
    }

    /**
     * Complete project
     */
    public function complete($id)
    {
        $project = ProjectInvestment::findOrFail($id);
        
        if (!in_array($project->status, ['approved', 'active'])) {
            return response()->json(['message' => 'Only approved or active projects can be completed'], 422);
        }

        $project->update([
            'status' => 'completed',
            'actual_end_date' => now(),
        ]);

        return response()->json($project->load(['items.product', 'user', 'approver']));
    }

    /**
     * Cancel project and return stock
     */
    public function cancel($id)
    {
        $project = ProjectInvestment::with('items')->findOrFail($id);
        
        if ($project->status === 'completed') {
            return response()->json(['message' => 'Completed projects cannot be cancelled'], 422);
        }

        DB::beginTransaction();
        try {
            // Return stock for deducted items
            $this->returnItemsToStock($project);

            $project->update(['status' => 'cancelled']);

            DB::commit();
            return response()->json($project->load(['items.product', 'user', 'approver']));
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    /**
     * Export project to Excel
     */
    public function export($id)
    {
        $project = ProjectInvestment::with(['items.product', 'user', 'approver'])->findOrFail($id);

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Header
        $sheet->setCellValue('A1', 'PROJECT INVESTMENT REPORT');
        $sheet->mergeCells('A1:F1');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(16);

        // Project Info
        $sheet->setCellValue('A3', 'Project Code:');
        $sheet->setCellValue('B3', $project->project_code);
        $sheet->setCellValue('A4', 'Project Name:');
        $sheet->setCellValue('B4', $project->project_name);
        $sheet->setCellValue('A5', 'Client:');
        $sheet->setCellValue('B5', $project->client_name);
        $sheet->setCellValue('A6', 'Status:');
        $sheet->setCellValue('B6', strtoupper($project->status));
        $sheet->setCellValue('A7', 'Start Date:');
        $sheet->setCellValue('B7', $project->start_date->format('d/m/Y'));
        $sheet->setCellValue('A8', 'Total Value:');
        $sheet->setCellValue('B8', 'Rp ' . number_format($project->total_value, 0, ',', '.'));

        // Items Header
        $sheet->setCellValue('A10', 'No');
        $sheet->setCellValue('B10', 'Product');
        $sheet->setCellValue('C10', 'Quantity');
        $sheet->setCellValue('D10', 'Unit Price');
        $sheet->setCellValue('E10', 'Total');
        $sheet->setCellValue('F10', 'Notes');
        $sheet->getStyle('A10:F10')->getFont()->setBold(true);

        // Items Data
        $row = 11;
        $no = 1;
        foreach ($project->items as $item) {
            $sheet->setCellValue('A' . $row, $no);
            $sheet->setCellValue('B' . $row, $item->product->title ?? 'N/A');
            $sheet->setCellValue('C' . $row, $item->quantity);
            $sheet->setCellValue('D' . $row, 'Rp ' . number_format($item->unit_price, 0, ',', '.'));
            $sheet->setCellValue('E' . $row, 'Rp ' . number_format($item->total_price, 0, ',', '.'));
            $sheet->setCellValue('F' . $row, $item->notes ?? '-');
            $row++;
            $no++;
        }

        // Auto width
        foreach (range('A', 'F') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $filename = 'Project_' . $project->project_code . '_' . date('Ymd') . '.xlsx';
        
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="' . $filename . '"');
        header('Cache-Control: max-age=0');

        $writer = new Xlsx($spreadsheet);
        $writer->save('php://output');
        exit;
    }

    private function returnItemsToStock(ProjectInvestment $project)
    {
        $stockService = new StockSyncService();

        foreach ($project->items as $item) {
            if ($item->stock_deducted) {
                $stockService->addStock(
                    $item->product_id,
                    $item->quantity,
                    'project_return',
                    $project->id,
                    "Returned from cancelled project: {$project->project_code}"
                );
                $item->update(['stock_deducted' => false]);
            }
        }
    }
}
