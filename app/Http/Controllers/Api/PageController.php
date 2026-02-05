<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Page;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class PageController extends Controller
{
    public function index(Request $request)
    {
        $query = Page::with(['sections', 'template']);
        
        if ($request->has('published')) {
            $query->published();
        }
        
        $pages = $query->orderBy('updated_at', 'desc')->paginate(10);
        return response()->json($pages);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'slug' => 'nullable|string|unique:pages,slug',
            'meta_description' => 'nullable|string',
            'meta_keywords' => 'nullable|string',
            'template_id' => 'nullable|exists:page_templates,id',
            'show_in_nav' => 'nullable|boolean',
            'nav_order' => 'nullable|integer',
            'nav_parent' => 'nullable|string',
            'nav_label' => 'nullable|string',
        ]);

        // Auto-generate slug if not provided
        if (!isset($validated['slug'])) {
            $validated['slug'] = Str::slug($validated['title']);
        }

        $page = Page::create($validated);

        // If template selected, copy default sections
        if ($page->template_id) {
            $template = $page->template;
            foreach ($template->default_sections as $index => $section) {
                $page->sections()->create([
                    'section_type' => $section['type'],
                    'content' => $section['content'] ?? [],
                    'settings' => $section['settings'] ?? [],
                    'order' => $index,
                ]);
            }
        }

        return response()->json($page->load('sections'), 201);
    }

    public function show($id)
    {
        $page = Page::with(['sections' => function($query) {
            $query->ordered();
        }, 'template'])->findOrFail($id);
        
        return response()->json($page);
    }

    public function showBySlug($slug)
    {
        $page = Page::bySlug($slug)
            ->published()
            ->with(['sections' => function($query) {
                $query->visible()->ordered();
            }])
            ->firstOrFail();
            
        return response()->json($page);
    }

    public function update(Request $request, $id)
    {
        $page = Page::findOrFail($id);

        $validated = $request->validate([
            'title' => 'sometimes|string|max:255',
            'slug' => 'sometimes|string|unique:pages,slug,' . $id,
            'meta_description' => 'nullable|string',
            'meta_keywords' => 'nullable|string',
            'show_in_nav' => 'nullable|boolean',
            'nav_order' => 'nullable|integer',
            'nav_parent' => 'nullable|string',
            'nav_label' => 'nullable|string',
        ]);

        $page->update($validated);
        
        return response()->json($page->load('sections'));
    }

    /**
     * Get pages for navigation (public endpoint)
     * Returns all published pages, sorted by nav_order
     */
    public function getNavigation(Request $request)
    {
        $query = Page::published()
            ->select('id', 'slug', 'title', 'nav_label', 'nav_order', 'nav_parent', 'show_in_nav');

        // If ?nav_only=true, only return pages with show_in_nav enabled
        // Otherwise, return all published pages
        if ($request->boolean('nav_only')) {
            $query->where('show_in_nav', true);
        }

        $pages = $query->orderBy('nav_order')
            ->get()
            ->map(function ($page) {
                return [
                    'id' => $page->id,
                    'name' => $page->nav_label ?: $page->title,
                    'href' => '/pages/' . $page->slug,
                    'slug' => $page->slug,
                    'order' => $page->nav_order ?? 100,
                    'parent' => $page->nav_parent,
                    'show_in_nav' => $page->show_in_nav,
                ];
            });

        return response()->json($pages);
    }

    public function destroy($id)
    {
        $page = Page::findOrFail($id);
        $page->delete();
        
        return response()->json(['message' => 'Page deleted successfully']);
    }

    public function publish($id)
    {
        $page = Page::findOrFail($id);
        $page->update([
            'is_published' => true,
            'published_at' => now(),
        ]);
        
        return response()->json($page);
    }

    public function unpublish($id)
    {
        $page = Page::findOrFail($id);
        $page->update(['is_published' => false]);
        
        return response()->json($page);
    }

    public function duplicate($id)
    {
        $original = Page::with('sections')->findOrFail($id);
        
        $duplicate = $original->replicate();
        $duplicate->slug = $original->slug . '-copy-' . time();
        $duplicate->title = $original->title . ' (Copy)';
        $duplicate->is_published = false;
        $duplicate->save();

        // Copy sections
        foreach ($original->sections as $section) {
            $newSection = $section->replicate();
            $newSection->page_id = $duplicate->id;
            $newSection->save();
        }

        return response()->json($duplicate->load('sections'), 201);
    }
}
