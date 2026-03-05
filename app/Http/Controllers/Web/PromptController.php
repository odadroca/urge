<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Prompt;
use Illuminate\Http\Request;

class PromptController extends Controller
{
    public function index()
    {
        $prompts = Prompt::with('activeVersion', 'creator')
            ->latest()
            ->paginate(20);

        return view('prompts.index', compact('prompts'));
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
        ]);

        $prompt = Prompt::create([
            'name'        => $data['name'],
            'description' => $data['description'] ?? null,
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
        ]);

        $prompt->update([
            'name'        => $data['name'],
            'description' => $data['description'] ?? null,
        ]);

        return redirect()->route('prompts.show', $prompt)->with('success', 'Prompt updated.');
    }

    public function destroy(Prompt $prompt)
    {
        $this->authorize('delete', $prompt);
        $prompt->delete();

        return redirect()->route('prompts.index')->with('success', 'Prompt deleted.');
    }
}
