<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Discount;
use Illuminate\Http\Request;

class DiscountController extends Controller
{
    private function serializeDiscount(Discount $discount, float $subtotal = 0.0): array
    {
        $amount = $discount->calculateDiscountAmount($subtotal);

        return [
            'id' => (string) $discount->id,
            'code' => $discount->code,
            'name' => $discount->name,
            'type' => $discount->type,
            'value' => (float) $discount->value,
            'minimumOrderTotal' => (float) $discount->minimum_order_total,
            'usageLimit' => $discount->usage_limit,
            'usageCount' => (int) $discount->usage_count,
            'discountAmount' => $amount,
            'finalTotal' => max(0, $subtotal - $amount),
            'isActive' => $discount->isActive(),
            'startsAt' => $discount->starts_at,
            'endsAt' => $discount->ends_at,
        ];
    }

    public function validateCode(Request $request)
    {
        $data = $request->validate([
            'code' => 'required|string|max:64',
            'subtotal' => 'nullable|numeric|min:0',
        ]);

        $discount = Discount::whereRaw('upper(code) = ?', [strtoupper($data['code'])])->first();

        if (! $discount || ! $discount->isActive()) {
            return response()->json(['message' => 'Discount code is invalid or expired'], 404);
        }

        $subtotal = (float) ($data['subtotal'] ?? 0);
        if ($subtotal > 0 && ! $discount->canBeUsedBy(null, $subtotal)) {
            return response()->json(['message' => 'Discount does not apply to this subtotal'], 422);
        }

        return response()->json(['discount' => $this->serializeDiscount($discount, $subtotal)]);
    }

    public function publicCoupons()
    {
        $now = now();
        $discounts = Discount::where('is_active', true)
            ->where(function ($q) use ($now) {
                $q->whereNull('starts_at')->orWhere('starts_at', '<=', $now);
            })
            ->where(function ($q) use ($now) {
                $q->whereNull('ends_at')->orWhere('ends_at', '>=', $now);
            })
            ->latest()
            ->take(6)
            ->get()
            ->map(function ($d) {
                return [
                    'id' => $d->id,
                    'code' => $d->code,
                    'title' => $d->name,
                    'type' => $d->type,
                    'value' => (float) $d->value,
                    'description' => $d->type === 'fixed' 
                        ? 'Flat ₹' . number_format($d->value) . ' off' . ($d->minimum_order_total > 0 ? ' on orders above ₹' . number_format($d->minimum_order_total) : '')
                        : 'Extra ' . $d->value . '% off' . ($d->minimum_order_total > 0 ? ' on orders above ₹' . number_format($d->minimum_order_total) : ''),
                    'minimumOrderTotal' => (float) $d->minimum_order_total,
                ];
            });

        return response()->json($discounts);
    }

    public function index(Request $request)
    {
        $query = Discount::query();

        if ($request->filled('search')) {
            $search = strtolower($request->search);
            $query->where(function ($q) use ($search) {
                $q->whereRaw('lower(code) like ?', ["%{$search}%"])
                  ->orWhereRaw('lower(name) like ?', ["%{$search}%"]);
            });
        }

        $discounts = $query->latest()->get()->map(function ($d) {
            return [
                'id' => $d->id,
                'coupon' => $d->name,
                'couponType' => $d->type === 'fixed' ? 'Discount on Purchase' : 'Percentage Discount',
                'duration' => ($d->starts_at?->format('M d, Y') ?? 'N/A') . ' - ' . ($d->ends_at?->format('M d, Y') ?? 'N/A'),
                'limit' => $d->usage_limit ?? 0,
                'totalUsed' => $d->usage_count,
                'discountBearer' => 'Admin',
                'status' => $d->is_active,
                'code' => $d->code,
                'discountType' => $d->type === 'fixed' ? 'Amount' : 'Percentage',
                'discountAmount' => $d->value,
                'minimumPurchase' => $d->minimum_order_total,
                'startDate' => $d->starts_at?->format('Y-m-d'),
                'endDate' => $d->ends_at?->format('Y-m-d'),
            ];
        });

        return response()->json($discounts);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'couponTitle' => 'required|string|max:255',
            'couponCode' => 'required|string|max:64|unique:discounts,code',
            'discountType' => 'required|string', // Amount or Percentage
            'discountAmount' => 'required|numeric|min:0',
            'minimumPurchase' => 'required|numeric|min:0',
            'limitPerUser' => 'nullable|integer|min:0',
            'startDate' => 'nullable|date',
            'endDate' => 'nullable|date',
        ]);

        $discount = Discount::create([
            'name' => $data['couponTitle'],
            'code' => strtoupper($data['couponCode']),
            'type' => $data['discountType'] === 'Amount' ? 'fixed' : 'percentage',
            'value' => $data['discountAmount'],
            'minimum_order_total' => $data['minimumPurchase'],
            'usage_limit' => $data['limitPerUser'],
            'starts_at' => $data['startDate'],
            'ends_at' => $data['endDate'],
            'is_active' => true,
        ]);

        return response()->json(['message' => 'Coupon created successfully', 'item' => $discount], 201);
    }

    public function update(Request $request, $id)
    {
        $discount = Discount::findOrFail($id);

        $data = $request->validate([
            'couponTitle' => 'required|string|max:255',
            'couponCode' => 'required|string|max:64|unique:discounts,code,' . $id,
            'discountType' => 'required|string',
            'discountAmount' => 'required|numeric|min:0',
            'minimumPurchase' => 'required|numeric|min:0',
            'limitPerUser' => 'nullable|integer|min:0',
            'startDate' => 'nullable|date',
            'endDate' => 'nullable|date',
        ]);

        $discount->update([
            'name' => $data['couponTitle'],
            'code' => strtoupper($data['couponCode']),
            'type' => $data['discountType'] === 'Amount' ? 'fixed' : 'percentage',
            'value' => $data['discountAmount'],
            'minimum_order_total' => $data['minimumPurchase'],
            'usage_limit' => $data['limitPerUser'],
            'starts_at' => $data['startDate'],
            'ends_at' => $data['endDate'],
        ]);

        return response()->json(['message' => 'Coupon updated successfully']);
    }

    public function toggleStatus($id)
    {
        $discount = Discount::findOrFail($id);
        $discount->is_active = !$discount->is_active;
        $discount->save();

        return response()->json(['message' => 'Coupon status updated', 'status' => $discount->is_active]);
    }

    public function destroy($id)
    {
        $discount = Discount::findOrFail($id);
        $discount->delete();

        return response()->json(['message' => 'Coupon deleted successfully']);
    }
}
