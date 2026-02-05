<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PageSection;
use Illuminate\Http\Request;

class PageSectionController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'page_id' => 'required|exists:pages,id',
            'section_type' => 'required|string',
            'content' => 'required|array',
            'settings' => 'nullable|array',
            'order' => 'nullable|integer',
        ]);

        // Auto-increment order if not provided
        if (!isset($validated['order'])) {
            $maxOrder = PageSection::where('page_id', $validated['page_id'])->max('order');
            $validated['order'] = ($maxOrder ?? -1) + 1;
        }

        $section = PageSection::create($validated);
        return response()->json($section, 201);
    }

    public function update(Request $request, $id)
    {
        $section = PageSection::findOrFail($id);

        $validated = $request->validate([
            'content' => 'sometimes|array',
            'settings' => 'nullable|array',
            'is_visible' => 'nullable|boolean',
        ]);

        $section->update($validated);
        return response()->json($section);
    }

    public function destroy($id)
    {
        $section = PageSection::findOrFail($id);
        $section->delete();
        
        return response()->json(['message' => 'Section deleted successfully']);
    }

    public function reorder(Request $request, $pageId)
    {
        $validated = $request->validate([
            'sections' => 'required|array',
            'sections.*.id' => 'required|exists:page_sections,id',
            'sections.*.order' => 'required|integer',
        ]);

        foreach ($validated['sections'] as $sectionData) {
            PageSection::where('id', $sectionData['id'])
                ->where('page_id', $pageId)
                ->update(['order' => $sectionData['order']]);
        }

        return response()->json(['message' => 'Sections reordered successfully']);
    }
}
