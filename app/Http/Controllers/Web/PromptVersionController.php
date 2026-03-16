<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\LibraryEntry;
use App\Models\Prompt;
use App\Models\PromptEnvironment;
use App\Models\PromptVersion;
use App\Services\TemplateEngine;
use App\Services\VersioningService;
use Illuminate\Http\Request;

class PromptVersionController extends Controller
{
    public function __construct(private VersioningService $versioning, private TemplateEngine $engine) {}

    public function index(Prompt $prompt)
    {
        $versions = $prompt->versions()->with('creator')->get();
        $environments = PromptEnvironment::where('prompt_id', $prompt->id)->get();
        return view('prompts.versions.index', compact('prompt', 'versions', 'environments'));
    }

    public function create(Prompt $prompt)
    {
        $this->authorize('createVersion', $prompt);
        $latest = $prompt->versions()->first();

        $allPrompts = Prompt::whereNull('deleted_at')
            ->where('id', '!=', $prompt->id)
            ->orderBy('name')
            ->get(['id', 'name', 'slug']);

        $knownVariables = PromptVersion::whereNotNull('variables')
            ->pluck('variables')
            ->flatten()
            ->unique()
            ->sort()
            ->values();

        return view('prompts.versions.create', compact('prompt', 'latest', 'allPrompts', 'knownVariables'));
    }

    public function store(Request $request, Prompt $prompt)
    {
        $this->authorize('createVersion', $prompt);

        $data = $request->validate([
            'content'                     => ['required', 'string'],
            'commit_message'              => ['nullable', 'string', 'max:500'],
            'variable_metadata'           => ['nullable', 'array'],
            'variable_metadata.*.type'    => ['nullable', 'string', 'in:string,text,enum,number,boolean'],
            'variable_metadata.*.default' => ['nullable', 'string'],
            'variable_metadata.*.description' => ['nullable', 'string'],
            'variable_metadata.*.options' => ['nullable', 'array'],
            'variable_metadata.*.options.*' => ['string'],
            'variable_metadata.*.options_csv' => ['nullable', 'string'],
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
            ->with('success', "Version {$version->version_number} created.");
    }

    public function show(Prompt $prompt, int $versionNumber)
    {
        $version = $prompt->versions()
            ->where('version_number', $versionNumber)
            ->with('creator')
            ->firstOrFail();

        $libraryCount = LibraryEntry::where('prompt_version_id', $version->id)->count();

        return view('prompts.versions.show', compact('prompt', 'version', 'libraryCount'));
    }

    public function compare(Request $request, Prompt $prompt)
    {
        $v1 = (int) $request->query('v1');
        $v2 = (int) $request->query('v2');

        if (!$v1 || !$v2 || $v1 === $v2) {
            return redirect()->route('prompts.versions.index', $prompt)
                ->with('error', 'Select two different versions to compare.');
        }

        [$numA, $numB] = $v1 < $v2 ? [$v1, $v2] : [$v2, $v1];

        $versionA = $prompt->versions()->where('version_number', $numA)->with('creator')->firstOrFail();
        $versionB = $prompt->versions()->where('version_number', $numB)->with('creator')->firstOrFail();

        $groups = $this->computeDiffGroups($versionA->content, $versionB->content);

        $additions = 0;
        $removals  = 0;
        foreach ($groups as $g) {
            if ($g['type'] === 'added')   $additions += count($g['items']);
            if ($g['type'] === 'removed') $removals  += count($g['items']);
        }

        return view('prompts.versions.compare', compact(
            'prompt', 'versionA', 'versionB', 'groups', 'additions', 'removals'
        ));
    }

    private function computeDiffGroups(string $oldText, string $newText): array
    {
        $oldLines = explode("\n", $oldText);
        $newLines = explode("\n", $newText);
        $m = count($oldLines);
        $n = count($newLines);

        // LCS DP table
        $dp = array_fill(0, $m + 1, array_fill(0, $n + 1, 0));
        for ($i = 1; $i <= $m; $i++) {
            for ($j = 1; $j <= $n; $j++) {
                $dp[$i][$j] = $oldLines[$i - 1] === $newLines[$j - 1]
                    ? $dp[$i - 1][$j - 1] + 1
                    : max($dp[$i - 1][$j], $dp[$i][$j - 1]);
            }
        }

        // Backtrack — build items in reverse, tracking line numbers
        $items = [];
        $lineA = $m;
        $lineB = $n;
        $i     = $m;
        $j     = $n;

        while ($i > 0 || $j > 0) {
            if ($i > 0 && $j > 0 && $oldLines[$i - 1] === $newLines[$j - 1]) {
                $items[] = ['type' => 'equal',   'line' => $oldLines[$i - 1], 'lineA' => $lineA--, 'lineB' => $lineB--];
                $i--; $j--;
            } elseif ($j > 0 && ($i === 0 || $dp[$i][$j - 1] >= $dp[$i - 1][$j])) {
                $items[] = ['type' => 'added',   'line' => $newLines[$j - 1], 'lineA' => null,     'lineB' => $lineB--];
                $j--;
            } else {
                $items[] = ['type' => 'removed', 'line' => $oldLines[$i - 1], 'lineA' => $lineA--, 'lineB' => null];
                $i--;
            }
        }

        $items = array_reverse($items);

        // Group consecutive same-type items
        $groups = [];
        foreach ($items as $item) {
            $last = count($groups) - 1;
            if ($last >= 0 && $groups[$last]['type'] === $item['type']) {
                $groups[$last]['items'][] = $item;
            } else {
                $groups[] = ['type' => $item['type'], 'items' => [$item]];
            }
        }

        return $groups;
    }

    public function assignEnvironment(Request $request, Prompt $prompt)
    {
        $this->authorize('activateVersion', $prompt);

        $data = $request->validate([
            'environment_name' => ['required', 'string', 'max:50'],
            'version_id'       => ['required', 'exists:prompt_versions,id'],
        ]);

        PromptEnvironment::updateOrCreate(
            ['prompt_id' => $prompt->id, 'name' => $data['environment_name']],
            ['prompt_version_id' => $data['version_id']]
        );

        return redirect()
            ->route('prompts.versions.index', $prompt)
            ->with('success', "Environment '{$data['environment_name']}' updated.");
    }

    public function compose(Prompt $prompt, int $versionNumber)
    {
        $version = $prompt->versions()
            ->where('version_number', $versionNumber)
            ->firstOrFail();

        try {
            $result = $this->engine->render($version->content, [], $version->variable_metadata);
            return response()->json([
                'composed'  => $result['rendered'],
                'includes'  => $result['includes_resolved'],
            ]);
        } catch (\RuntimeException $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }
    }

    public function activate(Prompt $prompt, int $versionNumber)
    {
        $this->authorize('activateVersion', $prompt);

        $version = $prompt->versions()
            ->where('version_number', $versionNumber)
            ->firstOrFail();

        $prompt->update(['active_version_id' => $version->id]);

        return redirect()
            ->route('prompts.versions.index', $prompt)
            ->with('success', "Version {$versionNumber} is now active.");
    }
}
