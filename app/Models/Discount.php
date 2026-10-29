<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property string $code
 * @property string $name
 * @property string $type
 * @property float $value
 * @property float $minimum_order_total
 * @property int|null $usage_limit
 * @property int $usage_count
 * @property bool $is_active
 * @property \Illuminate\Support\Carbon|null $starts_at
 * @property \Illuminate\Support\Carbon|null $ends_at
 */
class Discount extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
        'type',
        'value',
        'minimum_order_total',
        'starts_at',
        'ends_at',
        'usage_limit',
        'usage_count',
        'is_active',
    ];

    protected $casts = [
        'value' => 'float',
        'minimum_order_total' => 'float',
        'usage_count' => 'integer',
        'is_active' => 'boolean',
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
    ];

    public function redemptions(): HasMany
    {
        return $this->hasMany(DiscountRedemption::class);
    }

    public function isActive(): bool
    {
        if (! $this->is_active) {
            return false;
        }

        $now = now();
        if ($this->starts_at && $now->lt($this->starts_at)) {
            return false;
        }

        if ($this->ends_at && $now->gt($this->ends_at)) {
            return false;
        }

        if ($this->usage_limit !== null && $this->usage_count >= $this->usage_limit) {
            return false;
        }

        return true;
    }

    public function canBeUsedBy(?User $user, float $subtotal): bool
    {
        if (! $this->isActive()) {
            return false;
        }

        if ($subtotal < (float) $this->minimum_order_total) {
            return false;
        }

        if ($user) {
            $alreadyUsed = $this->redemptions()->where('user_id', $user->id)->exists();
            if ($alreadyUsed) {
                return false;
            }
        }

        return true;
    }

    public function calculateDiscountAmount(float $subtotal): float
    {
        if ($subtotal <= 0) {
            return 0.0;
        }

        if ($this->type === 'fixed') {
            return min($subtotal, (float) $this->value);
        }

        return round($subtotal * ((float) $this->value / 100), 2);
    }
}
