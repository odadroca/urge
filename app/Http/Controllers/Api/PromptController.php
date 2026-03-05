<?php

namespace App\Http\Controllers\Api;

use App\Models\Prompt;
use App\Services\TemplateEngine;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PromptController extends ApiController
{
    public function __construct(private TemplateEngine $engine) {}

    public function index(): JsonResponse
    {
        $prompts = Prompt::whereNotNull('active_version_id')
            ->with(['activeVersion'])
            ->get()
            ->map(fn ($p) => [
                'id'             => $p->id,
                'name'           => $p->name,
                'slug'           => $p->slug,
                'description'    => $p->description,
                'active_version' => $p->activeVersion?->version_number,
                'variables'      => $p->activeVersion?->variables ?? [],
                'created_at'     => $p->created_at,
            ]);

        return $this->success($prompts->values(), 200);
    }

    public function show(string $slug): JsonResponse
    {
        $prompt = Prompt::where('slug', $slug)->with('activeVersion.creator')->first();

        if (!$prompt || !$prompt->activeVersion) {
            return $this->error('NOT_FOUND', "No prompt with slug '{$slug}' was found or it has no active version.", 404);
        }

        return $this->success($this->formatPromptWithVersion($prompt, $prompt->activeVersion));
    }

    public function render(Request $request, string $slug): JsonResponse
    {
        $prompt = Prompt::where('slug', $slug)->with('activeVersion')->first();

        if (!$prompt) {
            return $this->error('NOT_FOUND', "No prompt with slug '{$slug}' was found.", 404);
        }

        $validated = $request->validate([
            'variables' => ['sometimes', 'array'],
            'version'   => ['sometimes', 'integer'],
        ]);

        if (isset($validated['version'])) {
            $version = $prompt->versions()->where('version_number', $validated['version'])->first();
            if (!$version) {
                return $this->error('NOT_FOUND', "Version {$validated['version']} not found for prompt '{$slug}'.", 404);
            }
        } else {
            $version = $prompt->activeVersion;
            if (!$version) {
                return $this->error('NOT_FOUND', "Prompt '{$slug}' has no active version.", 404);
            }
        }

        $result = $this->engine->render($version->content, $validated['variables'] ?? []);

        return $this->success([
            'rendered'          => $result['rendered'],
            'prompt_slug'       => $slug,
            'version_number'    => $version->version_number,
            'variables_used'    => $result['variables_used'],
            'variables_missing' => $result['variables_missing'],
        ]);
    }

    private function formatPromptWithVersion(Prompt $prompt, $version): array
    {
        return [
            'id'          => $prompt->id,
            'name'        => $prompt->name,
            'slug'        => $prompt->slug,
            'description' => $prompt->description,
            'version'     => [
                'id'             => $version->id,
                'version_number' => $version->version_number,
                'content'        => $version->content,
                'commit_message' => $version->commit_message,
                'variables'      => $version->variables ?? [],
                'created_by'     => $version->creator?->name,
                'created_at'     => $version->created_at,
            ],
        ];
    }
}
