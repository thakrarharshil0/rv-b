<?php

namespace Database\Seeders;

use App\Models\Order;
use App\Models\RefundRequest;
use App\Models\User;
use Illuminate\Database\Seeder;

class RefundRequestSeeder extends Seeder
{
    public function run(): void
    {
        $orders = Order::take(5)->get();
        $user = User::where('role', 'customer')->first() ?? User::first();

        if ($orders->isEmpty() || !$user) {
            return;
        }

        $reasons = [
            'Damaged product received',
            'Wrong size delivered',
            'Product not as described',
            'Duplicate payment issue',
            'Late delivery',
        ];

        $statuses = ['pending', 'approved', 'refunded', 'rejected'];

        foreach ($orders as $index => $order) {
            RefundRequest::create([
                'order_id' => $order->id,
                'user_id' => $user->id,
                'amount' => $order->total * 0.5,
                'reason' => $reasons[$index % count($reasons)],
                'status' => $statuses[$index % count($statuses)],
                'priority' => ['low', 'medium', 'high'][rand(0, 2)],
                'payment_method' => $order->payment_method ?? 'UPI',
            ]);
        }
    }
}
