<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CategoryController extends Controller
{
    public function index()
    {
        $categories = Category::withCount(['subCategories'])
            ->orderBy('priority')
            ->get();

        return response()->json([
            'items' => $categories->map(fn($c) => [
                'id' => $c->id,
                'catId' => 'CAT-' . str_pad($c->id, 4, '0', STR_PAD_LEFT),
                'name' => $c->name,
                'slug' => $c->slug,
                'icon' => $c->icon ?? '📦',
                'priority' => $c->priority,
                'visibility' => $c->is_active ? 'Active' : 'Draft',
                'inHomeCategory' => (bool)$c->show_on_home,
                'totalProducts' => \App\Models\Product::where('category', $c->name)->count(),
                'subCategories' => $c->sub_categories_count,
                'updatedBy' => 'Admin Team',
            ])
        ]);
    }

    public function store(Request $request)
    {
        try {
            $request->validate([
                'name' => 'required|string|max:255',
                'priority' => 'nullable|integer',
                'icon' => 'nullable|string',
                'is_active' => 'nullable|boolean',
                'show_on_home' => 'nullable|boolean',
            ]);

            $category = Category::create([
                'name' => $request->name,
                'slug' => Str::slug($request->name),
                'icon' => $request->icon,
                'priority' => $request->priority ?? 1,
                'is_active' => $request->is_active ?? true,
                'show_on_home' => $request->show_on_home ?? false,
            ]);

            return response()->json([
                'message' => 'Category created successfully',
                'item' => $category
            ], 201);
        } catch (\Exception $e) {
            \Log::error('Category Store Error: ' . $e->getMessage());
            return response()->json([
                'message' => 'Failed to create category',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $category = Category::find($id);
            if (!$category) {
                return response()->json(['message' => 'Category not found'], 404);
            }

            $request->validate([
                'name' => 'sometimes|string|max:255',
                'priority' => 'sometimes|integer',
                'icon' => 'sometimes|string',
                'is_active' => 'sometimes|boolean',
                'show_on_home' => 'sometimes|boolean',
            ]);

            if ($request->has('name')) {
                $category->name = $request->name;
                $category->slug = Str::slug($request->name);
            }

            if ($request->has('priority')) $category->priority = $request->priority;
            if ($request->has('icon')) $category->icon = $request->icon;
            if ($request->has('is_active')) $category->is_active = $request->is_active;
            if ($request->has('show_on_home')) $category->show_on_home = $request->show_on_home;

            $category->save();

            return response()->json([
                'message' => 'Category updated successfully',
                'item' => $category
            ]);
        } catch (\Exception $e) {
            \Log::error('Category Update Error: ' . $e->getMessage());
            return response()->json([
                'message' => 'Failed to update category',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function destroy($id)
    {
        $category = Category::find($id);
        if (!$category) {
            return response()->json(['message' => 'Category not found'], 404);
        }

        $category->delete();

        return response()->json(['message' => 'Category deleted successfully']);
    }
}
