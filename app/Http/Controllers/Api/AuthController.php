<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    private function signToken(User $user): string
    {
        try {
            $userId = (int) $user->getKey();
            $userRole = (string) ($user->role ?? 'customer');
            $secret = (string) config('app.key', 'secret_fallback');
            
            // If JWT_SECRET is explicitly set, use it, otherwise use APP_KEY
            $jwtSecret = env('JWT_SECRET');
            if ($jwtSecret) {
                $secret = $jwtSecret;
            }

            return JWT::encode([
                'sub' => $userId,
                'email' => $user->email,
                'role' => $userRole,
                'iat' => time(),
                'exp' => time() + (7 * 24 * 60 * 60),
            ], $secret, 'HS256');
        } catch (\Exception $e) {
            \Log::error('JWT Encoding Failed: ' . $e->getMessage());
            throw $e;
        }
    }

    public function register(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:6',
        ]);

        $user = User::create([
            'name' => $data['name'],
            'email' => strtolower($data['email']),
            'password' => Hash::make($data['password']),
            'role' => 'customer',
        ]);

        return response()->json([
            'token' => $this->signToken($user),
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'createdAt' => $user->created_at,
            ],
        ], 201);
    }

    public function login(Request $request)
    {
        $data = $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        $user = User::whereRaw('lower(email) = ?', [strtolower($data['email'])])->first();

        if (! $user || ! Hash::check($data['password'], $user->password)) {
            return response()->json(['message' => 'Invalid credentials'], 401);
        }

        return response()->json([
            'token' => $this->signToken($user),
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
                'createdAt' => $user->created_at,
            ],
        ]);
    }

    public function logout(Request $request)
    {
        $request->attributes->remove('auth_user');
        $request->attributes->remove('auth_role');

        return response()->json(['message' => 'Logged out']);
    }

    public function socialLogin(Request $request)
    {
        $data = $request->validate([
            'token' => 'required|string',
            'provider' => 'required|string',
        ]);

        $email = null;
        $name = null;

        if ($data['provider'] === 'google') {
            // In a production app, use google/apiclient to verify the token properly:
            // $client = new \Google_Client(['client_id' => $CLIENT_ID]);
            // $payload = $client->verifyIdToken($data['token']);
            
            // For now, we will decode the JWT locally to extract the info
            // NOTE: Verification should be added for production security
            try {
                $tks = explode('.', $data['token']);
                if (count($tks) != 3) throw new \Exception('Invalid token format');
                $payload = json_decode(base64_decode(strtr($tks[1], '-_', '+/')), true);
                
                $email = $payload['email'] ?? null;
                $name = $payload['name'] ?? 'Google User';
                $aud = $payload['aud'] ?? null;
                
                // Basic check for Client ID
                $clientId = "743766160104-17nvnllkntirlf4t5huh1c6q6l2crh2v.apps.googleusercontent.com";
                if ($aud !== $clientId) {
                    return response()->json(['message' => 'Invalid audience'], 401);
                }
            } catch (\Exception $e) {
                return response()->json(['message' => 'Token verification failed'], 401);
            }
        }

        if (! $email) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        // Find or create user
        $user = User::whereRaw('lower(email) = ?', [strtolower($email)])->first();

        if (! $user) {
            $user = User::create([
                'name' => $name,
                'email' => strtolower($email),
                'password' => Hash::make(\Illuminate\Support\Str::random(16)),
                'role' => 'customer',
            ]);
        }

        return response()->json([
            'token' => $this->signToken($user),
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
                'createdAt' => $user->created_at,
            ],
        ]);
    }

    public function me(Request $request)
    {
        $user = $request->attributes->get('auth_user');

        return response()->json([
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
                'createdAt' => $user->created_at,
            ],
        ]);
    }
}
