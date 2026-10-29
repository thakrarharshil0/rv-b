<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\FlashDeal;
use Illuminate\Http\Request;

class FlashDealController extends Controller
{
    public function index(Request $request)
    {
        $query = FlashDeal::withCount('products');

        if ($request->filled('search')) {
            $search = strtolower($request->search);
            $query->whereRaw('lower(title) like ?', ["%{$search}%"]);
        }

        $deals = $query->latest()->get()->map(function ($d) {
            return [
                'id' => $d->id,
                'title' => $d->title,
                'startDate' => $d->starts_at->format('Y-m-d'),
                'endDate' => $d->ends_at->format('Y-m-d'),
                'status' => $d->status,
                'activeProducts' => $d->products_count,
                'publish' => $d->is_published,
            ];
        });

        return response()->json($deals);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'title' => 'required|string|max:255',
            'startDate' => 'required|date',
            'endDate' => 'required|date',
            'status' => 'required|string',
            'publish' => 'required|boolean',
        ]);

        $deal = FlashDeal::create([
            'title' => $data['title'],
            'starts_at' => $data['startDate'],
            'ends_at' => $data['endDate'],
            'status' => $data['status'],
            'is_published' => $data['publish'],
        ]);

        return response()->json(['message' => 'Flash Deal created successfully', 'item' => $deal], 201);
    }

    public function update(Request $request, $id)
    {
        $deal = FlashDeal::findOrFail($id);

        $data = $request->validate([
            'title' => 'required|string|max:255',
            'startDate' => 'required|date',
            'endDate' => 'required|date',
            'status' => 'required|string',
            'publish' => 'required|boolean',
        ]);

        $deal->update([
            'title' => $data['title'],
            'starts_at' => $data['startDate'],
            'ends_at' => $data['endDate'],
            'status' => $data['status'],
            'is_published' => $data['publish'],
        ]);

        return response()->json(['message' => 'Flash Deal updated successfully']);
    }

    public function togglePublish($id)
    {
        $deal = FlashDeal::findOrFail($id);
        $deal->is_published = !$deal->is_published;
        $deal->save();

        return response()->json(['message' => 'Flash Deal publication status updated', 'publish' => $deal->is_published]);
    }

    public function destroy($id)
    {
        $deal = FlashDeal::findOrFail($id);
        $deal->delete();

        return response()->json(['message' => 'Flash Deal deleted successfully']);
    }
}
