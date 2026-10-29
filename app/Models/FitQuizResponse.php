<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FitQuizResponse extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'answers',
        'recommendation',
        'fit_label',
        'size_label',
        'score',
    ];

    protected $casts = [
        'answers' => 'array',
        'recommendation' => 'array',
        'score' => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
