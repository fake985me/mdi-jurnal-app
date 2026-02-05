<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ContactInfo;
use Illuminate\Http\Request;

class ContactInfoController extends Controller
{
    public function index(Request $request)
    {
        $query = ContactInfo::active()->ordered();

        if ($request->has('type')) {
            $query->byType($request->type);
        }

        $contacts = $query->get();
        return response()->json($contacts);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'type' => 'required|string',
            'label' => 'nullable|string|max:255',
            'value' => 'required|string',
            'icon' => 'nullable|string',
            'order' => 'nullable|integer',
            'is_active' => 'nullable|boolean',
        ]);

        $contact = ContactInfo::create($validated);
        return response()->json($contact, 201);
    }

    public function show($id)
    {
        $contact = ContactInfo::findOrFail($id);
        return response()->json($contact);
    }

    public function update(Request $request, $id)
    {
        $contact = ContactInfo::findOrFail($id);

        $validated = $request->validate([
            'type' => 'sometimes|string',
            'label' => 'nullable|string|max:255',
            'value' => 'sometimes|string',
            'icon' => 'nullable|string',
            'order' => 'nullable|integer',
            'is_active' => 'nullable|boolean',
        ]);

        $contact->update($validated);
        return response()->json($contact);
    }

    public function destroy($id)
    {
        $contact = ContactInfo::findOrFail($id);
        $contact->delete();
        return response()->json(['message' => 'Contact info deleted successfully']);
    }
}
