<?php

namespace App\Policies;

use App\Models\ApiKey;
use App\Models\User;

class ApiKeyPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function delete(User $user, ApiKey $apiKey): bool
    {
        return $user->isAdmin() || $apiKey->user_id === $user->id;
    }

    public function rotate(User $user, ApiKey $apiKey): bool
    {
        return $user->isAdmin() || $apiKey->user_id === $user->id;
    }
}
