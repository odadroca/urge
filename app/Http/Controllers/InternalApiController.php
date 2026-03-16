<?php

namespace App\Http\Controllers;

use App\Models\Prompt;
use App\Models\PromptVersion;

class InternalApiController extends Controller
{
    /**
     * Return all unique variable names across all prompt versions.
     */
    public function variables()
    {
        $variables = PromptVersion::whereNotNull('variables')
            ->pluck('variables')
            ->flatten()
            ->unique()
            ->sort()
            ->values();

        return response()->json($variables);
    }

    /**
     * Return all prompts that can be used as includes (have an active version).
     */
    public function fragments()
    {
        $fragments = Prompt::whereNull('deleted_at')
            ->whereNotNull('active_version_id')
            ->orderBy('name')
            ->get(['slug', 'name']);

        return response()->json($fragments);
    }
}
