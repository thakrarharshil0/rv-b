<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;

class CustomerController extends Controller
{
    public function index(Request $request)
    {
        $query = User::where('role', 'customer')
            ->withCount('orders')
            ->withSum('orders as spent', 'total');

        if ($request->filled('search')) {
            $search = strtolower($request->search);
            $query->where(function ($q) use ($search) {
                $q->whereRaw('lower(name) like ?', ["%{$search}%"])
                  ->orWhereRaw('lower(email) like ?', ["%{$search}%"]);
            });
        }

        $customers = $query->orderBy('created_at', 'desc')->get();

        $formatted = $customers->map(function ($c) {
            // Get latest order to find city and phone if available
            $latestOrder = $c->orders()->latest()->first();
            $city = 'Unknown';
            $phone = 'Not Provided';

            if ($latestOrder) {
                $address = $latestOrder->shipping_address;
                if (is_array($address)) {
                    $city = $address['city'] ?? 'Unknown';
                    $phone = $address['phone'] ?? 'Not Provided';
                } elseif (is_string($address)) {
                    $decoded = json_decode($address, true);
                    $city = $decoded['city'] ?? 'Unknown';
                    $phone = $decoded['phone'] ?? 'Not Provided';
                }
            }

            return [
                'id' => $c->id,
                'name' => $c->name,
                'email' => $c->email,
                'phone' => $phone,
                'orders' => $c->orders_count,
                'spent' => (float) ($c->spent ?? 0),
                'city' => $city,
                'status' => 'Active', // Default for now
                'created_at' => $c->created_at,
            ];
        });

        return response()->json($formatted);
    }
}
