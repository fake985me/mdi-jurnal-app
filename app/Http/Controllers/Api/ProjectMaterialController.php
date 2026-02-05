<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ProjectInvestment;
use App\Models\ProjectMaterial;
use App\Models\ProjectProgressLog;
use Illuminate\Http\Request;

class ProjectMaterialController extends Controller
{
    /**
     * Get all materials for a project
     */
    public function index(ProjectInvestment $project)
    {
        $materials = $project->materials()
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        $stats = [
            'total_count' => $materials->count(),
            'total_value' => $materials->sum('total_price'),
            'pending' => $materials->where('status', 'pending')->count(),
            'ordered' => $materials->where('status', 'ordered')->count(),
            'received' => $materials->where('status', 'received')->count(),
            'installed' => $materials->where('status', 'installed')->count(),
            'delivery_progress' => $materials->isEmpty() ? 0 : 
                round($materials->sum('quantity_received') / max($materials->sum('quantity_planned'), 1) * 100),
            'installation_progress' => $materials->isEmpty() ? 0 : 
                round($materials->sum('quantity_installed') / max($materials->sum('quantity_planned'), 1) * 100),
        ];

        return response()->json([
            'materials' => $materials,
            'stats' => $stats,
        ]);
    }

    /**
     * Store a new material
     */
    public function store(Request $request, ProjectInvestment $project)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'unit' => 'nullable|string|max:50',
            'specification' => 'nullable|string|max:500',
            'quantity_planned' => 'required|integer|min:0',
            'unit_price' => 'nullable|numeric|min:0',
            'expected_delivery_date' => 'nullable|date',
            'notes' => 'nullable|string',
        ]);

        $validated['project_investment_id'] = $project->id;
        $validated['unit'] = $validated['unit'] ?? 'pcs';
        $validated['unit_price'] = $validated['unit_price'] ?? 0;
        $validated['total_price'] = $validated['quantity_planned'] * $validated['unit_price'];
        $validated['status'] = ProjectMaterial::STATUS_PENDING;

        $material = ProjectMaterial::create($validated);

        // Update project total value
        $project->calculateTotalValue();

        // Log the change
        ProjectProgressLog::logChange(
            $project->id,
            ProjectProgressLog::TYPE_MATERIAL,
            'material_added',
            null,
            $material->name,
            "Added material: {$material->name} x {$material->quantity_planned} {$material->unit}"
        );

        return response()->json($material, 201);
    }

    /**
     * Update a material
     */
    public function update(Request $request, ProjectMaterial $material)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'unit' => 'nullable|string|max:50',
            'specification' => 'nullable|string|max:500',
            'quantity_planned' => 'required|integer|min:0',
            'quantity_ordered' => 'nullable|integer|min:0',
            'quantity_received' => 'nullable|integer|min:0',
            'quantity_installed' => 'nullable|integer|min:0',
            'unit_price' => 'nullable|numeric|min:0',
            'expected_delivery_date' => 'nullable|date',
            'actual_delivery_date' => 'nullable|date',
            'notes' => 'nullable|string',
            'sort_order' => 'nullable|integer',
        ]);

        $validated['total_price'] = $validated['quantity_planned'] * ($validated['unit_price'] ?? $material->unit_price);

        $material->update($validated);
        $material->updateStatus();

        // Update project total value
        $material->project->calculateTotalValue();

        return response()->json($material);
    }

    /**
     * Update material quantities and status
     */
    public function updateQuantities(Request $request, ProjectMaterial $material)
    {
        $validated = $request->validate([
            'quantity_ordered' => 'nullable|integer|min:0',
            'quantity_received' => 'nullable|integer|min:0',
            'quantity_installed' => 'nullable|integer|min:0',
            'actual_delivery_date' => 'nullable|date',
            'notes' => 'nullable|string',
        ]);

        $oldReceived = $material->quantity_received;
        $oldInstalled = $material->quantity_installed;

        if (isset($validated['quantity_ordered'])) {
            $material->quantity_ordered = $validated['quantity_ordered'];
        }
        if (isset($validated['quantity_received'])) {
            $material->quantity_received = $validated['quantity_received'];
        }
        if (isset($validated['quantity_installed'])) {
            $material->quantity_installed = $validated['quantity_installed'];
        }
        if (isset($validated['actual_delivery_date'])) {
            $material->actual_delivery_date = $validated['actual_delivery_date'];
        }

        $material->save();
        $material->updateStatus();

        // Log changes
        $project = $material->project;
        
        if ($oldReceived != $material->quantity_received) {
            ProjectProgressLog::logChange(
                $project->id,
                ProjectProgressLog::TYPE_MATERIAL,
                'quantity_received',
                $oldReceived,
                $material->quantity_received,
                "Material: {$material->name} - " . ($validated['notes'] ?? '')
            );
        }

        if ($oldInstalled != $material->quantity_installed) {
            ProjectProgressLog::logChange(
                $project->id,
                ProjectProgressLog::TYPE_MATERIAL,
                'quantity_installed',
                $oldInstalled,
                $material->quantity_installed,
                "Material: {$material->name} - " . ($validated['notes'] ?? '')
            );
        }

        return response()->json($material);
    }

    /**
     * Delete a material
     */
    public function destroy(ProjectMaterial $material)
    {
        $project = $material->project;
        $materialName = $material->name;
        
        $material->delete();

        // Update project total value
        $project->calculateTotalValue();

        // Log the deletion
        ProjectProgressLog::logChange(
            $project->id,
            ProjectProgressLog::TYPE_MATERIAL,
            'material_deleted',
            $materialName,
            null,
            "Deleted material: {$materialName}"
        );

        return response()->json(['message' => 'Material deleted successfully']);
    }

    /**
     * Reorder materials
     */
    public function reorder(Request $request, ProjectInvestment $project)
    {
        $validated = $request->validate([
            'materials' => 'required|array',
            'materials.*.id' => 'required|exists:project_materials,id',
            'materials.*.sort_order' => 'required|integer',
        ]);

        foreach ($validated['materials'] as $item) {
            ProjectMaterial::where('id', $item['id'])
                ->where('project_investment_id', $project->id)
                ->update(['sort_order' => $item['sort_order']]);
        }

        return response()->json(['message' => 'Materials reordered successfully']);
    }
}
