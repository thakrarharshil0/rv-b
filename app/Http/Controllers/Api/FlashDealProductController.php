<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\FlashDeal;
use App\Models\FlashDealProduct;
use App\Models\Product;
use Illuminate\Http\Request;

class FlashDealProductController extends Controller
{
    public function store(Request $request, $flashDealId)
    {
        $data = $request->validate([
            'product_id' => 'required|exists:products,id',
            'discount_amount' => 'required|numeric|min:0',
            'discount_type' => 'required|string|in:fixed,percentage',
        ]);

        $dealProduct = FlashDealProduct::updateOrCreate(
            ['flash_deal_id' => $flashDealId, 'product_id' => $data['product_id']],
            [
                'discount_amount' => $data['discount_amount'],
                'discount_type' => $data['discount_type']
            ]
        );

        return response()->json([
            'message' => 'Product added to flash deal',
            'item' => $dealProduct
        ]);
    }

    public function destroy($flashDealId, $productId)
    {
        FlashDealProduct::where('flash_deal_id', $flashDealId)
            ->where('product_id', $productId)
            ->delete();

        return response()->json(['message' => 'Product removed from flash deal']);
    }

    public function products($flashDealId)
    {
        $products = FlashDealProduct::with('product')
            ->where('flash_deal_id', $flashDealId)
            ->get();

        return response()->json($products);
    }
}
