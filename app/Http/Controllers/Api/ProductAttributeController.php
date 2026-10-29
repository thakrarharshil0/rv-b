<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ProductAttribute;
use Illuminate\Http\Request;

class ProductAttributeController extends Controller
{
    public function index()
    {
        $attributes = ProductAttribute::latest()->get();

        return response()->json([
            'items' => $attributes->map(fn($a) => [
                'id' => $a->id,
                'attrId' => 'ATTR-' . str_pad($a->id, 3, '0', STR_PAD_LEFT),
                'name' => $a->name,
                'inputType' => $a->input_type,
                'values' => $a->values_count,
                'usage' => $a->usage_count,
                'status' => $a->status,
                'updatedBy' => $a->updated_by,
            ])
        ]);
    }

    public function store(Request $request)
    {
        try {
            $request->validate([
                'name' => 'required|string|max:255',
                'input_type' => 'nullable|string',
                'status' => 'nullable|string',
                'values' => 'nullable|integer',
                'usage' => 'nullable|integer',
            ]);

            $attribute = ProductAttribute::create([
                'name' => $request->name,
                'input_type' => $request->input_type ?? 'Dropdown',
                'status' => $request->status ?? 'Active',
                'values_count' => $request->values ?? 0,
                'usage_count' => $request->usage ?? 0,
                'updated_by' => 'Admin Team',
            ]);

            return response()->json([
                'message' => 'Attribute created successfully',
                'item' => $attribute
            ], 201);
        } catch (\Exception $e) {
            \Log::error('Attribute Store Error: ' . $e->getMessage());
            return response()->json(['message' => 'Failed to create attribute', 'error' => $e->getMessage()], 500);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $attribute = ProductAttribute::find($id);
            if (!$attribute) {
                return response()->json(['message' => 'Attribute not found'], 404);
            }

            $request->validate([
                'name' => 'sometimes|string|max:255',
                'input_type' => 'sometimes|string',
                'status' => 'sometimes|string',
                'values' => 'sometimes|integer',
                'usage' => 'sometimes|integer',
            ]);

            if ($request->has('name')) $attribute->name = $request->name;
            if ($request->has('input_type')) $attribute->input_type = $request->input_type;
            if ($request->has('status')) $attribute->status = $request->status;
            if ($request->has('values')) $attribute->values_count = $request->values;
            if ($request->has('usage')) $attribute->usage_count = $request->usage;

            $attribute->save();

            return response()->json([
                'message' => 'Attribute updated successfully',
                'item' => $attribute
            ]);
        } catch (\Exception $e) {
            \Log::error('Attribute Update Error: ' . $e->getMessage());
            return response()->json(['message' => 'Failed to update attribute', 'error' => $e->getMessage()], 500);
        }
    }

    public function destroy($id)
    {
        $attribute = ProductAttribute::find($id);
        if (!$attribute) {
            return response()->json(['message' => 'Attribute not found'], 404);
        }

        $attribute->delete();

        return response()->json(['message' => 'Attribute deleted successfully']);
    }
}
