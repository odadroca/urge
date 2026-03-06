<?php

namespace App\Http\Controllers\Api;

use App\Models\Prompt;
use App\Models\PromptEnvironment;
use App\Services\TemplateEngine;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PromptController extends ApiController
{
    public function __construct(private TemplateEngine $engine) {}

    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
            'cursor'   => ['sometimes', 'string'],
        ]);

        $perPage = (int) $request->query('per_page', 25);

        $query = Prompt::whereNotNull('active_version_id')
            ->with(['activeVersion'])
            ->orderBy('id');

        // Filter by scoped prompts if key is scoped
        $scopedIds = $request->attributes->get('api_key_scoped_prompt_ids');
        if ($scopedIds) {
            $query->whereIn('id', $scopedIds);
        }

        $paginated = $query->cursorPaginate($perPage);

        $data = $paginated->getCollection()->map(fn ($p) => [
            'id'                => $p->id,
            'name'              => $p->name,
            'slug'              => $p->slug,
            'description'       => $p->description,
            'tags'              => $p->tags ?? [],
            'active_version'    => $p->activeVersion?->version_number,
            'variables'         => $p->activeVersion?->variables ?? [],
            'variable_metadata' => $p->activeVersion?->variable_metadata,
            'created_at'        => $p->created_at,
        ]);

        return response()->json([
            'data' => $data->values(),
            'meta' => [
                'per_page'    => $perPage,
                'next_cursor' => $paginated->nextCursor()?->encode(),
                'prev_cursor' => $paginated->previousCursor()?->encode(),
            ],
        ]);
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
            'variables'   => ['sometimes', 'array'],
            'version'     => ['sometimes', 'integer'],
            'environment' => ['sometimes', 'string'],
        ]);

        $environment = null;

        if (isset($validated['version'])) {
            // Explicit version pin takes highest precedence
            $version = $prompt->versions()->where('version_number', $validated['version'])->first();
            if (!$version) {
                return $this->error('NOT_FOUND', "Version {$validated['version']} not found for prompt '{$slug}'.", 404);
            }
        } elseif (isset($validated['environment'])) {
            // Environment lookup
            $envName = $validated['environment'];
            $env = PromptEnvironment::where('prompt_id', $prompt->id)
                ->where('name', $envName)
                ->first();
            if (!$env) {
                return $this->error('ENVIRONMENT_NOT_FOUND', "Environment '{$envName}' not found for prompt '{$slug}'.", 404);
            }
            $version = $prompt->versions()->where('id', $env->prompt_version_id)->first();
            if (!$version) {
                return $this->error('NOT_FOUND', "The version assigned to environment '{$envName}' no longer exists.", 404);
            }
            $environment = $envName;
        } else {
            $version = $prompt->activeVersion;
            if (!$version) {
                return $this->error('NOT_FOUND', "Prompt '{$slug}' has no active version.", 404);
            }
        }

        // Merge defaults from variable metadata
        $variables = $validated['variables'] ?? [];
        $result = $this->engine->render($version->content, $variables, $version->variable_metadata);

        return $this->success([
            'rendered'          => $result['rendered'],
            'prompt_slug'       => $slug,
            'version_number'    => $version->version_number,
            'environment'       => $environment,
            'variables_used'    => $result['variables_used'],
            'variables_missing' => $result['variables_missing'],
            'variable_metadata' => $version->variable_metadata,
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
                'id'                => $version->id,
                'version_number'    => $version->version_number,
                'content'           => $version->content,
                'commit_message'    => $version->commit_message,
                'variables'         => $version->variables ?? [],
                'variable_metadata' => $version->variable_metadata,
                'created_by'        => $version->creator?->name,
                'created_at'        => $version->created_at,
            ],
        ];
    }
}
