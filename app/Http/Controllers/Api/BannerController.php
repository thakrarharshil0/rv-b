<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Banner;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class BannerController extends Controller
{
    public function index()
    {
        $banners = Banner::orderBy('priority')->get();

        return response()->json([
            'items' => $banners->map(fn($b) => [
                'id' => 'BNR-' . str_pad($b->id, 4, '0', STR_PAD_LEFT),
                'dbId' => $b->id,
                'title' => $b->title,
                'placement' => $b->placement,
                'channel' => $b->channel,
                'audience' => $b->audience,
                'format' => $b->format,
                'status' => $b->status,
                'priority' => $b->priority,
                'startDate' => $b->start_date ? $b->start_date->format('Y-m-d') : null,
                'endDate' => $b->end_date ? $b->end_date->format('Y-m-d') : null,
                'clicks' => $b->clicks,
                'impressions' => $b->impressions,
                'ctaText' => $b->cta_text,
                'cover' => $b->cover ? (str_starts_with($b->cover, 'http') ? $b->cover : asset(ltrim($b->cover, '/'))) : 'https://images.unsplash.com/photo-1517836357463-d25dfeac3438?auto=format&fit=crop&q=80&w=1200',
            ])
        ]);
    }

    public function store(Request $request)
    {
        try {
            $data = $request->validate([
                'title' => 'required|string|max:255',
                'placement' => 'nullable|string',
                'channel' => 'nullable|string',
                'audience' => 'nullable|string',
                'format' => 'nullable|string',
                'status' => 'nullable|string',
                'priority' => 'nullable|integer',
                'startDate' => 'nullable|date',
                'endDate' => 'nullable|date',
                'ctaText' => 'nullable|string',
                'image' => 'nullable|image|max:2048',
            ]);

            $imagePath = null;
            if ($request->hasFile('image')) {
                $path = $request->file('image')->store('banners', 'public');
                $imagePath = Storage::url($path);
            }

            $banner = Banner::create([
                'title' => $request->input('title'),
                'placement' => $request->input('placement', 'Homepage Hero'),
                'channel' => $request->input('channel', 'Web'),
                'audience' => $request->input('audience', 'All Visitors'),
                'format' => $request->input('format', 'Hero Image'),
                'status' => $request->input('status', 'Active'),
                'priority' => $request->input('priority', 1),
                'start_date' => $request->input('startDate'),
                'end_date' => $request->input('endDate'),
                'cta_text' => $request->input('ctaText'),
                'cover' => $imagePath,
            ]);

            return response()->json([
                'message' => 'Banner created successfully',
                'item' => $banner
            ], 201);
        } catch (\Exception $e) {
            \Log::error('Banner Store Error: ' . $e->getMessage());
            return response()->json(['message' => 'Failed to create banner', 'error' => $e->getMessage()], 500);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            \Log::info('Banner Update ID: ' . $id);
            \Log::info('Banner Update Data: ', $request->all());

            $banner = Banner::findOrFail($id);

            // Handle Multipart for update
            $data = $request->all();

            $banner->fill($request->only([
                'title', 'placement', 'channel', 'audience', 'status'
            ]));

            if ($request->has('format')) {
                $banner->setAttribute('format', $request->input('format'));
            }

            if ($request->has('priority')) {
                $banner->priority = (int)$request->input('priority');
            }

            if ($request->has('startDate')) {
                $banner->start_date = $request->input('startDate');
            }

            if ($request->has('endDate')) {
                $banner->end_date = $request->input('endDate');
            }

            if ($request->has('ctaText')) {
                $banner->cta_text = $request->input('ctaText');
            }

            if ($request->hasFile('image')) {
                // Delete old image if exists
                if ($banner->cover) {
                    $oldPath = str_replace('/storage/', '', $banner->cover);
                    Storage::disk('public')->delete($oldPath);
                }
                $path = $request->file('image')->store('banners', 'public');
                $banner->cover = Storage::url($path);
            }

            $banner->save();

            return response()->json([
                'message' => 'Banner updated successfully',
                'item' => $banner
            ]);
        } catch (\Exception $e) {
            \Log::error('Banner Update Error: ' . $e->getMessage());
            return response()->json(['message' => 'Failed to update banner', 'error' => $e->getMessage()], 500);
        }
    }

    public function destroy($id)
    {
        $banner = Banner::findOrFail($id);
        if ($banner->cover) {
            $oldPath = str_replace('/storage/', '', $banner->cover);
            Storage::disk('public')->delete($oldPath);
        }
        $banner->delete();

        return response()->json(['message' => 'Banner deleted successfully']);
    }
}
