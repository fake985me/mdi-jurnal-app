<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Supplier;
use Illuminate\Http\Request;

class SupplierController extends Controller
{
    public function index(Request $request)
    {
        $query = Supplier::query();

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('company', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%")
                  ->orWhere('supplier_code', 'like', "%{$search}%");
            });
        }

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        if ($request->filled('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        // If requesting all (for dropdown), return without pagination
        if ($request->boolean('all')) {
            return response()->json(
                $query->active()->orderBy('name')->get()
            );
        }

        $suppliers = $query->withCount('purchases')
            ->orderBy('name')
            ->paginate($request->per_page ?? 15);

        return response()->json($suppliers);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'company' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:50',
            'phone_alt' => 'nullable|string|max:50',
            'address' => 'nullable|string',
            'city' => 'nullable|string|max:100',
            'province' => 'nullable|string|max:100',
            'postal_code' => 'nullable|string|max:20',
            'npwp' => 'nullable|string|max:50',
            'type' => 'nullable|in:individual,company',
            'bank_name' => 'nullable|string|max:100',
            'bank_account_number' => 'nullable|string|max:50',
            'bank_account_name' => 'nullable|string|max:100',
            'is_active' => 'nullable|boolean',
            'notes' => 'nullable|string',
        ]);

        $supplier = Supplier::create([
            'supplier_code' => Supplier::generateCode(),
            'name' => $validated['name'],
            'company' => $validated['company'] ?? null,
            'email' => $validated['email'] ?? null,
            'phone' => $validated['phone'] ?? null,
            'phone_alt' => $validated['phone_alt'] ?? null,
            'address' => $validated['address'] ?? null,
            'city' => $validated['city'] ?? null,
            'province' => $validated['province'] ?? null,
            'postal_code' => $validated['postal_code'] ?? null,
            'npwp' => $validated['npwp'] ?? null,
            'type' => $validated['type'] ?? 'company',
            'bank_name' => $validated['bank_name'] ?? null,
            'bank_account_number' => $validated['bank_account_number'] ?? null,
            'bank_account_name' => $validated['bank_account_name'] ?? null,
            'is_active' => $validated['is_active'] ?? true,
            'notes' => $validated['notes'] ?? null,
        ]);

        return response()->json($supplier, 201);
    }

    public function show(Supplier $supplier)
    {
        $supplier->loadCount('purchases');
        $supplier->total_purchases = $supplier->total_purchases;
        $supplier->recent_purchases = $supplier->purchases()
            ->with('items.product')
            ->orderBy('created_at', 'desc')
            ->take(10)
            ->get();

        return response()->json($supplier);
    }

    public function update(Request $request, Supplier $supplier)
    {
        $validated = $request->validate([
            'name' => 'nullable|string|max:255',
            'company' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:50',
            'phone_alt' => 'nullable|string|max:50',
            'address' => 'nullable|string',
            'city' => 'nullable|string|max:100',
            'province' => 'nullable|string|max:100',
            'postal_code' => 'nullable|string|max:20',
            'npwp' => 'nullable|string|max:50',
            'type' => 'nullable|in:individual,company',
            'bank_name' => 'nullable|string|max:100',
            'bank_account_number' => 'nullable|string|max:50',
            'bank_account_name' => 'nullable|string|max:100',
            'is_active' => 'nullable|boolean',
            'notes' => 'nullable|string',
        ]);

        $supplier->update($validated);

        return response()->json($supplier);
    }

    public function destroy(Supplier $supplier)
    {
        // Check if supplier has purchases
        if ($supplier->purchases()->exists()) {
            return response()->json([
                'message' => 'Cannot delete supplier with existing purchases. Deactivate instead.'
            ], 422);
        }

        $supplier->delete();
        return response()->json(['message' => 'Supplier deleted successfully']);
    }

    /**
     * Get summary statistics
     */
    public function summary()
    {
        return response()->json([
            'total_suppliers' => Supplier::count(),
            'active_suppliers' => Supplier::active()->count(),
            'companies' => Supplier::where('type', 'company')->count(),
            'individuals' => Supplier::where('type', 'individual')->count(),
        ]);
    }
}
