<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-start">
            <div>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">Compare Responses</h2>
                <p class="text-sm text-gray-500 mt-0.5">
                    <a href="{{ route('library.index') }}" class="text-indigo-600 hover:underline">Library</a>
                    &rsaquo;
                    <a href="{{ route('prompts.show', $version->prompt) }}" class="text-indigo-600 hover:underline">{{ $version->prompt->name }}</a>
                    &rsaquo; v{{ $version->version_number }}
                </p>
            </div>
            <a href="{{ route('library.index') }}"
               class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 text-gray-700 text-sm rounded-md hover:bg-gray-50">
                Back to Library
            </a>
        </div>
    </x-slot>

    <div class="py-12" x-data="compareView()">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            {{-- Version header card --}}
            <div class="bg-white shadow-sm sm:rounded-lg p-5">
                <div class="flex flex-wrap items-start justify-between gap-4">
                    <div class="flex-1 min-w-0">
                        <div class="flex flex-wrap items-center gap-3 mb-1">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-700 font-mono">
                                v{{ $version->version_number }}
                            </span>
                            @if($version->commit_message)
                            <span class="text-sm text-gray-500 italic">"{{ $version->commit_message }}"</span>
                            @endif
                        </div>
                        <p class="text-sm text-gray-400">
                            by {{ $version->creator?->name }}
                            &bull; {{ $version->created_at->format('Y-m-d H:i') }}
                            &bull; <a href="{{ route('prompts.versions.show', [$version->prompt, $version->version_number]) }}"
                                      class="text-indigo-600 hover:underline">View full version</a>
                        </p>
                    </div>
                    <button @click="promptOpen = !promptOpen"
                            class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium border rounded-md transition bg-white border-gray-300 text-gray-600 hover:bg-gray-50">
                        <svg class="w-3.5 h-3.5 transition-transform duration-150" :class="promptOpen ? 'rotate-90' : ''"
                             fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
                        </svg>
                        <span x-text="promptOpen ? 'Hide prompt' : 'Show prompt'"></span>
                    </button>
                </div>

                <div x-show="promptOpen"
                     x-transition:enter="transition ease-out duration-150"
                     x-transition:enter-start="opacity-0"
                     x-transition:enter-end="opacity-100"
                     x-transition:leave="transition ease-in duration-100"
                     x-transition:leave-start="opacity-100"
                     x-transition:leave-end="opacity-0"
                     class="mt-4 pt-4 border-t border-gray-100">
                    <p class="text-xs text-gray-400 uppercase tracking-wide font-medium mb-2">Prompt Content</p>
                    <pre class="bg-gray-50 border border-gray-200 rounded p-4 text-sm text-gray-800 whitespace-pre-wrap font-mono overflow-auto max-h-60 leading-relaxed">{{ $version->content }}</pre>
                </div>
            </div>

            {{-- Empty state --}}
            @if($entries->isEmpty())
            <div class="bg-white shadow-sm sm:rounded-lg p-12 text-center">
                <p class="text-gray-500 text-lg mb-4">No library entries for this version yet.</p>
                <a href="{{ route('library.create', ['prompt_id' => $version->prompt_id, 'prompt_version_id' => $version->id]) }}"
                   class="inline-flex items-center px-4 py-2 bg-indigo-600 text-white text-sm rounded-md hover:bg-indigo-700">
                    Add the first entry
                </a>
            </div>
            @else

            {{-- Single-entry notice --}}
            @if($entries->count() === 1)
            <div class="bg-amber-50 border border-amber-200 text-amber-700 px-4 py-3 rounded text-sm">
                Only one library entry exists for this version. Add more to compare responses side by side.
            </div>
            @endif

            {{-- Controls bar --}}
            <div class="flex items-center justify-between">
                <p class="text-sm text-gray-500">
                    {{ $entries->count() }} {{ $entries->count() === 1 ? 'response' : 'responses' }}
                </p>
                @if($entries->count() >= 2)
                <button @click="toggleLayout()"
                        :class="isSideBySide
                            ? 'bg-indigo-600 text-white border-indigo-600'
                            : 'bg-white text-gray-600 border-gray-300 hover:bg-gray-50'"
                        class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium border rounded-md transition">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h10M4 18h16"/>
                    </svg>
                    <span x-text="isSideBySide ? 'Stacked' : 'Side by side'"></span>
                </button>
                @endif
            </div>

            {{-- Entry cards --}}
            <div :class="isSideBySide
                    ? 'grid grid-cols-1 lg:grid-cols-2 gap-4'
                    : 'space-y-4'">

                @foreach($entries as $entry)
                <div class="bg-white shadow-sm sm:rounded-lg overflow-hidden flex flex-col">

                    {{-- Card header --}}
                    <div class="px-5 py-4 border-b border-gray-100 flex flex-wrap items-start justify-between gap-3">
                        <div class="flex flex-wrap items-center gap-2">
                            <span class="inline-flex items-center px-2.5 py-1 rounded-md text-xs font-medium bg-indigo-50 text-indigo-700 border border-indigo-100 font-mono">
                                @if($entry->provider){{ $entry->provider->name }} / @endif{{ $entry->model_used }}
                            </span>
                            @if($entry->rating)
                            <span class="text-yellow-500 text-sm">{{ str_repeat('★', $entry->rating) }}<span class="text-gray-200">{{ str_repeat('★', 5 - $entry->rating) }}</span></span>
                            <span class="text-xs text-gray-400">{{ $entry->rating }}/5</span>
                            @else
                            <span class="text-xs text-gray-300 italic">Not rated</span>
                            @endif
                        </div>
                        <div class="flex items-center gap-3 text-xs text-gray-400">
                            <span>{{ $entry->created_at->format('Y-m-d H:i') }}</span>
                            <a href="{{ route('library.show', $entry) }}" class="text-indigo-600 hover:underline font-medium">Full entry →</a>
                        </div>
                    </div>

                    {{-- Response with isolated copy scope --}}
                    <div class="p-5 flex-1 flex flex-col" x-data="{ copied: false }">
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-xs font-medium text-gray-400 uppercase tracking-wide">Response</span>
                            <div class="flex items-center gap-2">
                                <span class="text-xs text-gray-400">{{ str_word_count($entry->response_text) }} words</span>
                                <button
                                    @click="navigator.clipboard.writeText($refs.responseText.textContent.trim()); copied = true; setTimeout(() => copied = false, 2000)"
                                    class="inline-flex items-center gap-1.5 px-2.5 py-1.5 text-xs font-medium rounded border transition"
                                    :class="copied ? 'bg-green-50 border-green-300 text-green-700' : 'bg-white border-gray-300 text-gray-500 hover:text-gray-700 hover:border-gray-400'">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                        <rect x="9" y="9" width="13" height="13" rx="2" stroke-linecap="round"/>
                                        <path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1" stroke-linecap="round"/>
                                    </svg>
                                    <span x-text="copied ? 'Copied!' : 'Copy'"></span>
                                </button>
                            </div>
                        </div>
                        <pre x-ref="responseText"
                             class="bg-gray-50 border border-gray-200 rounded p-4 text-sm text-gray-800 whitespace-pre-wrap font-mono overflow-auto max-h-96 leading-relaxed flex-1">{{ $entry->response_text }}</pre>
                    </div>

                    {{-- Notes --}}
                    @if($entry->notes)
                    <div class="px-5 pb-4 border-t border-gray-50 pt-3">
                        <p class="text-xs text-gray-400 uppercase tracking-wide font-medium mb-1">Notes</p>
                        <p class="text-sm text-gray-600 whitespace-pre-wrap">{{ $entry->notes }}</p>
                    </div>
                    @endif

                    {{-- Footer --}}
                    <div class="px-5 py-3 bg-gray-50 border-t border-gray-100 text-xs text-gray-400">
                        Added by {{ $entry->creator?->name }}
                    </div>
                </div>
                @endforeach

            </div>
            @endif

        </div>
    </div>

    <script>
    function compareView() {
        return {
            promptOpen: false,
            layout: 'stacked',
            get isSideBySide() { return this.layout === 'sidebyside'; },
            toggleLayout() { this.layout = this.isSideBySide ? 'stacked' : 'sidebyside'; }
        };
    }
    </script>
</x-app-layout>
