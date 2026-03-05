<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\LibraryEntry;
use App\Models\LlmProvider;
use App\Models\Prompt;
use App\Models\PromptVersion;
use Illuminate\Http\Request;

class LibraryController extends Controller
{
    public function index(Request $request)
    {
        $query = LibraryEntry::with(['prompt', 'version', 'provider', 'creator'])
            ->orderByDesc('created_at');

        if ($request->filled('prompt_id')) {
            $query->where('prompt_id', $request->prompt_id);
        }

        if ($request->filled('provider_id')) {
            $query->where('llm_provider_id', $request->provider_id);
        }

        if ($request->filled('rated')) {
            $query->whereNotNull('rating');
        }

        $entries  = $query->paginate(20)->withQueryString();
        $prompts  = Prompt::orderBy('name')->get(['id', 'name']);
        $providers = LlmProvider::orderBy('sort_order')->get(['id', 'name']);

        return view('library.index', compact('entries', 'prompts', 'providers'));
    }

    public function create(Request $request)
    {
        $prompts  = Prompt::with('versions')->orderBy('name')->get();
        $providers = LlmProvider::orderBy('sort_order')->get();

        // Build prompt → versions map for Alpine.js
        $promptVersionMap = $prompts->mapWithKeys(fn ($p) => [
            $p->id => $p->versions->map(fn ($v) => [
                'id'             => $v->id,
                'version_number' => $v->version_number,
                'commit_message' => $v->commit_message,
            ])->values(),
        ]);

        // Pre-fill from query params (e.g. "Save from run")
        $defaults = [
            'prompt_id'         => $request->prompt_id,
            'prompt_version_id' => $request->prompt_version_id,
            'llm_provider_id'   => $request->provider_id,
            'model_used'        => $request->model_used,
            'response_text'     => $request->response_text,
            'rating'            => $request->rating,
        ];

        return view('library.create', compact('prompts', 'providers', 'promptVersionMap', 'defaults'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'prompt_id'         => ['required', 'exists:prompts,id'],
            'prompt_version_id' => ['required', 'exists:prompt_versions,id'],
            'llm_provider_id'   => ['nullable', 'exists:llm_providers,id'],
            'model_used'        => ['required', 'string', 'max:255'],
            'response_text'     => ['required', 'string'],
            'notes'             => ['nullable', 'string', 'max:2000'],
            'rating'            => ['nullable', 'integer', 'min:1', 'max:5'],
        ]);

        $validated['created_by'] = auth()->id();

        $entry = LibraryEntry::create($validated);

        return redirect()->route('library.show', $entry)
            ->with('success', 'Response saved to library.');
    }

    public function compare(Request $request)
    {
        $versionId = $request->query('version_id');

        if (! $versionId) {
            return redirect()->route('library.index')
                ->with('error', 'A version_id is required to compare responses.');
        }

        $version = PromptVersion::with(['prompt', 'creator'])->findOrFail($versionId);

        $query = LibraryEntry::with(['provider', 'creator'])
            ->where('prompt_version_id', $versionId)
            ->orderByRaw('rating IS NULL, rating DESC')
            ->orderBy('created_at');

        if ($request->filled('ids')) {
            $ids = array_filter(array_map('intval', explode(',', $request->query('ids'))));
            if ($ids) {
                $query->whereIn('id', $ids);
            }
        }

        $entries = $query->get();

        return view('library.compare', compact('version', 'entries'));
    }

    public function show(LibraryEntry $library)
    {
        $library->load(['prompt', 'version', 'provider', 'creator']);
        return view('library.show', ['entry' => $library]);
    }

    public function edit(LibraryEntry $library)
    {
        $entry    = $library->load(['prompt', 'version', 'provider']);
        $prompts  = Prompt::with('versions')->orderBy('name')->get();
        $providers = LlmProvider::orderBy('sort_order')->get();

        $promptVersionMap = $prompts->mapWithKeys(fn ($p) => [
            $p->id => $p->versions->map(fn ($v) => [
                'id'             => $v->id,
                'version_number' => $v->version_number,
                'commit_message' => $v->commit_message,
            ])->values(),
        ]);

        return view('library.edit', compact('entry', 'prompts', 'providers', 'promptVersionMap'));
    }

    public function update(Request $request, LibraryEntry $library)
    {
        $validated = $request->validate([
            'prompt_id'         => ['required', 'exists:prompts,id'],
            'prompt_version_id' => ['required', 'exists:prompt_versions,id'],
            'llm_provider_id'   => ['nullable', 'exists:llm_providers,id'],
            'model_used'        => ['required', 'string', 'max:255'],
            'response_text'     => ['required', 'string'],
            'notes'             => ['nullable', 'string', 'max:2000'],
            'rating'            => ['nullable', 'integer', 'min:1', 'max:5'],
        ]);

        $library->update($validated);

        return redirect()->route('library.show', $library)
            ->with('success', 'Library entry updated.');
    }

    public function destroy(LibraryEntry $library)
    {
        $library->delete();

        return redirect()->route('library.index')
            ->with('success', 'Library entry deleted.');
    }

    public function export(LibraryEntry $library)
    {
        $library->load(['prompt', 'version', 'creator']);

        $rating = $library->rating
            ? $library->rating . '/5'
            : 'Not rated';

        $ratedBy = $library->rating && $library->creator
            ? ' — added by ' . $library->creator->name
            : '';

        $content = "# {$library->prompt->name} — {$library->model_used}\n\n";
        $content .= "**Version:** v{$library->version->version_number}";
        if ($library->version->commit_message) {
            $content .= " — {$library->version->commit_message}";
        }
        $content .= "\n";
        $content .= "**Added:** {$library->created_at->format('Y-m-d H:i')}\n";
        if ($library->notes) {
            $content .= "**Notes:** {$library->notes}\n";
        }
        $content .= "\n---\n\n## Response\n\n{$library->response_text}\n\n---\n\n";
        $content .= "*Rating: {$rating}{$ratedBy}*\n";

        $filename = \Illuminate\Support\Str::slug($library->prompt->name) . '-library-' . $library->id . '.md';

        return response($content, 200, [
            'Content-Type'        => 'text/markdown',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }
}
