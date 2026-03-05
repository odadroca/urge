<?php

namespace App\Http\Middleware;

use App\Services\ApiKeyService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class ApiKeyAuthentication
{
    public function __construct(private ApiKeyService $apiKeyService) {}

    public function handle(Request $request, Closure $next): Response
    {
        $header = $request->header('Authorization');

        if (!$header) {
            return $this->unauthorized('MISSING_API_KEY', 'API key is required.');
        }

        if (!str_starts_with($header, 'Bearer ')) {
            return $this->unauthorized('INVALID_API_KEY_FORMAT', 'Authorization header must use Bearer scheme.');
        }

        $token = substr($header, 7);
        $apiKey = $this->apiKeyService->findByRawKey($token);

        if (!$apiKey) {
            return $this->unauthorized('INVALID_API_KEY', 'The provided API key is invalid.');
        }

        if ($apiKey->isExpired()) {
            return $this->unauthorized('EXPIRED_API_KEY', 'This API key has expired.');
        }

        $user = $apiKey->user;
        if (!$user) {
            return $this->unauthorized('KEY_OWNER_NOT_FOUND', 'The owner of this API key no longer exists.');
        }

        \DB::table('api_keys')
            ->where('id', $apiKey->id)
            ->update(['last_used_at' => now()]);

        Auth::setUser($user);

        return $next($request);
    }

    private function unauthorized(string $code, string $message): Response
    {
        return response()->json([
            'error' => ['code' => $code, 'message' => $message],
        ], 401);
    }
}
