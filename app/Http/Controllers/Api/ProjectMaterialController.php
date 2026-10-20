<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\{ProjectInvestment, ProjectMaterial, ProjectProgressLog, DeliveryFulfillment};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProjectMaterialController extends Controller
{
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


    private function rules(bool $update = false): array
    {
        $required = $update ? 'sometimes|required' : 'required';
        return ['name' => $required.'|string|max:255', 'unit' => 'nullable|string|max:50',
            'specification' => 'nullable|string|max:500', 'quantity_planned' => $required.'|integer|min:1',
            'unit_price' => $required.'|numeric|min:0|max:9999999999.99', 'notes' => 'nullable|string|max:5000',
            'expected_delivery_date' => 'nullable|date', 'sort_order' => 'nullable|integer'];
    }

    private function project($id): ProjectInvestment
    {
        $project = ProjectInvestment::lockForUpdate()->findOrFail($id);
        abort_unless(in_array($project->status, ['pending', 'approved', 'active'], true), 422, 'Project is closed.');
        return $project;
    }

    private function snapshot(ProjectMaterial $material): array
    {
        return $material->only(['id', 'name', 'unit', 'quantity_planned', 'quantity_ordered', 'quantity_received', 'quantity_installed', 'unit_price', 'total_price', 'status']);
    }

    private function record(ProjectMaterial $material, string $action, ?array $before): void
    {
        ProjectProgressLog::logChange($material->project_investment_id, 'material', $action,
            $before['total_price'] ?? null, $material->total_price,
            $material->name.' | Qty '.$material->quantity_planned.' '.$material->unit.' | Rp '.$material->unit_price.' | Total Rp '.$material->total_price,
            ['before' => $before, 'after' => $this->snapshot($material)]);
    }

    public function store(Request $request, ProjectInvestment $project)
    {
        $data = $request->validate($this->rules());
        $data['unit'] = $data['unit'] ?? 'pcs';
        return DB::transaction(function () use ($project, $data) {
            $project = $this->project($project->id);
            $material = $project->materials()->create($data + ['unit' => 'pcs', 'total_price' => round($data['quantity_planned'] * $data['unit_price'], 2), 'status' => 'pending']);
            $project->calculateTotalValue();
            $this->record($material, 'material_added', null);
            return response()->json($material, 201);
        });
    }

    public function update(Request $request, ProjectMaterial $material)
    {
        $data = $request->validate($this->rules(true));
        if (array_key_exists('unit', $data)) $data['unit'] = $data['unit'] ?? 'pcs';
        return DB::transaction(function () use ($material, $data) {
            $project = $this->project($material->project_investment_id);
            $material = ProjectMaterial::lockForUpdate()->findOrFail($material->id);
            $before = $this->snapshot($material);
            $quantity = $data['quantity_planned'] ?? $material->quantity_planned;
            abort_if($quantity < $material->quantity_received || $quantity < $material->quantity_installed, 422, 'Quantity cannot be below received or installed quantity.');
            $shipment = DeliveryFulfillment::where('request_key', 'material-'.$material->id)->first();
            abort_if($quantity != $material->quantity_planned && $shipment && $shipment->status !== 'preparing', 422, 'Shipment quantities are locked after shipping.');
            $data['total_price'] = round($quantity * ($data['unit_price'] ?? $material->unit_price), 2);
            $material->update($data); $material->updateStatus();
            $project->calculateTotalValue(); $this->record($material, 'material_updated', $before);
            return response()->json($material);
        });
    }

    public function updateQuantities(Request $request, ProjectMaterial $material)
    {
        $data = $request->validate(['quantity_ordered' => 'nullable|integer|min:0', 'quantity_received' => 'nullable|integer|min:0',
            'quantity_installed' => 'nullable|integer|min:0', 'actual_delivery_date' => 'nullable|date', 'notes' => 'nullable|string|max:5000']);
        return DB::transaction(function () use ($material, $data) {
            $this->project($material->project_investment_id);
            $material = ProjectMaterial::lockForUpdate()->findOrFail($material->id);
            abort_if(isset($data['quantity_received']) && $data['quantity_received'] != $material->quantity_received, 422, 'Confirm receipt manually in Deliveries.');
            abort_if(($data['quantity_installed'] ?? $material->quantity_installed) > $material->quantity_received || ($data['quantity_ordered'] ?? $material->quantity_ordered) > $material->quantity_planned, 422, 'Material quantities are inconsistent.');
            $before = $this->snapshot($material);
            $material->update($data); $material->updateStatus();
            $this->record($material, 'material_quantities', $before);
            return response()->json($material);
        });
    }

    public function destroy(ProjectMaterial $material)
    {
        return DB::transaction(function () use ($material) {
            $project = $this->project($material->project_investment_id);
            $material = ProjectMaterial::lockForUpdate()->findOrFail($material->id);
            $shipment = DeliveryFulfillment::where('request_key', 'material-'.$material->id)->first();
            abort_if($material->quantity_received > 0 || $material->quantity_installed > 0 || ($shipment && !in_array($shipment->status, ['preparing', 'cancelled'], true)), 422, 'Received or shipped materials must remain in the register.');
            $this->record($material, 'material_deleted', $this->snapshot($material));
            $material->delete(); $project->calculateTotalValue();
            return response()->json(['message' => 'Material deleted.']);
        });
    }

    public function reorder(Request $request, ProjectInvestment $project)
    {
        $data = $request->validate(['materials' => 'required|array', 'materials.*.id' => 'required|integer', 'materials.*.sort_order' => 'required|integer']);
        foreach ($data['materials'] as $item) $project->materials()->whereKey($item['id'])->update(['sort_order' => $item['sort_order']]);
        return response()->json(['message' => 'Materials reordered.']);
    }
}
