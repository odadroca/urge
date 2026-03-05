<?php

namespace App\Services;

use App\Models\ApiKey;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Crypt;

class ApiKeyService
{
    public function generate(): array
    {
        $raw = 'urge_' . bin2hex(random_bytes(31));

        return [
            'raw'       => $raw,
            'hash'      => hash('sha256', $raw),
            'encrypted' => Crypt::encryptString($raw),
            'preview'   => substr($raw, 0, 8) . '...',
        ];
    }

    public function createForUser(User $user, string $name, ?Carbon $expiresAt = null): string
    {
        $parts = $this->generate();

        ApiKey::create([
            'user_id'       => $user->id,
            'name'          => $name,
            'key_hash'      => $parts['hash'],
            'key_encrypted' => $parts['encrypted'],
            'key_preview'   => $parts['preview'],
            'expires_at'    => $expiresAt,
        ]);

        return $parts['raw'];
    }

    public function findByRawKey(string $rawKey): ?ApiKey
    {
        $hash = hash('sha256', $rawKey);
        return ApiKey::where('key_hash', $hash)->first();
    }

    public function revealKey(ApiKey $apiKey): string
    {
        return Crypt::decryptString($apiKey->key_encrypted);
    }
}
