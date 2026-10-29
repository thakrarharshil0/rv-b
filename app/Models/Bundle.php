<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Bundle extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'description',
        'discount_text',
        'original_price',
        'sale_price',
        'icon',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'original_price' => 'float',
        'sale_price' => 'float',
    ];
}
