<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\LibraryEntry;
use App\Models\Prompt;
use App\Models\PromptRun;
use App\Models\Story;
use App\Models\User;

class DashboardController extends Controller
{
    public function index()
    {
        // ── Stats ──────────────────────────────────────────────────────────
        $stats = [
            'prompts'  => Prompt::count(),
            'active'   => Prompt::whereNotNull('active_version_id')->count(),
            'runs'     => PromptRun::count(),
            'library'  => LibraryEntry::count(),
            'stories'  => Story::count(),
        ];

        // ── Recently updated prompts ───────────────────────────────────────
        $recentPrompts = Prompt::with('activeVersion')
            ->latest('updated_at')
            ->limit(5)
            ->get();

        // ── Prompts missing an active version ──────────────────────────────
        $draftPrompts = Prompt::whereNull('active_version_id')
            ->withCount('versions')
            ->latest('updated_at')
            ->limit(5)
            ->get();

        // ── Recent runs ────────────────────────────────────────────────────
        $recentRuns = PromptRun::with(['prompt', 'creator'])
            ->withCount('responses')
            ->latest('created_at')
            ->limit(5)
            ->get();

        // ── Recent library entries ─────────────────────────────────────────
        $recentLibrary = LibraryEntry::with(['prompt', 'provider'])
            ->latest()
            ->limit(5)
            ->get();

        // ── Top tags ───────────────────────────────────────────────────────
        $topTags = Prompt::whereNotNull('tags')
            ->pluck('tags')
            ->flatten()
            ->filter()
            ->countBy()
            ->sortByDesc(fn ($c) => $c)
            ->take(12);

        return view('dashboard', compact(
            'stats',
            'recentPrompts',
            'draftPrompts',
            'recentRuns',
            'recentLibrary',
            'topTags',
        ));
    }
}
