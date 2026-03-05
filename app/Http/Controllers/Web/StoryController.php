<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\LibraryEntry;
use App\Models\Prompt;
use App\Models\Story;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class StoryController extends Controller
{
    public function index()
    {
        $stories = Story::withCount('steps')
            ->with('creator')
            ->orderByDesc('updated_at')
            ->paginate(20);

        return view('stories.index', compact('stories'));
    }

    public function create()
    {
        return view('stories.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title'       => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
        ]);

        $story = Story::create([
            ...$validated,
            'created_by' => auth()->id(),
        ]);

        return redirect()->route('stories.edit', $story)
            ->with('success', 'Story created — add your first step below.');
    }

    public function show(Story $story)
    {
        $story->load([
            'steps.prompt',
            'steps.version',
            'steps.libraryEntry.provider',
            'creator',
        ]);

        return view('stories.show', compact('story'));
    }

    public function edit(Story $story)
    {
        $story->load(['steps.prompt', 'steps.version', 'steps.libraryEntry.provider']);

        $prompts = Prompt::with('versions')->orderBy('name')->get();

        $promptVersionMap = $prompts->mapWithKeys(fn ($p) => [
            $p->id => $p->versions->map(fn ($v) => [
                'id'             => $v->id,
                'version_number' => $v->version_number,
                'commit_message' => $v->commit_message,
            ])->values(),
        ]);

        $versionIds = $prompts->flatMap->versions->pluck('id');

        $versionLibraryMap = LibraryEntry::with('provider')
            ->whereIn('prompt_version_id', $versionIds)
            ->get()
            ->groupBy('prompt_version_id')
            ->map(fn ($entries) => $entries->map(fn ($e) => [
                'id'      => $e->id,
                'label'   => ($e->provider?->name ?? 'Custom') . ' — ' . $e->model_used,
                'preview' => Str::limit($e->response_text, 80),
            ])->values());

        return view('stories.edit', compact('story', 'prompts', 'promptVersionMap', 'versionLibraryMap'));
    }

    public function update(Request $request, Story $story)
    {
        $validated = $request->validate([
            'title'       => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
        ]);

        $story->update($validated);

        return redirect()->route('stories.edit', $story)
            ->with('success', 'Story updated.');
    }

    public function destroy(Story $story)
    {
        $story->delete();

        return redirect()->route('stories.index')
            ->with('success', 'Story deleted.');
    }
}
