<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\FitQuizResponse;
use Illuminate\Http\Request;

class FitQuizController extends Controller
{
    private function recommend(array $answers): array
    {
        $fit = $answers['1'] ?? $answers[1] ?? 'regular';
        $height = $answers['2'] ?? $answers[2] ?? 'avg';
        $brand = $answers['3'] ?? $answers[3] ?? 'none';

        $size = '32';
        $fitLabel = 'Regular Fit';

        if ($fit === 'slim') {
            $size = $height === 'short' ? '30' : '32';
            $fitLabel = 'Slim Fit';
        } elseif ($fit === 'relaxed') {
            $size = $height === 'tall' ? '36' : '34';
            $fitLabel = 'Relaxed Fit';
        }

        return [
            'fit' => $fit,
            'size' => $size,
            'label' => $fitLabel,
            'brandPreference' => $brand,
            'recommendation' => [
                'productType' => 'Jeans',
                'fit' => $fitLabel,
                'size' => $size,
                'message' => 'Based on your answers, this size and fit should offer the best balance of comfort and style.',
            ],
        ];
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'answers' => 'required|array',
        ]);

        $user = $request->attributes->get('auth_user');
        $result = $this->recommend($data['answers']);

        $response = FitQuizResponse::create([
            'user_id' => $user?->id,
            'answers' => $data['answers'],
            'recommendation' => $result,
            'fit_label' => $result['label'],
            'size_label' => $result['size'],
            'score' => match ($result['label']) {
                'Slim Fit' => 92,
                'Relaxed Fit' => 88,
                default => 90,
            },
        ]);

        return response()->json([
            'item' => [
                'id' => (string) $response->id,
                'answers' => $response->answers,
                'recommendation' => $response->recommendation,
                'fitLabel' => $response->fit_label,
                'sizeLabel' => $response->size_label,
                'score' => (int) $response->score,
                'createdAt' => $response->created_at,
            ],
        ], 201);
    }

    public function recommendations(Request $request)
    {
        $user = $request->attributes->get('auth_user');
        $latest = FitQuizResponse::where('user_id', $user->id)->latest()->first();

        if (! $latest) {
            return response()->json([
                'item' => null,
                'message' => 'No fit quiz responses found',
            ]);
        }

        return response()->json([
            'item' => [
                'id' => (string) $latest->id,
                'answers' => $latest->answers,
                'recommendation' => $latest->recommendation,
                'fitLabel' => $latest->fit_label,
                'sizeLabel' => $latest->size_label,
                'score' => (int) $latest->score,
                'createdAt' => $latest->created_at,
            ],
        ]);
    }
}
