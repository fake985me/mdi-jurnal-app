<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\TaxRate;
use Illuminate\Http\Request;

class TaxRateController extends Controller
{
    public function index()
    {
        $taxRates = TaxRate::orderBy('name')->get();
        return response()->json($taxRates);
    }

    public function active()
    {
        $taxRates = TaxRate::active()->orderBy('name')->get();
        return response()->json($taxRates);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:50|unique:tax_rates,code',
            'rate' => 'required|numeric|min:0|max:100',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        $taxRate = TaxRate::create($validated);
        return response()->json($taxRate, 201);
    }

    public function show(TaxRate $taxRate)
    {
        return response()->json($taxRate);
    }

    public function update(Request $request, TaxRate $taxRate)
    {
        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'code' => 'sometimes|required|string|max:50|unique:tax_rates,code,' . $taxRate->id,
            'rate' => 'sometimes|required|numeric|min:0|max:100',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        $taxRate->update($validated);
        return response()->json($taxRate);
    }

    public function destroy(TaxRate $taxRate)
    {
        $taxRate->delete();
        return response()->json(['message' => 'Tax rate deleted successfully']);
    }

    public function toggle(TaxRate $taxRate)
    {
        $taxRate->update(['is_active' => !$taxRate->is_active]);
        return response()->json($taxRate);
    }
}
