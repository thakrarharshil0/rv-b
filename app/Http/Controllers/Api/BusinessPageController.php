<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\BusinessPage;
use Illuminate\Http\Request;

class BusinessPageController extends Controller
{
    public function index()
    {
        return response()->json(BusinessPage::orderBy('id', 'desc')->get());
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'nullable|string',
            'is_default' => 'nullable|boolean',
            'is_active' => 'nullable|boolean',
            'meta_title' => 'nullable|string|max:255',
            'meta_description' => 'nullable|string',
        ]);

        $page = BusinessPage::create($validated);
        return response()->json($page);
    }

    public function show(BusinessPage $businessPage)
    {
        return response()->json($businessPage);
    }

    public function update(Request $request, BusinessPage $businessPage)
    {
        $validated = $request->validate([
            'title' => 'nullable|string|max:255',
            'content' => 'nullable|string',
            'is_active' => 'nullable|boolean',
            'meta_title' => 'nullable|string|max:255',
            'meta_description' => 'nullable|string',
        ]);

        $businessPage->update($validated);
        return response()->json($businessPage);
    }

    public function destroy(BusinessPage $businessPage)
    {
        if ($businessPage->is_default) {
            return response()->json(['message' => 'Cannot delete default pages'], 403);
        }
        $businessPage->delete();
        return response()->json(['message' => 'Page deleted']);
    }

    public function getPublicPage($slug)
    {
        $page = BusinessPage::where('slug', $slug)->where('is_active', true)->firstOrFail();
        return response()->json($page);
    }
}
