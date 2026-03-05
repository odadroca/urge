<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Prompt;
use Illuminate\Http\Request;

class PromptController extends Controller
{
    public function index(Request $request)
    {
        $tag = $request->query('tag');

        $query = Prompt::with('activeVersion', 'creator')->latest();

        if ($tag) {
            $query->whereJsonContains('tags', $tag);
        }

        $prompts = $query->paginate(20)->withQueryString();

        $allTags = Prompt::whereNotNull('tags')
            ->pluck('tags')
            ->flatten()
            ->filter()
            ->unique()
            ->sort()
            ->values()
            ->all();

        return view('prompts.index', compact('prompts', 'allTags', 'tag'));
    }

    public function create()
    {
        $this->authorize('create', Prompt::class);
        return view('prompts.create');
    }

    public function store(Request $request)
    {
        $this->authorize('create', Prompt::class);

        $data = $request->validate([
            'name'        => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'tags'        => ['nullable', 'array'],
            'tags.*'      => ['string', 'max:50'],
        ]);

        $prompt = Prompt::create([
            'name'        => $data['name'],
            'description' => $data['description'] ?? null,
            'tags'        => $this->normalizeTags($data['tags'] ?? []),
            'created_by'  => auth()->id(),
        ]);

        return redirect()->route('prompts.show', $prompt)->with('success', 'Prompt created.');
    }

    public function show(Prompt $prompt)
    {
        $prompt->load('activeVersion.creator', 'creator');
        return view('prompts.show', compact('prompt'));
    }

    public function edit(Prompt $prompt)
    {
        $this->authorize('update', $prompt);
        return view('prompts.edit', compact('prompt'));
    }

    public function update(Request $request, Prompt $prompt)
    {
        $this->authorize('update', $prompt);

        $data = $request->validate([
            'name'        => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'tags'        => ['nullable', 'array'],
            'tags.*'      => ['string', 'max:50'],
        ]);

        $prompt->update([
            'name'        => $data['name'],
            'description' => $data['description'] ?? null,
            'tags'        => $this->normalizeTags($data['tags'] ?? []),
        ]);

        return redirect()->route('prompts.show', $prompt)->with('success', 'Prompt updated.');
    }

    public function destroy(Prompt $prompt)
    {
        $this->authorize('delete', $prompt);
        $prompt->delete();

        return redirect()->route('prompts.index')->with('success', 'Prompt deleted.');
    }

    private function normalizeTags(array $tags): array
    {
        return array_values(array_unique(
            array_filter(
                array_map(fn($t) => strtolower(trim($t)), $tags)
            )
        ));
    }
}
