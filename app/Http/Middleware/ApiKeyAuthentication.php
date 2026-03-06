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

        $request->attributes->set('api_key_id', $apiKey->id);
        $request->attributes->set('api_key', $apiKey);

        // Check prompt scope
        $scopedPromptIds = $apiKey->prompts()->pluck('prompts.id')->all();
        if (!empty($scopedPromptIds)) {
            $request->attributes->set('api_key_scoped_prompt_ids', $scopedPromptIds);

            // Extract slug from the route path
            $path = $request->path();
            if (preg_match('#api/v1/prompts/([^/]+)#', $path, $matches)) {
                $slug = $matches[1];
                $prompt = \App\Models\Prompt::where('slug', $slug)->first();
                if ($prompt && !in_array($prompt->id, $scopedPromptIds)) {
                    return response()->json([
                        'error' => [
                            'code' => 'KEY_SCOPE_DENIED',
                            'message' => "This API key does not have access to prompt '{$slug}'.",
                        ],
                    ], 403);
                }
            }
        }

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
