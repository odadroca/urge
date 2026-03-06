<?php

namespace App\Http\Controllers\Api;

use App\Models\Prompt;
use Illuminate\Http\JsonResponse;

class VersionController extends ApiController
{
    public function index(string $slug): JsonResponse
    {
        $prompt = Prompt::where('slug', $slug)->first();

        if (!$prompt) {
            return $this->error('NOT_FOUND', "No prompt with slug '{$slug}' was found.", 404);
        }

        $versions = $prompt->versions()->with('creator')->get()->map(fn ($v) => [
            'version_number'    => $v->version_number,
            'commit_message'    => $v->commit_message,
            'variables'         => $v->variables ?? [],
            'variable_metadata' => $v->variable_metadata,
            'created_by'        => $v->creator?->name,
            'created_at'        => $v->created_at,
            'is_active'         => $prompt->active_version_id === $v->id,
        ]);

        return $this->success($versions->values());
    }

    public function show(string $slug, int $versionNumber): JsonResponse
    {
        $prompt = Prompt::where('slug', $slug)->first();

        if (!$prompt) {
            return $this->error('NOT_FOUND', "No prompt with slug '{$slug}' was found.", 404);
        }

        $version = $prompt->versions()->where('version_number', $versionNumber)->with('creator')->first();

        if (!$version) {
            return $this->error('NOT_FOUND', "Version {$versionNumber} not found for prompt '{$slug}'.", 404);
        }

        return $this->success([
            'id'             => $prompt->id,
            'name'           => $prompt->name,
            'slug'           => $prompt->slug,
            'description'    => $prompt->description,
            'version'        => [
                'id'                => $version->id,
                'version_number'    => $version->version_number,
                'content'           => $version->content,
                'commit_message'    => $version->commit_message,
                'variables'         => $version->variables ?? [],
                'variable_metadata' => $version->variable_metadata,
                'created_by'        => $version->creator?->name,
                'created_at'        => $version->created_at,
                'is_active'         => $prompt->active_version_id === $version->id,
            ],
        ]);
    }
}
