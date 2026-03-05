<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Prompt;
use App\Models\PromptVersion;
use App\Services\VersioningService;
use Illuminate\Http\Request;

class PromptVersionController extends Controller
{
    public function __construct(private VersioningService $versioning) {}

    public function index(Prompt $prompt)
    {
        $versions = $prompt->versions()->with('creator')->get();
        return view('prompts.versions.index', compact('prompt', 'versions'));
    }

    public function create(Prompt $prompt)
    {
        $this->authorize('createVersion', $prompt);
        $latest = $prompt->versions()->first();
        return view('prompts.versions.create', compact('prompt', 'latest'));
    }

    public function store(Request $request, Prompt $prompt)
    {
        $this->authorize('createVersion', $prompt);

        $data = $request->validate([
            'content'        => ['required', 'string'],
            'commit_message' => ['nullable', 'string', 'max:500'],
        ]);

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

        return view('prompts.versions.show', compact('prompt', 'version'));
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
