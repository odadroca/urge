<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <div>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ $prompt->name }}</h2>
                <p class="text-sm text-gray-500 font-mono mt-0.5">{{ $prompt->slug }}</p>
            </div>
            <div class="flex gap-2">
                @if($prompt->activeVersion)
                <a href="{{ route('prompt-runs.create', $prompt) }}" class="inline-flex items-center px-4 py-2 bg-green-600 text-white text-sm rounded-md hover:bg-green-700">
                    Run
                </a>
                @endif
                @can('createVersion', $prompt)
                <a href="{{ route('prompts.versions.create', $prompt) }}" class="inline-flex items-center px-4 py-2 bg-indigo-600 text-white text-sm rounded-md hover:bg-indigo-700">
                    New Version
                </a>
                @endcan
                @can('update', $prompt)
                <a href="{{ route('prompts.edit', $prompt) }}" class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 text-gray-700 text-sm rounded-md hover:bg-gray-50">
                    Edit
                </a>
                @endcan
            </div>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if(session('success'))
                <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded">{{ session('success') }}</div>
            @endif

            {{-- Metadata --}}
            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                @if($prompt->description)
                <p class="text-gray-600 mb-4">{{ $prompt->description }}</p>
                @endif
                @if($prompt->tags && count($prompt->tags))
                <div class="flex flex-wrap gap-1.5 mb-4">
                    @foreach($prompt->tags as $tag)
                    <a href="{{ route('prompts.index', ['tag' => $tag]) }}"
                       class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-purple-50 text-purple-700 border border-purple-200 hover:bg-purple-100 transition">{{ $tag }}</a>
                    @endforeach
                </div>
                @endif
                <div class="flex flex-wrap gap-6 text-sm text-gray-500">
                    <span>Created by <strong>{{ $prompt->creator?->name }}</strong></span>
                    <span>{{ $prompt->created_at->format('Y-m-d') }}</span>
                    <a href="{{ route('prompts.versions.index', $prompt) }}" class="text-indigo-600 hover:underline">Version history</a>
                    <a href="{{ route('prompt-runs.index', $prompt) }}" class="text-indigo-600 hover:underline">Run history</a>
                </div>
            </div>

            {{-- Active version --}}
            @if($prompt->activeVersion)
            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                <div class="flex justify-between items-start mb-4">
                    <div>
                        <h3 class="font-semibold text-gray-800">Active Version</h3>
                        <p class="text-sm text-gray-500">
                            v{{ $prompt->activeVersion->version_number }}
                            @if($prompt->activeVersion->commit_message)
                                — {{ $prompt->activeVersion->commit_message }}
                            @endif
                            &bull; by {{ $prompt->activeVersion->creator?->name }}
                            &bull; {{ $prompt->activeVersion->created_at->format('Y-m-d H:i') }}
                        </p>
                    </div>
                    <a href="{{ route('prompts.versions.show', [$prompt, $prompt->activeVersion->version_number]) }}" class="text-sm text-indigo-600 hover:underline">View full</a>
                </div>

                @if($prompt->activeVersion->variables && count($prompt->activeVersion->variables))
                <div class="mb-4">
                    <p class="text-xs text-gray-500 mb-1">Variables</p>
                    <div class="flex flex-wrap gap-1.5">
                        @foreach($prompt->activeVersion->variables as $var)
                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-mono bg-indigo-50 text-indigo-700 border border-indigo-200">&#123;&#123;{{ $var }}&#125;&#125;</span>
                        @endforeach
                    </div>
                </div>
                @endif

                <div x-data="{ copied: false }" class="relative">
                    <pre x-ref="content" class="bg-gray-50 border border-gray-200 rounded p-4 pr-24 text-sm text-gray-800 whitespace-pre-wrap font-mono overflow-auto max-h-96">{{ $prompt->activeVersion->content }}</pre>
                    <button
                        @click="navigator.clipboard.writeText($refs.content.textContent.trim()); copied = true; setTimeout(() => copied = false, 2000)"
                        class="absolute top-3 right-3 inline-flex items-center gap-1.5 px-2.5 py-1.5 text-xs font-medium rounded border transition"
                        :class="copied ? 'bg-green-50 border-green-300 text-green-700' : 'bg-white border-gray-300 text-gray-500 hover:text-gray-700 hover:border-gray-400'">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <rect x="9" y="9" width="13" height="13" rx="2" stroke-linecap="round"/>
                            <path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1" stroke-linecap="round"/>
                        </svg>
                        <span x-text="copied ? 'Copied!' : 'Copy'"></span>
                    </button>
                </div>
            </div>
            @else
            <div class="bg-white shadow-sm sm:rounded-lg p-6 text-center text-gray-500">
                <p>No active version set.</p>
                @can('createVersion', $prompt)
                <a href="{{ route('prompts.versions.create', $prompt) }}" class="mt-2 inline-flex items-center text-sm text-indigo-600 hover:underline">Create the first version</a>
                @endcan
            </div>
            @endif

            {{-- API snippet --}}
            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                <h3 class="font-semibold text-gray-800 mb-3">API Usage</h3>
                <pre class="bg-gray-900 text-green-400 rounded p-4 text-xs overflow-auto">GET /api/v1/prompts/{{ $prompt->slug }}
Authorization: Bearer YOUR_API_KEY

# Render with variables:
POST /api/v1/prompts/{{ $prompt->slug }}/render
Authorization: Bearer YOUR_API_KEY
Content-Type: application/json
{"variables": { {{ $prompt->activeVersion?->variables ? '"' . implode('": "...", "', $prompt->activeVersion->variables ?? []) . '": "..."' : '' }} }}</pre>
            </div>

            @can('delete', $prompt)
            <div class="bg-white shadow-sm sm:rounded-lg p-6 border-t-4 border-red-300">
                <h3 class="font-semibold text-red-700 mb-2">Danger Zone</h3>
                <form method="POST" action="{{ route('prompts.destroy', $prompt) }}" onsubmit="return confirm('Delete this prompt and all its versions?')">
                    @csrf @method('DELETE')
                    <button type="submit" class="px-4 py-2 bg-red-600 text-white text-sm rounded-md hover:bg-red-700">Delete Prompt</button>
                </form>
            </div>
            @endcan
        </div>
    </div>
</x-app-layout>
