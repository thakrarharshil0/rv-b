<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SubCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class SubCategoryController extends Controller
{
    public function index()
    {
        $subCategories = SubCategory::with('category')
            ->orderBy('priority')
            ->get();

        return response()->json([
            'items' => $subCategories->map(fn($s) => [
                'id' => $s->id,
                'subId' => 'SUB-' . str_pad($s->id, 4, '0', STR_PAD_LEFT),
                'name' => $s->name,
                'parentCategory' => $s->category?->name ?? 'None',
                'categoryId' => $s->category_id,
                'priority' => $s->priority,
                'status' => $s->is_active ? 'Active' : 'Draft',
                'updatedBy' => 'Admin Team',
                'totalProducts' => 0, // Placeholder
            ])
        ]);
    }

    public function store(Request $request)
    {
        try {
            $request->validate([
                'category_id' => 'required|exists:categories,id',
                'name' => 'required|string|max:255',
                'priority' => 'nullable|integer',
                'is_active' => 'nullable|boolean',
            ]);

            $subCategory = SubCategory::create([
                'category_id' => $request->category_id,
                'name' => $request->name,
                'slug' => Str::slug($request->name),
                'priority' => $request->priority ?? 1,
                'is_active' => $request->is_active ?? true,
            ]);

            return response()->json([
                'message' => 'Sub category created successfully',
                'item' => $subCategory
            ], 201);
        } catch (\Exception $e) {
            \Log::error('SubCategory Store Error: ' . $e->getMessage());
            return response()->json([
                'message' => 'Failed to create sub category',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $subCategory = SubCategory::find($id);
            if (!$subCategory) {
                return response()->json(['message' => 'Sub category not found'], 404);
            }

            $request->validate([
                'category_id' => 'sometimes|exists:categories,id',
                'name' => 'sometimes|string|max:255',
                'priority' => 'sometimes|integer',
                'is_active' => 'sometimes|boolean',
            ]);

            if ($request->has('category_id')) $subCategory->category_id = $request->category_id;
            if ($request->has('name')) {
                $subCategory->name = $request->name;
                $subCategory->slug = Str::slug($request->name);
            }
            if ($request->has('priority')) $subCategory->priority = $request->priority;
            if ($request->has('is_active')) $subCategory->is_active = $request->is_active;

            $subCategory->save();

            return response()->json([
                'message' => 'Sub category updated successfully',
                'item' => $subCategory
            ]);
        } catch (\Exception $e) {
            \Log::error('SubCategory Update Error: ' . $e->getMessage());
            return response()->json([
                'message' => 'Failed to update sub category',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function destroy($id)
    {
        $subCategory = SubCategory::find($id);
        if (!$subCategory) {
            return response()->json(['message' => 'Sub category not found'], 404);
        }

        $subCategory->delete();

        return response()->json(['message' => 'Sub category deleted successfully']);
    }
}
