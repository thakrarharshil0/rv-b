<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\RefundRequest;
use Illuminate\Http\Request;

class RefundController extends Controller
{
    public function index(Request $request)
    {
        $role = $request->attributes->get('auth_role');
        $user = $request->attributes->get('auth_user');
        $status = $request->query('status');

        $query = RefundRequest::with(['order', 'user']);

        if (strtolower($role) !== 'admin') {
            $query->where('user_id', $user->id);
        }

        if ($status && $status !== 'all') {
            $query->where('status', $status);
        }

        $refunds = $query->latest()->get();

        return response()->json([
            'items' => $refunds->map(fn ($r) => [
                'id' => $r->id,
                'refundId' => '#RFD-' . str_pad($r->id, 4, '0', STR_PAD_LEFT),
                'orderId' => '#ORD-' . str_pad($r->order_id, 6, '0', STR_PAD_LEFT),
                'customer' => $r->user->name,
                'email' => $r->user->email,
                'requestedOn' => $r->created_at->toDateString(),
                'amount' => (float) $r->amount,
                'priority' => ucfirst($r->priority),
                'status' => ucfirst($r->status),
                'assignee' => $r->assignee?->name ?? 'Unassigned',
                'reason' => $r->reason,
                'paymentMethod' => $r->payment_method,
            ])
        ]);
    }

    public function show(Request $request, $id)
    {
        $role = $request->attributes->get('auth_role');
        $user = $request->attributes->get('auth_user');

        $refund = RefundRequest::with(['order', 'user', 'assignee'])->find($id);

        if (!$refund) {
            return response()->json(['message' => 'Refund request not found'], 404);
        }

        if (strtolower($role) !== 'admin' && $refund->user_id !== $user->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        return response()->json([
            'item' => [
                'id' => $refund->id,
                'refundId' => '#RFD-' . str_pad($refund->id, 4, '0', STR_PAD_LEFT),
                'orderId' => '#ORD-' . str_pad($refund->order_id, 6, '0', STR_PAD_LEFT),
                'customer' => $refund->user->name,
                'email' => $refund->user->email,
                'requestedOn' => $refund->created_at->toDateString(),
                'amount' => (float) $refund->amount,
                'priority' => ucfirst($refund->priority),
                'status' => ucfirst($refund->status),
                'assignee' => $refund->assignee?->name ?? 'Unassigned',
                'reason' => $refund->reason,
                'paymentMethod' => $refund->payment_method,
                'adminNote' => $refund->admin_note,
            ]
        ]);
    }

    public function update(Request $request, $id)
    {
        $role = $request->attributes->get('auth_role');
        if (strtolower($role) !== 'admin') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $refund = RefundRequest::find($id);
        if (!$refund) {
            return response()->json(['message' => 'Refund request not found'], 404);
        }

        $request->validate([
            'status' => 'sometimes|string|in:pending,approved,refunded,rejected',
            'admin_note' => 'sometimes|string|nullable',
        ]);

        if ($request->has('status')) {
            $refund->status = $request->status;
        }

        if ($request->has('admin_note')) {
            $refund->admin_note = $request->admin_note;
        }

        $refund->save();

        return response()->json([
            'message' => 'Refund request updated successfully',
            'item' => $refund
        ]);
    }

    public function store(Request $request)
    {
        $user = $request->attributes->get('auth_user');

        $request->validate([
            'order_id' => 'required|exists:orders,id',
            'amount' => 'required|numeric',
            'reason' => 'required|string',
            'payment_method' => 'nullable|string',
        ]);

        $refund = RefundRequest::create([
            'order_id' => $request->order_id,
            'user_id' => $user->id,
            'amount' => $request->amount,
            'reason' => $request->reason,
            'payment_method' => $request->payment_method,
            'status' => 'pending',
            'priority' => 'medium',
        ]);

        return response()->json([
            'message' => 'Refund request submitted successfully',
            'item' => $refund
        ], 201);
    }
}
