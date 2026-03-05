<?php

namespace App\Services;

use App\Models\LlmProvider;
use App\Services\LlmProviders\AnthropicDriver;
use App\Services\LlmProviders\GeminiDriver;
use App\Services\LlmProviders\LlmResult;
use App\Services\LlmProviders\MistralDriver;
use App\Services\LlmProviders\OllamaDriver;
use App\Services\LlmProviders\OpenAiDriver;
use Illuminate\Support\Facades\Crypt;

class LlmDispatchService
{
    public function dispatch(LlmProvider $provider, string $prompt): LlmResult
    {
        $driver = $this->resolveDriver($provider);
        return $driver->complete($prompt);
    }

    private function resolveDriver(LlmProvider $provider): \App\Services\LlmProviders\Contracts\LlmDriverInterface
    {
        $apiKey = $this->decryptKey($provider);

        return match ($provider->driver) {
            'openai'    => new OpenAiDriver($apiKey, $provider->model, $provider->base_url),
            'anthropic' => new AnthropicDriver($apiKey, $provider->model),
            'mistral'   => new MistralDriver($apiKey, $provider->model),
            'gemini'    => new GeminiDriver($apiKey, $provider->model),
            'ollama'    => new OllamaDriver($provider->base_url ?? 'http://localhost:11434', $provider->model),
            default     => throw new \InvalidArgumentException("Unknown LLM driver: {$provider->driver}"),
        };
    }

    private function decryptKey(LlmProvider $provider): string
    {
        if ($provider->isOllama()) {
            return '';
        }

        if (!$provider->api_key_encrypted) {
            throw new \RuntimeException("No API key configured for provider: {$provider->name}");
        }

        return Crypt::decryptString($provider->api_key_encrypted);
    }
}
