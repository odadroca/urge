<?php

namespace App\Providers;

use App\Models\ApiKey;
use App\Models\Prompt;
use App\Models\User;
use App\Policies\ApiKeyPolicy;
use App\Policies\PromptPolicy;
use App\Policies\UserPolicy;
use Illuminate\Support\Facades\Gate;
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
    }
}
