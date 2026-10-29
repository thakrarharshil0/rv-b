<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property string|int $id
 * @property string $name
 * @property string|null $category
 * @property float $price
 * @property float|null $original_price
 * @property int $quantity
 * @property string|null $image
 * @property string|null $sku
 * @property string|null $description
 * @property bool $is_new
 * @property bool $is_featured
 * @property string|null $fit
 * @property string|null $material
 * @property string|null $care_instructions
 * @property array|null $sizes
 * @property array|null $colors
 * @property array|null $features
 * @property array|null $gallery
 * @property float $rating_average
 * @property int $reviews_count
 */
class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'id',
        'name',
        'category',
        'price',
        'original_price',
        'quantity',
        'image',
        'sku',
        'description',
        'is_new',
        'is_featured',
        'is_limited',
        'fit',
        'material',
        'care_instructions',
        'sizes',
        'colors',
        'features',
        'gallery',
        'min_stock',
        'max_stock',
        'rating_average',
        'reviews_count',
    ];

    protected $casts = [
        'is_new' => 'boolean',
        'is_featured' => 'boolean',
        'is_limited' => 'boolean',
        'price' => 'float',
        'original_price' => 'float',
        'sizes' => 'array',
        'colors' => 'array',
        'features' => 'array',
        'gallery' => 'array',
        'rating_average' => 'float',
        'reviews_count' => 'integer',
    ];

    public function reviews(): HasMany
    {
        return $this->hasMany(ProductReview::class);
    }
}
