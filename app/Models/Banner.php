<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Banner extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'placement',
        'channel',
        'audience',
        'format',
        'status',
        'priority',
        'start_date',
        'end_date',
        'clicks',
        'impressions',
        'cta_text',
        'cover',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'priority' => 'integer',
        'clicks' => 'integer',
        'impressions' => 'integer',
    ];
}
