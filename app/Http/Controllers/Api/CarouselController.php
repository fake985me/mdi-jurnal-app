<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CarouselSlide;
use Illuminate\Http\Request;

class CarouselController extends Controller
{
    /**
     * Display a listing of active carousel slides (public)
     */
    public function index()
    {
        $slides = CarouselSlide::active()->get();
        return response()->json($slides);
    }

    /**
     * Store a newly created carousel slide
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'nullable|string|max:255',
            'text' => 'required|string',
            'bg_image' => 'nullable|string|max:500',
            'center_img' => 'nullable|string|max:500',
            'img_left' => 'nullable|string|max:500',
            'mid_left' => 'nullable|string|max:500',
            'mid_right' => 'nullable|string|max:500',
            'img_right' => 'nullable|string|max:500',
            'use_component' => 'boolean',
            'component_name' => 'nullable|string|max:255',
            'order' => 'integer|min:0',
            'is_active' => 'boolean',
        ]);

        $slide = CarouselSlide::create($validated);
        return response()->json($slide, 201);
    }

    /**
     * Display the specified carousel slide
     */
    public function show($id)
    {
        $slide = CarouselSlide::findOrFail($id);
        return response()->json($slide);
    }

    /**
     * Update the specified carousel slide
     */
    public function update(Request $request, $id)
    {
        $slide = CarouselSlide::findOrFail($id);

        $validated = $request->validate([
            'title' => 'nullable|string|max:255',
            'text' => 'required|string',
            'bg_image' => 'nullable|string|max:500',
            'center_img' => 'nullable|string|max:500',
            'img_left' => 'nullable|string|max:500',
            'mid_left' => 'nullable|string|max:500',
            'mid_right' => 'nullable|string|max:500',
            'img_right' => 'nullable|string|max:500',
            'use_component' => 'boolean',
            'component_name' => 'nullable|string|max:255',
            'order' => 'integer|min:0',
            'is_active' => 'boolean',
        ]);

        $slide->update($validated);
        return response()->json($slide);
    }

    /**
     * Remove the specified carousel slide
     */
    public function destroy($id)
    {
        $slide = CarouselSlide::findOrFail($id);
        $slide->delete();

        return response()->json(['message' => 'Carousel slide deleted successfully']);
    }
}
