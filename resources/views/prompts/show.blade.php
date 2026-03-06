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
                <div class="relative" x-data="{ open: false }">
                    <div class="inline-flex rounded-md shadow-sm">
                        <a href="{{ route('prompts.versions.create', $prompt) }}" class="inline-flex items-center px-4 py-2 bg-indigo-600 text-white text-sm rounded-l-md hover:bg-indigo-700">
                            New Version
                        </a>
                        <button type="button" @click="open = !open" @click.away="open = false"
                                class="inline-flex items-center px-2 py-2 bg-indigo-700 text-white text-sm rounded-r-md hover:bg-indigo-800 border-l border-indigo-500">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>
                        </button>
                    </div>
                    <div x-show="open" x-cloak class="absolute right-0 mt-1 w-48 bg-white border border-gray-200 rounded-md shadow-lg z-10">
                        <a href="{{ route('prompts.versions.create', $prompt) }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">Text Editor</a>
                        <a href="{{ route('prompts.versions.designer', $prompt) }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">Visual Designer</a>
                    </div>
                </div>
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

            @if($prompt->trashed())
            <div class="bg-amber-50 border border-amber-300 text-amber-800 px-4 py-3 rounded flex items-center justify-between">
                <span>This prompt was archived on {{ $prompt->deleted_at->format('Y-m-d') }}.</span>
                <div class="flex gap-2">
                    <form method="POST" action="{{ route('prompts.restore', $prompt) }}">
                        @csrf
                        <button type="submit" class="px-3 py-1 bg-indigo-600 text-white text-xs rounded-md hover:bg-indigo-700">Restore</button>
                    </form>
                    <form method="POST" action="{{ route('prompts.force-delete', $prompt) }}" onsubmit="return confirm('Permanently delete this prompt? This cannot be undone.')">
                        @csrf @method('DELETE')
                        <button type="submit" class="px-3 py-1 bg-red-600 text-white text-xs rounded-md hover:bg-red-700">Delete permanently</button>
                    </form>
                </div>
            </div>
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
                    @if($prompt->activeVersion)
                        @if($libraryCount >= 2)
                            <a href="{{ route('library.compare', ['version_id' => $prompt->activeVersion->id]) }}" class="text-indigo-600 hover:underline">Compare {{ $libraryCount }} responses</a>
                        @elseif($libraryCount === 1)
                            <a href="{{ route('library.index', ['prompt_id' => $prompt->id]) }}" class="text-indigo-600 hover:underline">1 library entry</a>
                        @else
                            <span class="text-gray-400">No library entries yet</span>
                        @endif
                    @endif
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

                @if($prompt->activeVersion->includes && count($prompt->activeVersion->includes))
                <div class="mb-4">
                    <p class="text-xs text-gray-500 mb-1">Includes</p>
                    <div class="flex flex-wrap gap-1.5">
                        @foreach($prompt->activeVersion->includes as $inclSlug)
                        <a href="{{ route('prompts.show', $inclSlug) }}"
                           class="inline-flex items-center px-2 py-0.5 rounded text-xs font-mono bg-emerald-50 text-emerald-700 border border-emerald-200 hover:bg-emerald-100 transition">&#123;&#123;&gt;{{ $inclSlug }}&#125;&#125;</a>
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

                @if($prompt->activeVersion->includes && count($prompt->activeVersion->includes))
                <div class="mt-4"
                     x-data="{
                         open: true, loading: false, composed: null, includes: [], error: null, copied: false,
                         async load() {
                             this.loading = true;
                             try {
                                 const r = await fetch('{{ route('prompts.versions.compose', [$prompt, $prompt->activeVersion->version_number]) }}');
                                 const d = await r.json();
                                 if (d.error) { this.error = d.error; } else { this.composed = d.composed; this.includes = d.includes; }
                             } catch { this.error = 'Failed to load composed content.'; }
                             this.loading = false;
                         }
                     }"
                     x-init="load()">
                    <div class="flex items-center gap-3 mb-2">
                        <button @click="open = !open"
                                class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium border rounded-md transition"
                                :class="open ? 'bg-emerald-50 border-emerald-300 text-emerald-700' : 'bg-white border-gray-300 text-gray-600 hover:bg-gray-50'">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h10M4 18h16"/>
                            </svg>
                            <span x-text="loading ? 'Resolving…' : (open ? 'Hide composed' : 'Preview composed')">Preview composed</span>
                        </button>
                    </div>

                    <div x-show="open">
                        <template x-if="loading">
                            <div class="bg-emerald-50 border border-emerald-200 rounded p-4 text-sm text-emerald-600 italic animate-pulse">Resolving includes…</div>
                        </template>
                        <template x-if="error">
                            <p class="text-sm text-red-500 italic" x-text="error"></p>
                        </template>
                        <template x-if="composed !== null && !error">
                            <div x-data="{ copied: false }">
                                <div class="flex flex-wrap gap-1.5 mb-2">
                                    <template x-for="slug in includes" :key="slug">
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-mono bg-emerald-50 text-emerald-600 border border-emerald-200" x-text="slug + ' inlined'"></span>
                                    </template>
                                </div>
                                <div class="relative">
                                    <pre x-ref="composedContent" class="bg-emerald-50 border border-emerald-200 rounded p-4 pr-24 text-sm text-gray-800 whitespace-pre-wrap font-mono overflow-auto max-h-96" x-text="composed"></pre>
                                    <button
                                        @click="navigator.clipboard.writeText($refs.composedContent.textContent.trim()); copied = true; setTimeout(() => copied = false, 2000)"
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
                        </template>
                    </div>
                </div>
                @endif
            </div>
            @else
            <div class="bg-white shadow-sm sm:rounded-lg p-6 text-center text-gray-500">
                <p>No active version set.</p>
                @can('createVersion', $prompt)
                <div class="mt-2 flex items-center justify-center gap-3">
                    <a href="{{ route('prompts.versions.create', $prompt) }}" class="inline-flex items-center text-sm text-indigo-600 hover:underline">Create the first version</a>
                    <span class="text-gray-300">or</span>
                    <a href="{{ route('prompts.versions.designer', $prompt) }}" class="inline-flex items-center text-sm text-indigo-600 hover:underline">Use the Visual Designer</a>
                </div>
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

            @if(!$prompt->trashed())
            @can('delete', $prompt)
            <div class="bg-white shadow-sm sm:rounded-lg p-6 border-t-4 border-red-300">
                <h3 class="font-semibold text-red-700 mb-2">Danger Zone</h3>
                <form method="POST" action="{{ route('prompts.destroy', $prompt) }}" onsubmit="return confirm('Archive this prompt? It can be restored by an admin.')">
                    @csrf @method('DELETE')
                    <button type="submit" class="px-4 py-2 bg-red-600 text-white text-sm rounded-md hover:bg-red-700">Archive Prompt</button>
                </form>
            </div>
            @endcan
            @endif
        </div>
    </div>
</x-app-layout>
