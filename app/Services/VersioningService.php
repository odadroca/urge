<?php

namespace App\Services;

use App\Models\Prompt;
use App\Models\PromptVersion;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class VersioningService
{
    public function __construct(private TemplateEngine $templateEngine) {}

    public function createVersion(Prompt $prompt, array $data, User $author): PromptVersion
    {
        return DB::transaction(function () use ($prompt, $data, $author) {
            $maxVersion = PromptVersion::where('prompt_id', $prompt->id)->max('version_number');
            $nextNumber = ($maxVersion ?? 0) + 1;

            $variables = $this->templateEngine->extractVariables($data['content']);

            return PromptVersion::create([
                'prompt_id'      => $prompt->id,
                'version_number' => $nextNumber,
                'content'        => $data['content'],
                'commit_message' => $data['commit_message'] ?? null,
                'variables'      => $variables,
                'created_by'     => $author->id,
            ]);
        });
    }
}
