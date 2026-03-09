<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\LibraryEntry;
use App\Models\Prompt;
use Illuminate\Http\Request;

class PromptController extends Controller
{
    public function index(Request $request)
    {
        $tag = $request->query('tag');
        $categorySlug = $request->query('category');
        $showArchived = $request->boolean('archived') && auth()->user()?->isAdmin();

        $query = Prompt::with('activeVersion', 'creator', 'category')->latest();

        if ($showArchived) {
            $query->withTrashed();
        }

        if ($tag) {
            $query->whereJsonContains('tags', $tag);
        }

        if ($categorySlug) {
            if ($categorySlug === 'uncategorized') {
                $query->whereNull('category_id');
            } else {
                $query->whereHas('category', fn ($q) => $q->where('slug', $categorySlug));
            }
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

        $categories = Category::withCount('prompts')->orderBy('sort_order')->orderBy('name')->get();
        $uncategorizedCount = Prompt::whereNull('category_id')->count();

        return view('prompts.index', compact('prompts', 'allTags', 'tag', 'showArchived', 'categories', 'categorySlug', 'uncategorizedCount'));
    }

    public function create()
    {
        $this->authorize('create', Prompt::class);
        $categories = Category::orderBy('sort_order')->orderBy('name')->get();

        return view('prompts.create', compact('categories'));
    }

    public function store(Request $request)
    {
        $this->authorize('create', Prompt::class);

        $data = $request->validate([
            'name'        => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'category_id' => ['nullable', 'exists:categories,id'],
            'tags'        => ['nullable', 'array'],
            'tags.*'      => ['string', 'max:50'],
        ]);

        $prompt = Prompt::create([
            'name'        => $data['name'],
            'description' => $data['description'] ?? null,
            'category_id' => $data['category_id'] ?? null,
            'tags'        => $this->normalizeTags($data['tags'] ?? []),
            'created_by'  => auth()->id(),
        ]);

        return redirect()->route('prompts.show', $prompt)->with('success', 'Prompt created.');
    }

    public function show(Prompt $prompt)
    {
        if ($prompt->trashed() && !auth()->user()?->isAdmin()) {
            abort(404);
        }

        $prompt->load('activeVersion.creator', 'creator');

        $libraryCount = $prompt->activeVersion
            ? LibraryEntry::where('prompt_version_id', $prompt->activeVersion->id)->count()
            : 0;

        return view('prompts.show', compact('prompt', 'libraryCount'));
    }

    public function edit(Prompt $prompt)
    {
        $this->authorize('update', $prompt);
        $categories = Category::orderBy('sort_order')->orderBy('name')->get();

        return view('prompts.edit', compact('prompt', 'categories'));
    }

    public function update(Request $request, Prompt $prompt)
    {
        $this->authorize('update', $prompt);

        $data = $request->validate([
            'name'        => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'category_id' => ['nullable', 'exists:categories,id'],
            'tags'        => ['nullable', 'array'],
            'tags.*'      => ['string', 'max:50'],
        ]);

        $prompt->update([
            'name'        => $data['name'],
            'description' => $data['description'] ?? null,
            'category_id' => $data['category_id'] ?? null,
            'tags'        => $this->normalizeTags($data['tags'] ?? []),
        ]);

        return redirect()->route('prompts.show', $prompt)->with('success', 'Prompt updated.');
    }

    public function destroy(Prompt $prompt)
    {
        $this->authorize('delete', $prompt);
        $prompt->delete();

        return redirect()->route('prompts.index')->with('success', 'Prompt archived.');
    }

    public function restore(Prompt $prompt)
    {
        $this->authorize('restore', $prompt);
        $prompt->restore();

        return redirect()->route('prompts.show', $prompt)->with('success', 'Prompt restored.');
    }

    public function forceDelete(Prompt $prompt)
    {
        $this->authorize('forceDelete', $prompt);
        $prompt->forceDelete();

        return redirect()->route('prompts.index')->with('success', 'Prompt permanently deleted.');
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
