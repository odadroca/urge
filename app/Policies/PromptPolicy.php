<?php

namespace App\Policies;

use App\Models\Prompt;
use App\Models\User;

class PromptPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Prompt $prompt): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return $user->isEditor();
    }

    public function update(User $user, Prompt $prompt): bool
    {
        return $user->isEditor();
    }

    public function delete(User $user, Prompt $prompt): bool
    {
        return $user->isAdmin();
    }

    public function createVersion(User $user, Prompt $prompt): bool
    {
        return $user->isEditor();
    }

    public function activateVersion(User $user, Prompt $prompt): bool
    {
        return $user->isEditor();
    }

    public function restore(User $user, Prompt $prompt): bool
    {
        return $user->isAdmin();
    }

    public function forceDelete(User $user, Prompt $prompt): bool
    {
        return $user->isAdmin();
    }
}
