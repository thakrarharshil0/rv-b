<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Cart;
use App\Models\Product;
use Illuminate\Http\Request;

class CartController extends Controller
{
    private function serializeCartItems(int $userId)
    {
        return Cart::where('user_id', $userId)->get()->map(function ($item) {
            return [
                'id' => (string) $item->id,
                'productId' => (string) $item->product_id,
                'name' => $item->name,
                'price' => (float) $item->price,
                'image' => $item->image,
                'size' => $item->size,
                'color' => $item->color,
                'quantity' => (int) $item->quantity,
                'createdAt' => $item->created_at,
            ];
        })->values();
    }

    public function index(Request $request)
    {
        $user = $request->attributes->get('auth_user');
        return response()->json(['items' => $this->serializeCartItems($user->id)]);
    }

    public function store(Request $request)
    {
        $user = $request->attributes->get('auth_user');

        $data = $request->validate([
            'id' => 'nullable',
            'productId' => 'nullable',
            'name' => 'nullable|string',
            'price' => 'nullable|numeric',
            'image' => 'nullable|string',
            'size' => 'nullable|string',
            'color' => 'nullable|string',
            'quantity' => 'nullable|integer|min:1',
        ]);

        $productId = $data['productId'] ?? $data['id'] ?? null;
        if (! $productId) {
            return response()->json(['message' => 'productId is required'], 400);
        }

        $product = Product::find($productId);
        if (! $product) {
            return response()->json(['message' => 'Product not found'], 404);
        }

        $item = Cart::firstOrNew([
            'user_id' => $user->id,
            'product_id' => $productId,
            'size' => $data['size'] ?? null,
        ]);

        $requestedQuantity = (int) ($data['quantity'] ?? 1);
        $currentQuantity = $item->exists ? (int) $item->quantity : 0;
        $finalQuantity = $currentQuantity + $requestedQuantity;

        if ($finalQuantity > (int) $product->quantity) {
            return response()->json(['message' => 'Requested quantity exceeds available stock'], 422);
        }

        if ($item->exists) {
            $item->quantity = $finalQuantity;
            $item->save();
        } else {
            $item->fill([
                'name' => $data['name'] ?? $product->name,
                'price' => $data['price'] ?? $product->price,
                'image' => $data['image'] ?? $product->image,
                'color' => $data['color'] ?? null,
                'quantity' => $requestedQuantity,
            ]);
            $item->save();
        }

        return response()->json(['items' => $this->serializeCartItems($user->id)], 201);
    }

    public function update(Request $request, string $itemId)
    {
        $user = $request->attributes->get('auth_user');
        $data = $request->validate(['quantity' => 'required|integer']);

        $item = Cart::where('user_id', $user->id)->where('id', $itemId)->first();

        if (! $item) {
            return response()->json(['message' => 'Cart item not found'], 404);
        }

        if ($data['quantity'] <= 0) {
            $item->delete();
        } else {
            $item->quantity = $data['quantity'];
            $item->save();
        }

        return response()->json(['items' => $this->serializeCartItems($user->id)]);
    }

    public function destroy(Request $request, string $itemId)
    {
        $user = $request->attributes->get('auth_user');
        Cart::where('user_id', $user->id)->where('id', $itemId)->delete();

        return response()->json(['items' => $this->serializeCartItems($user->id)]);
    }
}
