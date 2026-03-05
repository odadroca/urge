<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LlmProvider extends Model
{
    protected $fillable = [
        'driver',
        'name',
        'model',
        'base_url',
        'api_key_encrypted',
        'enabled',
        'sort_order',
    ];

    protected $casts = [
        'enabled' => 'boolean',
    ];

    public function responses()
    {
        return $this->hasMany(LlmResponse::class);
    }

    public function isOllama(): bool
    {
        return $this->driver === 'ollama';
    }

    public function keyPreview(): ?string
    {
        if (!$this->api_key_encrypted) {
            return null;
        }
        try {
            $raw = \Illuminate\Support\Facades\Crypt::decryptString($this->api_key_encrypted);
            return substr($raw, 0, 8) . '...';
        } catch (\Throwable) {
            return '(encrypted)';
        }
    }
}
