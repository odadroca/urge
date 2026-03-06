<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-start">
            <div>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    v{{ $version->version_number }}
                    @if($prompt->active_version_id === $version->id)
                    <span class="ml-2 inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">active</span>
                    @endif
                </h2>
                <p class="text-sm text-gray-500 mt-0.5">
                    <a href="{{ route('prompts.show', $prompt) }}" class="text-indigo-600 hover:underline">{{ $prompt->name }}</a>
                    &rsaquo; <a href="{{ route('prompts.versions.index', $prompt) }}" class="text-indigo-600 hover:underline">versions</a>
                </p>
            </div>
            <div class="flex gap-2">
                @can('activateVersion', $prompt)
                @if($prompt->active_version_id !== $version->id)
                <form method="POST" action="{{ route('prompts.versions.activate', [$prompt, $version->version_number]) }}">
                    @csrf
                    <button type="submit" class="inline-flex items-center px-4 py-2 bg-green-600 text-white text-sm rounded-md hover:bg-green-700">Set as Active</button>
                </form>
                @endif
                @endcan
            </div>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if(session('success'))
                <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded">{{ session('success') }}</div>
            @endif

            <div class="bg-white shadow-sm sm:rounded-lg p-6"
                 @if($version->includes) x-data="{
                     open: false, loading: false, composed: null, includes: [], error: null,
                     async load() {
                         if (this.composed !== null) { this.open = !this.open; return; }
                         this.loading = true; this.open = true;
                         try {
                             const r = await fetch('{{ route('prompts.versions.compose', [$prompt, $version->version_number]) }}');
                             const d = await r.json();
                             if (d.error) { this.error = d.error; } else { this.composed = d.composed; this.includes = d.includes; }
                         } catch { this.error = 'Failed to load composed content.'; }
                         this.loading = false;
                     }
                 }" @endif>
                <div class="flex flex-wrap gap-6 text-sm text-gray-500 mb-4">
                    <span>By <strong>{{ $version->creator?->name }}</strong></span>
                    <span>{{ $version->created_at->format('Y-m-d H:i') }}</span>
                    @if($version->commit_message)
                    <span class="italic">"{{ $version->commit_message }}"</span>
                    @endif
                </div>

                @if($version->variables && count($version->variables))
                <div class="mb-4">
                    <p class="text-xs text-gray-500 mb-1.5 font-medium uppercase tracking-wider">Variables</p>
                    <div class="flex flex-wrap gap-1.5">
                        @foreach($version->variables as $var)
                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-mono bg-indigo-50 text-indigo-700 border border-indigo-200">&#123;&#123;{{ $var }}&#125;&#125;</span>
                        @endforeach
                    </div>
                </div>
                @endif

                @if($version->includes && count($version->includes))
                <div class="mb-4">
                    <p class="text-xs text-gray-500 mb-1.5 font-medium uppercase tracking-wider">Includes</p>
                    <div class="flex flex-wrap gap-1.5">
                        @foreach($version->includes as $slug)
                        <a href="{{ route('prompts.show', $slug) }}"
                           class="inline-flex items-center px-2 py-0.5 rounded text-xs font-mono bg-emerald-50 text-emerald-700 border border-emerald-200 hover:bg-emerald-100 transition">&#123;&#123;&gt;{{ $slug }}&#125;&#125;</a>
                        @endforeach
                    </div>
                </div>
                @endif

                <div>
                    <p class="text-xs text-gray-500 mb-1.5 font-medium uppercase tracking-wider">Content</p>
                    <pre class="bg-gray-50 border border-gray-200 rounded p-4 text-sm text-gray-800 whitespace-pre-wrap font-mono overflow-auto">{{ $version->content }}</pre>
                </div>

                @if($version->includes)
                <div class="mt-4">
                    <button @click="load()"
                            class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium border rounded-md transition"
                            :class="open ? 'bg-emerald-50 border-emerald-300 text-emerald-700' : 'bg-white border-gray-300 text-gray-600 hover:bg-gray-50'">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h10M4 18h16"/>
                        </svg>
                        <span x-text="loading ? 'Resolving…' : (open ? 'Hide composed' : 'Preview composed')">Preview composed</span>
                    </button>

                    <div x-show="open" x-cloak class="mt-3">
                        <template x-if="error">
                            <p class="text-sm text-red-500 italic" x-text="error"></p>
                        </template>
                        <template x-if="composed !== null && !error">
                            <div>
                                <div class="flex flex-wrap gap-1.5 mb-2">
                                    <template x-for="slug in includes" :key="slug">
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-mono bg-emerald-50 text-emerald-600 border border-emerald-200" x-text="slug + ' inlined'"></span>
                                    </template>
                                </div>
                                <pre class="bg-emerald-50 border border-emerald-200 rounded p-4 text-sm text-gray-800 whitespace-pre-wrap font-mono overflow-auto" x-text="composed"></pre>
                            </div>
                        </template>
                    </div>
                </div>
                @endif

                <div class="mt-4 pt-4 border-t border-gray-100 flex items-center justify-between">
                    <span class="text-sm text-gray-500">
                        {{ $libraryCount }} {{ $libraryCount === 1 ? 'library entry' : 'library entries' }}
                    </span>
                    @if($libraryCount > 0)
                    <a href="{{ route('library.compare', ['version_id' => $version->id]) }}"
                       class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium border rounded-md bg-white border-gray-300 text-gray-600 hover:bg-gray-50 transition">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M8 7h12M8 12h8M8 17h12"/>
                        </svg>
                        {{ $libraryCount >= 2 ? 'Compare ' . $libraryCount . ' responses' : 'View library entry' }}
                    </a>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
