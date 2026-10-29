<?php

namespace App\Http\Controllers;

use App\Models\Bundle;
use Illuminate\Http\Request;

class BundleController extends Controller
{
    public function index()
    {
        return response()->json(Bundle::where('is_active', true)->get());
    }

    public function adminIndex()
    {
        return response()->json(Bundle::all());
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'discount_text' => 'nullable|string',
            'original_price' => 'required|numeric',
            'sale_price' => 'required|numeric',
            'icon' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        $bundle = Bundle::create($data);
        return response()->json($bundle, 201);
    }

    public function show(Bundle $bundle)
    {
        return response()->json($bundle);
    }

    public function update(Request $request, Bundle $bundle)
    {
        $data = $request->validate([
            'title' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'discount_text' => 'nullable|string',
            'original_price' => 'sometimes|numeric',
            'sale_price' => 'sometimes|numeric',
            'icon' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        $bundle->update($data);
        return response()->json($bundle);
    }

    public function destroy(Bundle $bundle)
    {
        $bundle->delete();
        return response()->json(['message' => 'Bundle deleted']);
    }
}
