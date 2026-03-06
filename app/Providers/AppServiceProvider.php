<?php

namespace App\Providers;

use App\Models\ApiKey;
use App\Models\Prompt;
use App\Models\User;
use App\Policies\ApiKeyPolicy;
use App\Policies\PromptPolicy;
use App\Policies\UserPolicy;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Gate::policy(Prompt::class, PromptPolicy::class);
        Gate::policy(ApiKey::class, ApiKeyPolicy::class);
        Gate::policy(User::class, UserPolicy::class);

        RateLimiter::for('urge-api', function ($request) {
            $limit = config('urge.api_rate_limit', 60);
            $window = config('urge.api_rate_window', 60);
            $key = $request->attributes->get('api_key_id', 'anonymous');
            $decayMinutes = max(1, (int) ceil($window / 60));

            return Limit::perMinutes($decayMinutes, $limit)
                ->by('api_key:' . $key)
                ->response(function ($request, $headers) {
                    $retryAfter = $headers['Retry-After'] ?? 60;

                    return response()->json([
                        'error' => [
                            'code' => 'RATE_LIMITED',
                            'message' => "Too many requests. Try again in {$retryAfter} seconds.",
                        ],
                    ], 429, [
                        'Retry-After' => $retryAfter,
                        'X-RateLimit-Remaining' => 0,
                    ]);
                });
        });
    }
}
