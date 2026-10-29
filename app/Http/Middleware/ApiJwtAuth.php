<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ApiJwtAuth
{
    public function handle(Request $request, Closure $next): Response
    {
        $header = $request->header('Authorization', '');
        $token = str_starts_with($header, 'Bearer ') ? substr($header, 7) : null;

        if (! $token) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        try {
            $payload = JWT::decode($token, new Key(env('JWT_SECRET', env('APP_KEY')), 'HS256'));
            $user = User::find($payload->sub ?? null);

            if (! $user) {
                return response()->json(['message' => 'Unauthorized'], 401);
            }

            $request->attributes->set('auth_user', $user);
            $request->attributes->set('auth_role', $payload->role ?? $user->role ?? 'customer');

            return $next($request);
        } catch (\Throwable $e) {
            return response()->json(['message' => 'Invalid token'], 401);
        }
    }
}
