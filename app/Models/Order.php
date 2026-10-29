<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'customer_details',
        'shipping_address',
        'billing_details',
        'shipping_option',
        'payment_method',
        'subtotal',
        'shipping_cost',
        'discount_code',
        'discount_amount',
        'total',
        'status',
    ];

    protected $casts = [
        'customer_details' => 'array',
        'shipping_address' => 'array',
        'billing_details' => 'array',
        'subtotal' => 'float',
        'shipping_cost' => 'float',
        'discount_amount' => 'float',
        'total' => 'float',
    ];

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function statusHistories(): HasMany
    {
        return $this->hasMany(OrderStatusHistory::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
