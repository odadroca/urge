<?php

namespace App\Services\LlmProviders;

use App\Services\LlmProviders\Contracts\LlmDriverInterface;
use Illuminate\Support\Facades\Http;

class GeminiDriver implements LlmDriverInterface
{
    private const BASE_URL = 'https://generativelanguage.googleapis.com';

    public function __construct(
        private readonly string $apiKey,
        private readonly string $model,
    ) {}

    public function complete(string $prompt): LlmResult
    {
        $start = hrtime(true);

        try {
            $url = self::BASE_URL . "/v1beta/models/{$this->model}:generateContent?key={$this->apiKey}";

            $response = Http::withOptions(['verify' => config('urge.curl_ssl_verify', true)])
                ->timeout(120)
                ->post($url, [
                    'contents' => [
                        ['parts' => [['text' => $prompt]]],
                    ],
                ]);

            $durationMs = (int) ((hrtime(true) - $start) / 1_000_000);

            if ($response->failed()) {
                $error = $response->json('error.message') ?? $response->body();
                return LlmResult::failure($error, $this->model, $durationMs);
            }

            $data = $response->json();
            $text = $data['candidates'][0]['content']['parts'][0]['text'] ?? '';

            return LlmResult::success(
                text: $text,
                modelUsed: $this->model,
                durationMs: $durationMs,
                inputTokens: $data['usageMetadata']['promptTokenCount'] ?? null,
                outputTokens: $data['usageMetadata']['candidatesTokenCount'] ?? null,
            );
        } catch (\Throwable $e) {
            $durationMs = (int) ((hrtime(true) - $start) / 1_000_000);
            return LlmResult::failure($e->getMessage(), $this->model, $durationMs);
        }
    }
}
