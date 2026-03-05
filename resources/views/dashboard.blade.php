<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Dashboard</h2>
    </x-slot>

    <div class="py-10">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-8">

            @if(session('success'))
            <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded">{{ session('success') }}</div>
            @endif

            {{-- ── Stat cards ───────────────────────────────────────────── --}}
            <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-4">
                <a href="{{ route('prompts.index') }}"
                   class="bg-white shadow-sm sm:rounded-lg p-5 hover:shadow-md transition-shadow">
                    <div class="text-3xl font-bold text-indigo-600">{{ $stats['prompts'] }}</div>
                    <div class="text-sm text-gray-500 mt-1">Prompts</div>
                </a>
                <a href="{{ route('prompts.index') }}"
                   class="bg-white shadow-sm sm:rounded-lg p-5 hover:shadow-md transition-shadow">
                    <div class="text-3xl font-bold text-green-600">{{ $stats['active'] }}</div>
                    <div class="text-sm text-gray-500 mt-1">Active versions</div>
                    @if($stats['prompts'] > 0)
                    <div class="text-xs text-gray-400 mt-0.5">
                        {{ round($stats['active'] / $stats['prompts'] * 100) }}% of prompts
                    </div>
                    @endif
                </a>
                <a href="{{ route('library.index') }}"
                   class="bg-white shadow-sm sm:rounded-lg p-5 hover:shadow-md transition-shadow">
                    <div class="text-3xl font-bold text-blue-600">{{ $stats['library'] }}</div>
                    <div class="text-sm text-gray-500 mt-1">Library entries</div>
                </a>
                <a href="{{ route('stories.index') }}"
                   class="bg-white shadow-sm sm:rounded-lg p-5 hover:shadow-md transition-shadow">
                    <div class="text-3xl font-bold text-purple-600">{{ $stats['stories'] }}</div>
                    <div class="text-sm text-gray-500 mt-1">Stories</div>
                </a>
                <div class="bg-white shadow-sm sm:rounded-lg p-5">
                    <div class="text-3xl font-bold text-gray-600">{{ $stats['runs'] }}</div>
                    <div class="text-sm text-gray-500 mt-1">Total runs</div>
                </div>
            </div>

            {{-- ── Main content grid ────────────────────────────────────── --}}
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

                {{-- Recent prompts (2/3 width) --}}
                <div class="lg:col-span-2 bg-white shadow-sm sm:rounded-lg">
                    <div class="flex items-center justify-between px-6 pt-5 pb-3 border-b border-gray-100">
                        <h3 class="font-semibold text-gray-800">Recent Prompts</h3>
                        <a href="{{ route('prompts.index') }}" class="text-xs text-indigo-600 hover:underline">View all →</a>
                    </div>
                    @if($recentPrompts->isEmpty())
                    <p class="px-6 py-8 text-sm text-gray-400 text-center italic">No prompts yet.</p>
                    @else
                    <ul class="divide-y divide-gray-100">
                        @foreach($recentPrompts as $prompt)
                        <li class="px-6 py-3 flex items-center gap-3 hover:bg-gray-50 transition-colors">
                            <a href="{{ route('prompts.show', $prompt) }}" class="flex-1 min-w-0">
                                <div class="flex items-center gap-2 flex-wrap">
                                    <span class="font-medium text-gray-800 text-sm truncate">{{ $prompt->name }}</span>
                                    @if($prompt->activeVersion)
                                    <span class="flex-shrink-0 inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-700">v{{ $prompt->activeVersion->version_number }}</span>
                                    @else
                                    <span class="flex-shrink-0 inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-700">no version</span>
                                    @endif
                                </div>
                                @if($prompt->tags && count($prompt->tags))
                                <div class="flex flex-wrap gap-1 mt-1">
                                    @foreach(array_slice($prompt->tags, 0, 3) as $tag)
                                    <span class="inline-flex items-center px-1.5 py-0 rounded text-xs bg-purple-50 text-purple-600 border border-purple-100">{{ $tag }}</span>
                                    @endforeach
                                </div>
                                @endif
                            </a>
                            <span class="flex-shrink-0 text-xs text-gray-400">{{ $prompt->updated_at->diffForHumans() }}</span>
                        </li>
                        @endforeach
                    </ul>
                    @endif
                </div>

                {{-- Needs attention + quick actions (1/3 width) --}}
                <div class="space-y-6">

                    {{-- Prompts without active version --}}
                    @if($draftPrompts->isNotEmpty())
                    <div class="bg-white shadow-sm sm:rounded-lg">
                        <div class="flex items-center gap-2 px-5 pt-4 pb-3 border-b border-gray-100">
                            <svg class="w-4 h-4 text-yellow-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/>
                            </svg>
                            <h3 class="font-semibold text-gray-800 text-sm">Needs attention</h3>
                        </div>
                        <ul class="divide-y divide-gray-100">
                            @foreach($draftPrompts as $prompt)
                            <li class="px-5 py-2.5 flex items-center justify-between gap-2 hover:bg-gray-50">
                                <span class="text-sm text-gray-700 truncate">{{ $prompt->name }}</span>
                                <a href="{{ route('prompts.versions.create', $prompt) }}"
                                   class="flex-shrink-0 text-xs text-indigo-600 hover:underline">
                                    {{ $prompt->versions_count > 0 ? 'Set active' : 'Add version' }}
                                </a>
                            </li>
                            @endforeach
                        </ul>
                    </div>
                    @endif

                    {{-- Quick actions --}}
                    <div class="bg-white shadow-sm sm:rounded-lg p-5">
                        <h3 class="font-semibold text-gray-800 text-sm mb-3">Quick Actions</h3>
                        <div class="space-y-2">
                            @can('create', App\Models\Prompt::class)
                            <a href="{{ route('prompts.create') }}"
                               class="flex items-center gap-2 w-full px-3 py-2 text-sm text-gray-700 border border-gray-200 rounded-md hover:bg-gray-50 transition-colors">
                                <svg class="w-4 h-4 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
                                New Prompt
                            </a>
                            @endcan
                            <a href="{{ route('library.create') }}"
                               class="flex items-center gap-2 w-full px-3 py-2 text-sm text-gray-700 border border-gray-200 rounded-md hover:bg-gray-50 transition-colors">
                                <svg class="w-4 h-4 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M5 5a2 2 0 012-2h10a2 2 0 012 2v16l-7-3.5L5 21V5z"/></svg>
                                Add Library Entry
                            </a>
                            <a href="{{ route('stories.create') }}"
                               class="flex items-center gap-2 w-full px-3 py-2 text-sm text-gray-700 border border-gray-200 rounded-md hover:bg-gray-50 transition-colors">
                                <svg class="w-4 h-4 text-purple-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                                New Story
                            </a>
                            <a href="{{ route('api-keys.create') }}"
                               class="flex items-center gap-2 w-full px-3 py-2 text-sm text-gray-700 border border-gray-200 rounded-md hover:bg-gray-50 transition-colors">
                                <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/></svg>
                                New API Key
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            {{-- ── Bottom row ───────────────────────────────────────────── --}}
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

                {{-- Recent runs --}}
                <div class="bg-white shadow-sm sm:rounded-lg">
                    <div class="flex items-center justify-between px-6 pt-5 pb-3 border-b border-gray-100">
                        <h3 class="font-semibold text-gray-800">Recent Runs</h3>
                    </div>
                    @if($recentRuns->isEmpty())
                    <p class="px-6 py-8 text-sm text-gray-400 text-center italic">No runs yet.</p>
                    @else
                    <ul class="divide-y divide-gray-100">
                        @foreach($recentRuns as $run)
                        <li class="px-6 py-3 hover:bg-gray-50 transition-colors">
                            <a href="{{ route('prompt-runs.show', [$run->prompt, $run]) }}" class="block">
                                <div class="flex items-center justify-between gap-2">
                                    <span class="text-sm font-medium text-gray-800 truncate">{{ $run->prompt?->name ?? '—' }}</span>
                                    <span class="flex-shrink-0 text-xs text-gray-400">{{ $run->created_at->diffForHumans() }}</span>
                                </div>
                                <div class="mt-0.5 text-xs text-gray-400">
                                    {{ $run->responses_count }} response{{ $run->responses_count !== 1 ? 's' : '' }}
                                    @if($run->creator) &bull; {{ $run->creator->name }}@endif
                                </div>
                            </a>
                        </li>
                        @endforeach
                    </ul>
                    @endif
                </div>

                {{-- Recent library entries --}}
                <div class="bg-white shadow-sm sm:rounded-lg">
                    <div class="flex items-center justify-between px-6 pt-5 pb-3 border-b border-gray-100">
                        <h3 class="font-semibold text-gray-800">Recent Library</h3>
                        <a href="{{ route('library.index') }}" class="text-xs text-indigo-600 hover:underline">View all →</a>
                    </div>
                    @if($recentLibrary->isEmpty())
                    <p class="px-6 py-8 text-sm text-gray-400 text-center italic">No library entries yet.</p>
                    @else
                    <ul class="divide-y divide-gray-100">
                        @foreach($recentLibrary as $entry)
                        <li class="px-6 py-3 hover:bg-gray-50 transition-colors">
                            <a href="{{ route('library.show', $entry) }}" class="block">
                                <div class="flex items-center justify-between gap-2">
                                    <span class="text-sm font-medium text-gray-800 truncate">{{ $entry->prompt?->name ?? '—' }}</span>
                                    @if($entry->rating)
                                    <span class="flex-shrink-0 text-xs text-yellow-500">{{ str_repeat('★', $entry->rating) }}</span>
                                    @endif
                                </div>
                                <div class="mt-0.5 text-xs text-gray-400">
                                    {{ $entry->provider?->name ?? 'Custom' }} &mdash; <span class="font-mono">{{ $entry->model_used }}</span>
                                </div>
                            </a>
                        </li>
                        @endforeach
                    </ul>
                    @endif
                </div>

                {{-- Top tags --}}
                <div class="bg-white shadow-sm sm:rounded-lg p-6">
                    <h3 class="font-semibold text-gray-800 mb-4">Top Tags</h3>
                    @if($topTags->isEmpty())
                    <p class="text-sm text-gray-400 italic">No tags used yet.</p>
                    @else
                    <div class="flex flex-wrap gap-2">
                        @foreach($topTags as $tag => $count)
                        <a href="{{ route('prompts.index', ['tag' => $tag]) }}"
                           class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium bg-purple-50 text-purple-700 border border-purple-200 hover:bg-purple-100 transition">
                            {{ $tag }}
                            <span class="text-purple-400">{{ $count }}</span>
                        </a>
                        @endforeach
                    </div>
                    @endif
                </div>

            </div>
        </div>
    </div>
</x-app-layout>
