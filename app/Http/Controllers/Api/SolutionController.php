<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Solution;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class SolutionController extends Controller
{
    public function index()
    {
        $solutions = Solution::ordered()->get();
        return response()->json($solutions);
    }

    public function publicIndex()
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
            'image' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:5120',
            'order' => 'nullable|integer',
            'is_active' => 'nullable|boolean',
        ]);

        if ($request->hasFile('image')) {
            $validated['image_url'] = Storage::disk('public')->url(
                $request->file('image')->store('solutions', 'public')
            );
        }

        unset($validated['image']);
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
            'image' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:5120',
            'order' => 'nullable|integer',
            'is_active' => 'nullable|boolean',
        ]);

        $previousImageUrl = $solution->image_url;
        if ($request->hasFile('image')) {
            $validated['image_url'] = Storage::disk('public')->url(
                $request->file('image')->store('solutions', 'public')
            );
        }

        unset($validated['image']);
        $solution->update($validated);
        if ($previousImageUrl !== $solution->image_url) {
            $this->deleteManagedImage($previousImageUrl);
        }

        return response()->json($solution);
    }

    public function destroy($id)
    {
        $solution = Solution::findOrFail($id);
        $imageUrl = $solution->image_url;
        $solution->delete();
        $this->deleteManagedImage($imageUrl);

        return response()->json(['message' => 'Solution deleted successfully']);
    }

    private function deleteManagedImage(?string $imageUrl): void
    {
        if (!$imageUrl) {
            return;
        }

        $imagePath = parse_url($imageUrl, PHP_URL_PATH);
        $publicPath = parse_url(Storage::disk('public')->url(''), PHP_URL_PATH);
        $managedPrefix = rtrim((string) $publicPath, '/') . '/solutions/';

        if (is_string($imagePath) && str_starts_with($imagePath, $managedPrefix)) {
            $filename = basename(rawurldecode(substr($imagePath, strlen($managedPrefix))));
            if ($filename !== '' && $filename !== '.' && $filename !== '..') {
                Storage::disk('public')->delete('solutions/' . $filename);
            }
        }
    }
}
