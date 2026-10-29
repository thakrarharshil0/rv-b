<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\LoyaltyTransaction;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class LoyaltyPointController extends Controller
{
    public function index(Request $request)
    {
        $query = LoyaltyTransaction::with('user');

        if ($request->filled('search')) {
            $search = strtolower($request->search);
            $query->where(function ($q) use ($search) {
                $q->whereRaw('lower(id) like ?', ["%{$search}%"])
                  ->orWhereHas('user', function ($uq) use ($search) {
                      $uq->whereRaw('lower(name) like ?', ["%{$search}%"]);
                  });
            });
        }

        if ($request->filled('type') && $request->type !== 'All') {
            $query->where('type', $request->type);
        }

        if ($request->filled('customer_id') && $request->customer_id !== 'All') {
            $query->where('user_id', $request->customer_id);
        }

        if ($request->filled('start_date')) {
            $query->whereDate('created_at', '>=', $request->start_date);
        }

        if ($request->filled('end_date')) {
            $query->whereDate('created_at', '<=', $request->end_date);
        }

        $transactions = $query->latest()->get()->map(fn ($t) => [
            'id' => $t->id,
            'createdAt' => $t->created_at->format('Y-m-d'),
            'customer' => $t->user->name,
            'credit' => (float) $t->credit,
            'debit' => (float) $t->debit,
            'balance' => (float) $t->balance,
            'type' => $t->type,
        ]);

        return response()->json($transactions);
    }

    public function stats()
    {
        return response()->json([
            'total_balance' => (float) User::where('role', 'customer')->sum('loyalty_points'),
            'total_credit' => (float) LoyaltyTransaction::sum('credit'),
            'total_debit' => (float) LoyaltyTransaction::sum('debit'),
        ]);
    }
}
