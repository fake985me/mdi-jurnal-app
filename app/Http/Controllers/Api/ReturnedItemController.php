<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ReturnedItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReturnedItemController extends Controller
{
    public function inspect(Request $request, $id)
    {
        $data = $request->validate(['condition' => 'required|in:unrepairable,limited,working', 'notes' => 'required|string|max:5000']);
        return DB::transaction(function () use ($id, $data) {
            $item = ReturnedItem::whereKey($id)->lockForUpdate()->firstOrFail();
            abort_unless(in_array($item->condition, ['defective', 'unrepairable'], true), 422, 'Barang sudah masuk stok RMA atau telah dipakai.');
            $item->update([
                'condition' => $data['condition'], 'notes' => $data['notes'],
                'available_quantity' => $data['condition'] === 'unrepairable' ? 0 : $item->quantity,
                'book_value' => 0, 'inspected_at' => now(), 'inspected_by' => auth()->id(),
            ]);
            return response()->json($item->load('product'));
        });
    }
}
