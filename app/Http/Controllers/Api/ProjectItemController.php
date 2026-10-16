<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\{ProjectInvestment, ProjectInvestmentItem, ProjectItemChange, ReturnedItem, Warehouse};
use App\Services\{StockTransferService, StockSyncService, ReturnedInventoryService};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProjectItemController extends Controller
{
    public function show($id)
    {
        $project = ProjectInvestment::with(['items.product', 'warehouse'])->findOrFail($id);
        return response()->json(['project' => $project, 'changes' => ProjectItemChange::where('project_investment_id', $id)->latest('id')->get()]);
    }

    public function store(Request $request, $id)
    {
        $data = $request->validate([
            'product_id' => 'required|exists:products,id', 'quantity' => 'required|integer|min:1',
            'unit_price' => 'required|numeric|min:0', 'notes' => 'nullable|string|max:5000',
            'serial_numbers' => 'nullable|array', 'serial_numbers.*' => 'required|string|max:255|distinct:ignore_case',
            'returned_item_id' => 'nullable|exists:returned_items,id',
        ]);
        try {
            return DB::transaction(function () use ($id, $data) {
                $project = $this->project($id);
                $serials = $data['serial_numbers'] ?? [];
                if ($serials && count($serials) !== (int) $data['quantity']) throw new \RuntimeException('Jumlah SN harus sesuai quantity.');
                $recovered = null;
                if (!empty($data['returned_item_id'])) {
                    $recovered = ReturnedItem::whereKey($data['returned_item_id'])->lockForUpdate()->firstOrFail();
                    if ($recovered->product_id != $data['product_id'] || !in_array($recovered->condition, ['working', 'limited'], true) || $recovered->available_quantity < $data['quantity']) throw new \RuntimeException('Stok RMA tidak sesuai atau tidak mencukupi.');
                    $serials = $recovered->serial_number ? [$recovered->serial_number] : [];
                    $recovered->available_quantity -= $data['quantity'];
                    $recovered->save();
                } else {
                    $this->allocate($project, $data['product_id'], $data['quantity']);
                }
                $this->checkSerials($project, $data['product_id'], $serials);
                $price = $recovered ? 0 : $data['unit_price'];
                $item = $project->items()->create([
                    'product_id' => $data['product_id'], 'quantity' => $data['quantity'],
                    'unit_price' => $price, 'total_price' => $price * $data['quantity'],
                    'stock_deducted' => true, 'serial_numbers' => $serials,
                    'returned_item_id' => $recovered?->id, 'notes' => $data['notes'] ?? null,
                ]);
                $this->log($project, $item, 'add', $data['quantity'], [], $serials, $data['notes'] ?? null);
                $project->calculateTotalValue();
                return response()->json($item->load('product'), 201);
            });
        } catch (\Exception $e) { return response()->json(['message' => $e->getMessage()], 422); }
    }

    public function serials(Request $request, $id, $itemId)
    {
        $data = $request->validate(['serial_numbers' => 'required|array|min:1', 'serial_numbers.*' => 'required|string|max:255|distinct:ignore_case']);
        try {
            return DB::transaction(function () use ($id, $itemId, $data) {
                $project = $this->project($id);
                $item = $project->items()->whereKey($itemId)->lockForUpdate()->firstOrFail();
                if ($item->serial_numbers) throw new \RuntimeException('SN sudah dicatat. Gunakan penggantian barang untuk mengganti unit.');
                if (count($data['serial_numbers']) !== $item->quantity) throw new \RuntimeException('Isi satu SN untuk setiap unit pada item ini.');
                $this->checkSerials($project, $item->product_id, $data['serial_numbers']);
                $item->update($data);
                $this->log($project, $item, 'serials', $item->quantity, [], $data['serial_numbers'], 'Pencatatan SN awal');
                return response()->json($item);
            });
        } catch (\Exception $e) { return response()->json(['message' => $e->getMessage()], 422); }
    }

    public function replace(Request $request, $id, $itemId)
    {
        $data = $request->validate([
            'old_serial_numbers' => 'required|array|min:1', 'old_serial_numbers.*' => 'required|string|max:255|distinct:ignore_case',
            'new_serial_numbers' => 'required|array|min:1', 'new_serial_numbers.*' => 'required|string|max:255|distinct:ignore_case',
            'notes' => 'required|string|max:5000',
        ]);
        try {
            return DB::transaction(function () use ($id, $itemId, $data) {
                $project = $this->project($id);
                $item = $project->items()->whereKey($itemId)->lockForUpdate()->firstOrFail();
                $old = array_map('trim', $data['old_serial_numbers']);
                $new = array_map('trim', $data['new_serial_numbers']);
                $quantity = count($old);
                if (!$item->stock_deducted || $quantity !== count($new) || $quantity > $item->quantity || ($item->returned_item_id && $quantity !== $item->quantity)) throw new \RuntimeException('Jumlah unit pengganti tidak sesuai item proyek.');
                if (array_intersect(array_map('strtolower', $old), array_map('strtolower', $new))) throw new \RuntimeException('SN baru harus berbeda dari SN retur.');
                $current = $item->serial_numbers ?? [];
                if (!$current) throw new \RuntimeException('Catat SN awal item proyek sebelum melakukan penggantian.');
                foreach ($old as $sn) if (!in_array(strtolower($sn), array_map('strtolower', $current), true)) throw new \RuntimeException('SN retur tidak terdaftar pada item proyek ini.');
                $this->checkSerials($project, $item->product_id, $new);
                if (!$item->returned_item_id) app(StockSyncService::class)->deductStock($item->product_id, $quantity, 'project_defective_return', $project->id, $data['notes'], $project->warehouse_id);
                $this->allocate($project, $item->product_id, $quantity);
                $mapping = array_combine(array_map('strtolower', $old), $new);
                $item->update(['serial_numbers' => array_map(fn ($sn) => $mapping[strtolower($sn)] ?? $sn, $current), 'returned_item_id' => null]);
                $change = $this->log($project, $item, 'replace', $quantity, $old, $new, $data['notes']);
                app(ReturnedInventoryService::class)->register('project', $change->id, $project->project_code, $item->product_id, $quantity, $old, $data['notes']);
                return response()->json($item->load('product'));
            });
        } catch (\Exception $e) { return response()->json(['message' => $e->getMessage()], 422); }
    }

    private function project($id): ProjectInvestment
    {
        $project = ProjectInvestment::whereKey($id)->lockForUpdate()->firstOrFail();
        if (!in_array($project->status, ['approved', 'active'], true)) throw new \RuntimeException('Pengelolaan barang tersedia untuk proyek approved atau active.');
        return $project;
    }
    private function allocate(ProjectInvestment $project, int $productId, int $quantity): void
    {
        $default = Warehouse::getDefault();
        if (!$default || !$project->warehouse_id || $default->id == $project->warehouse_id) throw new \RuntimeException('Gudang asal dan gudang proyek harus tersedia dan berbeda.');
        app(StockTransferService::class)->transferNow($default->id, $project->warehouse_id, $productId, $quantity, 'Penambahan/penggantian barang '.$project->project_code, auth()->id());
    }
    private function checkSerials(ProjectInvestment $project, int $productId, array $serials): void
    {
        $normalized = array_map(fn ($sn) => strtolower(trim($sn)), $serials);
        if (in_array('', $normalized, true) || count(array_unique($normalized)) !== count($normalized)) throw new \RuntimeException('SN harus terisi dan unik.');
        foreach ($project->items()->where('product_id', $productId)->get() as $item) {
            if (array_intersect($normalized, array_map('strtolower', $item->serial_numbers ?? []))) throw new \RuntimeException('SN sudah digunakan pada proyek ini.');
        }
    }
    private function log($project, $item, $type, $quantity, $old, $new, $notes): ProjectItemChange
    {
        return ProjectItemChange::create(['project_investment_id' => $project->id, 'project_investment_item_id' => $item->id, 'type' => $type, 'quantity' => $quantity, 'old_serial_numbers' => $old, 'new_serial_numbers' => $new, 'notes' => $notes, 'user_id' => auth()->id()]);
    }
}
