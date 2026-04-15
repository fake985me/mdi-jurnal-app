<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use Illuminate\Http\Request;

class CustomerController extends Controller
{
    public function index(Request $request)
    {
        $query = Customer::query();

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('company', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%")
                  ->orWhere('customer_code', 'like', "%{$search}%");
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

        $customers = $query->withCount('sales')
            ->orderBy('name')
            ->paginate($request->per_page ?? 15);

        return response()->json($customers);
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
            'is_active' => 'nullable|boolean',
            'notes' => 'nullable|string',
        ]);

        $customer = Customer::create([
            'customer_code' => Customer::generateCode(),
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
            'type' => $validated['type'] ?? 'individual',
            'is_active' => $validated['is_active'] ?? true,
            'notes' => $validated['notes'] ?? null,
        ]);

        return response()->json($customer, 201);
    }

    public function show(Customer $customer)
    {
        $customer->loadCount('sales');
        $customer->total_sales = $customer->total_sales;
        $customer->recent_sales = $customer->sales()
            ->with('items.product')
            ->orderBy('created_at', 'desc')
            ->take(10)
            ->get();

        return response()->json($customer);
    }

    public function update(Request $request, Customer $customer)
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
            'is_active' => 'nullable|boolean',
            'notes' => 'nullable|string',
        ]);

        $customer->update($validated);

        return response()->json($customer);
    }

    public function destroy(Customer $customer)
    {
        // Check if customer has sales
        if ($customer->sales()->exists()) {
            return response()->json([
                'message' => 'Cannot delete customer with existing sales. Deactivate instead.'
            ], 422);
        }

        $customer->delete();
        return response()->json(['message' => 'Customer deleted successfully']);
    }

    /**
     * Get summary statistics
     */
    public function summary()
    {
        return response()->json([
            'total_customers' => Customer::count(),
            'active_customers' => Customer::active()->count(),
            'companies' => Customer::where('type', 'company')->count(),
            'individuals' => Customer::where('type', 'individual')->count(),
        ]);
    }
}
