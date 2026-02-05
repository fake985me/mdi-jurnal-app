<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PageTemplate;
use Illuminate\Http\Request;

class PageTemplateController extends Controller
{
    public function index()
    {
        $templates = PageTemplate::active()->get();
        return response()->json($templates);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'thumbnail' => 'nullable|string',
            'default_sections' => 'required|array',
            'is_active' => 'nullable|boolean',
        ]);

        $template = PageTemplate::create($validated);
        return response()->json($template, 201);
    }

    public function show($id)
    {
        $template = PageTemplate::findOrFail($id);
        return response()->json($template);
    }

    public function update(Request $request, $id)
    {
        $template = PageTemplate::findOrFail($id);

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'thumbnail' => 'nullable|string',
            'default_sections' => 'sometimes|array',
            'is_active' => 'nullable|boolean',
        ]);

        $template->update($validated);
        return response()->json($template);
    }

    public function destroy($id)
    {
        $template = PageTemplate::findOrFail($id);
        $template->delete();
        
        return response()->json(['message' => 'Template deleted successfully']);
    }
}
