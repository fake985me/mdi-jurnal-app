<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Project;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ProjectController extends Controller
{
    public function index()
    {
        return response()->json(Project::ordered()->get());
    }

    public function publicIndex(Request $request)
    {
        $query = Project::active()->ordered();

        if ($request->filled('category')) {
            $query->where('category', $request->query('category'));
        }

        return response()->json($query->get());
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'image_url' => 'nullable|string|max:255',
            'image' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:5120',
            'url' => 'nullable|string|max:255',
            'category' => 'nullable|string|max:255',
            'order' => 'nullable|integer',
            'is_active' => 'nullable|boolean',
        ]);

        if ($request->hasFile('image')) {
            $validated['image_url'] = Storage::disk('public')->url(
                $request->file('image')->store('projects', 'public')
            );
        }

        unset($validated['image']);

        return response()->json(Project::create($validated), 201);
    }

    public function show($id)
    {
        return response()->json(Project::findOrFail($id));
    }

    public function update(Request $request, $id)
    {
        $project = Project::findOrFail($id);
        $validated = $request->validate([
            'title' => 'sometimes|required|string|max:255',
            'description' => 'sometimes|required|string',
            'image_url' => 'nullable|string|max:255',
            'image' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:5120',
            'url' => 'nullable|string|max:255',
            'category' => 'nullable|string|max:255',
            'order' => 'nullable|integer',
            'is_active' => 'nullable|boolean',
        ]);

        $previousImageUrl = $project->image_url;
        if ($request->hasFile('image')) {
            $validated['image_url'] = Storage::disk('public')->url(
                $request->file('image')->store('projects', 'public')
            );
        }

        unset($validated['image']);
        $project->update($validated);
        if ($previousImageUrl !== $project->image_url) {
            $this->deleteManagedImage($previousImageUrl);
        }

        return response()->json($project);
    }

    public function destroy($id)
    {
        $project = Project::findOrFail($id);
        $imageUrl = $project->image_url;
        $project->delete();
        $this->deleteManagedImage($imageUrl);

        return response()->json(['message' => 'Project deleted successfully']);
    }

    private function deleteManagedImage(?string $imageUrl): void
    {
        if (!$imageUrl) {
            return;
        }

        $imagePath = parse_url($imageUrl, PHP_URL_PATH);
        $publicPath = parse_url(Storage::disk('public')->url(''), PHP_URL_PATH);
        $managedPrefix = rtrim((string) $publicPath, '/') . '/projects/';

        if (is_string($imagePath) && str_starts_with($imagePath, $managedPrefix)) {
            $filename = basename(rawurldecode(substr($imagePath, strlen($managedPrefix))));
            if ($filename !== '' && $filename !== '.' && $filename !== '..') {
                Storage::disk('public')->delete('projects/' . $filename);
            }
        }
    }
}
