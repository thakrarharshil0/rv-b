<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\Request;

class SystemSettingController extends Controller
{
    public function index(Request $request)
    {
        $group = $request->query('group', 'general');
        $settings = Setting::where('group', $group)->get()->pluck('value', 'key');
        
        return response()->json($settings);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'settings' => 'required|array',
            'group' => 'nullable|string',
        ]);

        $group = $request->input('group', 'general');
        
        foreach ($request->input('settings') as $key => $value) {
            Setting::set($key, $value, $group);
        }

        return response()->json(['message' => 'Settings updated successfully']);
    }

    /**
     * Get specific setting publically
     */
    public function getPublicSettings()
    {
        $keys = ['store_features', 'store_name', 'contact_email', 'contact_phone', 'currency_symbol'];
        $settings = Setting::whereIn('key', $keys)->get()->pluck('value', 'key');
        
        // Defaults if not set
        if (!isset($settings['store_features'])) {
            $settings['store_features'] = ["Free Shipping", "30-Day Returns", "Perfect Fit Guarantee"];
        }
        
        return response()->json($settings);
    }
}
