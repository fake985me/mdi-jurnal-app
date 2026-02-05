<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Solution;
use Illuminate\Http\Request;

class SolutionController extends Controller
{
    public function index()
    {
        $solutions = Solution::active()->ordered()->get();
        return response()->json($solutions);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'image_url' => 'nullable|string',
            'order' => 'nullable|integer',
            'is_active' => 'nullable|boolean',
        ]);

        $solution = Solution::create($validated);
        return response()->json($solution, 201);
    }

    public function show($id)
    {
        $solution = Solution::findOrFail($id);
        return response()->json($solution);
    }

    public function update(Request $request, $id)
    {
        $solution = Solution::findOrFail($id);

        $validated = $request->validate([
            'title' => 'sometimes|string|max:255',
            'description' => 'sometimes|string',
            'image_url' => 'nullable|string',
            'order' => 'nullable|integer',
            'is_active' => 'nullable|boolean',
        ]);

        $solution->update($validated);
        return response()->json($solution);
    }

    public function destroy($id)
    {
        $solution = Solution::findOrFail($id);
        $solution->delete();
        return response()->json(['message' => 'Solution deleted successfully']);
    }
}
