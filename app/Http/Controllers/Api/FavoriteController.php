<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Favorite;
use App\Models\Product;
use Illuminate\Http\Request;

class FavoriteController extends Controller
{
    private function serializeFavorites(int $userId)
    {
        return Favorite::where('user_id', $userId)
            ->with('product')
            ->get()
            ->map(function ($favorite) {
                $p = $favorite->product;
                if (! $p) {
                    return null;
                }

                return [
                    'id' => (string) $p->id,
                    'name' => $p->name,
                    'category' => $p->category,
                    'price' => (float) $p->price,
                    'quantity' => (int) $p->quantity,
                    'image' => $p->image,
                    'sku' => $p->sku,
                    'description' => $p->description,
                    'isNew' => (bool) $p->is_new,
                ];
            })
            ->filter()
            ->values();
    }

    public function index(Request $request)
    {
        $user = $request->attributes->get('auth_user');

        return response()->json(['items' => $this->serializeFavorites($user->id)]);
    }

    public function store(Request $request)
    {
        $user = $request->attributes->get('auth_user');
        $product = $request->input('product');
        $productId = $request->input('productId') ?? ($product['id'] ?? null);

        if (! $productId) {
            return response()->json(['message' => 'productId is required'], 400);
        }

        $exists = Product::find($productId);
        if (! $exists) {
            return response()->json(['message' => 'Product not found'], 404);
        }

        Favorite::firstOrCreate([
            'user_id' => $user->id,
            'product_id' => $productId,
        ]);

        return response()->json(['items' => $this->serializeFavorites($user->id)]);
    }

    public function destroy(Request $request, string $productId)
    {
        $user = $request->attributes->get('auth_user');
        Favorite::where('user_id', $user->id)->where('product_id', $productId)->delete();

        return response()->json(['items' => $this->serializeFavorites($user->id)]);
    }
}
