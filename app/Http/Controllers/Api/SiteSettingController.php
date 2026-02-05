<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SiteSetting;
use Illuminate\Http\Request;

class SiteSettingController extends Controller
{
    public function index(Request $request)
    {
        $query = SiteSetting::query();

        if ($request->has('group')) {
            $query->byGroup($request->group);
        }

        $settings = $query->get();

        // Return as key-value pairs if format=simple, otherwise return full objects
        if ($request->get('format') === 'simple') {
            $formatted = $settings->pluck('value', 'key');
            return response()->json($formatted);
        }

        return response()->json($settings);
    }

    public function show($id)
    {
        $setting = SiteSetting::findOrFail($id);
        return response()->json($setting);
    }

    public function update(Request $request, $id)
    {
        $setting = SiteSetting::findOrFail($id);

        $validated = $request->validate([
            'value' => 'nullable|string',
            'label' => 'nullable|string',
        ]);

        $setting->update($validated);
        return response()->json($setting);
    }

    public function bulkUpdate(Request $request)
    {
        $settings = $request->validate([
            '*.key' => 'required|string',
            '*.value' => 'nullable|string',
        ]);

        foreach ($settings as $settingData) {
            SiteSetting::set($settingData['key'], $settingData['value']);
        }

        return response()->json(['message' => 'Settings updated successfully']);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'key' => 'required|string|unique:site_settings,key',
            'value' => 'nullable|string',
            'group' => 'required|string',
            'type' => 'required|string',
            'label' => 'nullable|string',
        ]);

        $setting = SiteSetting::create($validated);
        return response()->json($setting, 201);
    }

    public function destroy($id)
    {
        $setting = SiteSetting::findOrFail($id);
        $setting->delete();
        return response()->json(['message' => 'Setting deleted successfully']);
    }
}
