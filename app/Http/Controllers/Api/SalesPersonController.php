<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SalesPerson;
use Illuminate\Http\Request;

class SalesPersonController extends Controller
{
    public function index()
    {
        $salesPeople = SalesPerson::with('user')->get();
        return response()->json($salesPeople);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'nullable|string',
            'email' => 'nullable|email',
            'commission_rate' => 'required|numeric|min:0|max:100',
            'active' => 'required|boolean',
        ]);

        $salesPerson = SalesPerson::create($validated);
        return response()->json($salesPerson, 201);
    }

    public function show($id)
    {
        $salesPerson = SalesPerson::with(['user', 'sales'])->findOrFail($id);
        return response()->json($salesPerson);
    }

    public function update(Request $request, $id)
    {
        $salesPerson = SalesPerson::findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'nullable|string',
            'email' => 'nullable|email',
            'commission_rate' => 'required|numeric|min:0|max:100',
            'active' => 'required|boolean',
        ]);

        $salesPerson->update($validated);
        return response()->json($salesPerson);
    }

    public function destroy($id)
    {
        $salesPerson = SalesPerson::findOrFail($id);
        $salesPerson->delete();
        return response()->json(['message' => 'Sales person deleted successfully']);
    }
}
