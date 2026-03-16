<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Prompt;
use App\Models\PromptVersion;
use App\Services\VersioningService;
use Illuminate\Http\Request;

class DesignerController extends Controller
{
    public function __construct(private VersioningService $versioning) {}

    public function create(Prompt $prompt)
    {
        $this->authorize('createVersion', $prompt);

        $allPrompts = Prompt::whereNull('deleted_at')
            ->where('id', '!=', $prompt->id)
            ->whereNotNull('active_version_id')
            ->with('category')
            ->orderBy('name')
            ->get(['id', 'name', 'slug', 'category_id']);

        $knownVariables = PromptVersion::whereNotNull('variables')
            ->pluck('variables')
            ->flatten()
            ->unique()
            ->sort()
            ->values();

        $includeCategories = $this->groupPromptsByCategory($allPrompts);
        $initialBlocks = [];
        $previousMetadata = [];

        return view('prompts.versions.designer', compact(
            'prompt', 'allPrompts', 'knownVariables', 'includeCategories', 'initialBlocks', 'previousMetadata'
        ));
    }

    public function edit(Prompt $prompt, int $version)
    {
        $this->authorize('createVersion', $prompt);

        $promptVersion = $prompt->versions()
            ->where('version_number', $version)
            ->firstOrFail();

        $allPrompts = Prompt::whereNull('deleted_at')
            ->where('id', '!=', $prompt->id)
            ->whereNotNull('active_version_id')
            ->with('category')
            ->orderBy('name')
            ->get(['id', 'name', 'slug', 'category_id']);

        $knownVariables = PromptVersion::whereNotNull('variables')
            ->pluck('variables')
            ->flatten()
            ->unique()
            ->sort()
            ->values();

        $includeCategories = $this->groupPromptsByCategory($allPrompts);
        $initialBlocks = $this->parseContentToBlocks($promptVersion->content);
        $previousMetadata = $promptVersion->variable_metadata ?? [];

        return view('prompts.versions.designer', compact(
            'prompt', 'allPrompts', 'knownVariables', 'includeCategories', 'initialBlocks', 'previousMetadata', 'promptVersion'
        ));
    }

    public function store(Request $request, Prompt $prompt)
    {
        $this->authorize('createVersion', $prompt);

        $data = $request->validate([
            'content'                          => ['required', 'string'],
            'commit_message'                   => ['nullable', 'string', 'max:500'],
            'variable_metadata'                => ['nullable', 'array'],
            'variable_metadata.*.type'         => ['nullable', 'string', 'in:string,text,enum,number,boolean'],
            'variable_metadata.*.default'      => ['nullable', 'string'],
            'variable_metadata.*.description'  => ['nullable', 'string'],
            'variable_metadata.*.options'      => ['nullable', 'array'],
            'variable_metadata.*.options.*'    => ['string'],
            'variable_metadata.*.options_csv'  => ['nullable', 'string'],
        ]);

        // Convert options_csv to options array for enum types
        if (!empty($data['variable_metadata'])) {
            foreach ($data['variable_metadata'] as $varName => &$meta) {
                if (!empty($meta['options_csv'])) {
                    $meta['options'] = array_values(array_filter(
                        array_map('trim', explode(',', $meta['options_csv']))
                    ));
                }
                unset($meta['options_csv']);
            }
            unset($meta);
        }

        $version = $this->versioning->createVersion($prompt, $data, auth()->user());

        return redirect()
            ->route('prompts.versions.show', [$prompt, $version->version_number])
            ->with('success', "Version {$version->version_number} created via designer.");
    }

    private function groupPromptsByCategory($allPrompts): array
    {
        $grouped = [];

        foreach ($allPrompts as $p) {
            $key = $p->category ? $p->category->name : 'Uncategorized';
            $color = $p->category ? $p->category->color : 'gray';
            $grouped[$key] ??= ['color' => $color, 'prompts' => []];
            $grouped[$key]['prompts'][] = $p;
        }

        // Sort: named categories first (alphabetically), Uncategorized last
        uksort($grouped, function ($a, $b) {
            if ($a === 'Uncategorized') return 1;
            if ($b === 'Uncategorized') return -1;
            return strcasecmp($a, $b);
        });

        return $grouped;
    }

    /**
     * Parse prompt content back into designer blocks.
     *
     * Splits on {{variable}} and {{>slug}} tokens, producing typed blocks
     * that preserve exact whitespace for lossless round-tripping.
     */
    private function parseContentToBlocks(string $content): array
    {
        $pattern = '/(\{\{>[a-zA-Z0-9_-]+\}\}|\{\{[a-zA-Z_][a-zA-Z0-9_]*\}\})/';
        $parts = preg_split($pattern, $content, -1, PREG_SPLIT_DELIM_CAPTURE);
        $blocks = [];
        $id = 1;

        foreach ($parts as $part) {
            if ($part === '') {
                continue;
            }

            if (preg_match('/^\{\{>([a-zA-Z0-9_-]+)\}\}$/', $part, $m)) {
                $blocks[] = [
                    'id'    => $id++,
                    'type'  => 'include',
                    'slug'  => $m[1],
                    'token' => $part,
                ];
            } elseif (preg_match('/^\{\{([a-zA-Z_][a-zA-Z0-9_]*)\}\}$/', $part, $m)) {
                $blocks[] = [
                    'id'    => $id++,
                    'type'  => 'variable',
                    'name'  => $m[1],
                    'token' => $part,
                ];
            } else {
                $blocks[] = [
                    'id'      => $id++,
                    'type'    => 'text',
                    'content' => $part,
                ];
            }
        }

        return $blocks;
    }
}
