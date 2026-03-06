<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <div>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ $story->title }}</h2>
                <p class="text-sm text-gray-500 mt-0.5">
                    <a href="{{ route('stories.index') }}" class="text-indigo-600 hover:underline">Stories</a>
                    &rsaquo; {{ $story->steps->count() }} step{{ $story->steps->count() !== 1 ? 's' : '' }}
                    &bull; by {{ $story->creator?->name }}
                </p>
            </div>
            <a href="{{ route('stories.edit', $story) }}" class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 text-gray-700 text-sm rounded-md hover:bg-gray-50">Edit Story</a>
        </div>
    </x-slot>

    @php
        $stepsData = $story->steps->mapWithKeys(fn ($s) => [
            (string) $s->id => [
                'id'             => $s->id,
                'prompt_name'    => $s->prompt->name,
                'version_number' => $s->version->version_number,
                'prompt_content' => $s->version->content,
                'has_response'   => (bool) $s->libraryEntry,
                'response_text'  => $s->libraryEntry?->response_text,
                'provider'       => $s->libraryEntry?->provider?->name ?? ($s->libraryEntry ? 'Custom' : null),
                'model_used'     => $s->libraryEntry?->model_used,
                'rating'         => $s->libraryEntry?->rating,
            ]
        ]);
        $grouped = $story->steps->groupBy('prompt_id');
    @endphp

    <div x-data="storyShow(@js($stepsData))">

        <div class="py-12">
            <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">

                @if($story->description)
                <p class="text-gray-600 mb-6">{{ $story->description }}</p>
                @endif

                @if($story->steps->isEmpty())
                <div class="bg-white shadow-sm sm:rounded-lg p-10 text-center text-gray-400">
                    <p>No steps yet.</p>
                    <a href="{{ route('stories.edit', $story) }}" class="mt-3 inline-flex items-center text-sm text-indigo-600 hover:underline">Add steps →</a>
                </div>
                @else
                <div class="space-y-4">
                    @foreach($grouped as $promptId => $steps)
                    @php $first = $steps->first(); $responseCount = $steps->filter(fn($s) => $s->libraryEntry)->count(); @endphp
                    <div class="bg-white shadow-sm sm:rounded-lg overflow-hidden" x-data="{ open: false }">

                        {{-- Prompt group header --}}
                        <button type="button" @click="open = !open"
                                class="w-full flex items-center gap-4 px-6 py-4 text-left hover:bg-gray-50 transition-colors">
                            <div class="flex-1 min-w-0">
                                <span class="font-semibold text-gray-800">{{ $first->prompt->name }}</span>
                                <span class="ml-2 text-xs text-gray-400">
                                    {{ $responseCount }} response{{ $responseCount !== 1 ? 's' : '' }}
                                    @if($steps->count() > $responseCount)
                                    &bull; {{ $steps->count() - $responseCount }} without response
                                    @endif
                                </span>
                            </div>
                            <div class="flex gap-1 flex-wrap">
                                @foreach($steps->filter(fn($s) => $s->libraryEntry) as $s)
                                <span class="inline-flex items-center px-1.5 py-0.5 rounded text-xs bg-blue-50 text-blue-600 border border-blue-200">
                                    {{ $s->libraryEntry->model_used }}
                                </span>
                                @endforeach
                            </div>
                            <svg x-bind:class="open ? 'rotate-90' : ''"
                                 class="flex-shrink-0 w-4 h-4 text-gray-400 transition-transform duration-150"
                                 fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
                            </svg>
                        </button>

                        {{-- Results list --}}
                        <div x-show="open" x-cloak>
                            @foreach($steps as $step)
                            <div class="border-t border-gray-100 px-6 py-4" x-data="{ expanded: false, copied: false }">

                                <div class="flex items-center gap-3">
                                    {{-- Comparison checkbox --}}
                                    @if($step->libraryEntry)
                                    <input type="checkbox"
                                           :checked="selected.includes({{ $step->id }})"
                                           @change="toggle({{ $step->id }})"
                                           :disabled="selected.length >= 2 && !selected.includes({{ $step->id }})"
                                           class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500 cursor-pointer disabled:opacity-40 disabled:cursor-not-allowed flex-shrink-0">
                                    @else
                                    <span class="w-4 flex-shrink-0"></span>
                                    @endif

                                    {{-- Row info + expand toggle --}}
                                    <button type="button" @click="expanded = !expanded"
                                            class="flex-1 flex items-center gap-2 text-left min-w-0">
                                        <span class="text-xs font-mono bg-gray-100 text-gray-600 px-1.5 py-0.5 rounded flex-shrink-0">v{{ $step->version->version_number }}</span>
                                        @if($step->libraryEntry)
                                        <span class="inline-flex items-center px-1.5 py-0.5 rounded text-xs bg-blue-50 text-blue-700 border border-blue-200 flex-shrink-0">
                                            {{ $step->libraryEntry->provider?->name ?? 'Custom' }} &mdash; {{ $step->libraryEntry->model_used }}
                                        </span>
                                        @if($step->libraryEntry->rating)
                                        <span class="text-xs text-yellow-500 flex-shrink-0">{{ str_repeat('★', $step->libraryEntry->rating) }}<span class="text-gray-200">{{ str_repeat('★', 5 - $step->libraryEntry->rating) }}</span></span>
                                        @endif
                                        @else
                                        <span class="text-xs text-gray-400 italic">No response</span>
                                        @endif
                                        @if($step->notes)
                                        <span class="text-xs text-gray-400 truncate">— {{ $step->notes }}</span>
                                        @endif
                                    </button>

                                    <svg x-bind:class="expanded ? 'rotate-90' : ''"
                                         class="flex-shrink-0 w-4 h-4 text-gray-400 transition-transform duration-150"
                                         fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
                                    </svg>
                                </div>

                                {{-- Expanded: prompt + response --}}
                                <div x-show="expanded" x-cloak class="mt-4 space-y-4 ml-7">

                                    <div>
                                        <p class="text-xs text-gray-400 uppercase tracking-wide font-medium mb-2">Prompt</p>
                                        <div class="relative">
                                            <pre x-ref="promptContent" class="bg-gray-50 border border-gray-200 rounded p-4 pr-24 text-sm text-gray-800 whitespace-pre-wrap font-mono overflow-auto max-h-72 leading-relaxed">{{ $step->version->content }}</pre>
                                            <button
                                                @click.stop="navigator.clipboard.writeText($refs.promptContent.textContent.trim()); copied = true; setTimeout(() => copied = false, 2000)"
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

                                    @if($step->libraryEntry)
                                    <div>
                                        <div class="flex items-center gap-3 mb-2">
                                            <p class="text-xs text-gray-400 uppercase tracking-wide font-medium">Response</p>
                                            <span class="text-xs text-gray-500">{{ $step->libraryEntry->provider?->name ?? 'Custom' }} &mdash; <span class="font-mono">{{ $step->libraryEntry->model_used }}</span></span>
                                            @if($step->libraryEntry->rating)
                                            <span class="text-xs text-yellow-500">{{ str_repeat('★', $step->libraryEntry->rating) }}<span class="text-gray-200">{{ str_repeat('★', 5 - $step->libraryEntry->rating) }}</span></span>
                                            @endif
                                        </div>
                                        <pre class="bg-gray-50 border border-gray-200 rounded p-4 text-sm text-gray-800 whitespace-pre-wrap font-mono overflow-auto max-h-72 leading-relaxed">{{ $step->libraryEntry->response_text }}</pre>
                                        @if($step->libraryEntry->notes)
                                        <p class="mt-2 text-xs text-gray-400 italic">{{ $step->libraryEntry->notes }}</p>
                                        @endif
                                    </div>
                                    @endif

                                    @if($step->notes)
                                    <div>
                                        <p class="text-xs text-gray-400 uppercase tracking-wide font-medium mb-1">Notes</p>
                                        <p class="text-sm text-gray-700 whitespace-pre-wrap">{{ $step->notes }}</p>
                                    </div>
                                    @endif

                                </div>
                            </div>
                            @endforeach
                        </div>

                    </div>
                    @endforeach
                </div>
                @endif

            </div>
        </div>

        {{-- Sticky compare bar --}}
        <div x-show="selected.length > 0" x-cloak
             class="fixed bottom-0 inset-x-0 z-40 bg-indigo-600 text-white px-6 py-3 flex items-center justify-between shadow-lg">
            <span class="text-sm font-medium">
                <span x-text="selected.length"></span> result<span x-show="selected.length !== 1">s</span> selected
                <span x-show="selected.length < 2" class="text-indigo-300 font-normal"> — pick one more to compare</span>
            </span>
            <div class="flex items-center gap-3">
                <button @click="selected = []" class="text-sm text-indigo-200 hover:text-white transition-colors">Clear</button>
                <button @click="compareOpen = true"
                        x-show="selected.length === 2"
                        class="text-sm bg-white text-indigo-600 font-medium px-4 py-1.5 rounded-md hover:bg-indigo-50 transition-colors">
                    Compare side-by-side →
                </button>
            </div>
        </div>

        {{-- Compare modal --}}
        <div x-show="compareOpen" x-cloak
             class="fixed inset-0 z-50 overflow-y-auto"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0">

            <div class="fixed inset-0 bg-black/50" @click="compareOpen = false"></div>

            <div class="relative min-h-screen flex items-start justify-center p-4 pt-10 pb-16">
                <div class="relative bg-white rounded-lg shadow-xl w-full max-w-6xl">

                    {{-- Modal header --}}
                    <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200">
                        <h3 class="font-semibold text-gray-800">Compare Results</h3>
                        <div class="flex items-center gap-3">
                            <button @click="toggleLayout()"
                                    class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium border rounded-md transition"
                                    :class="isSideBySide ? 'bg-indigo-50 border-indigo-300 text-indigo-700' : 'bg-white border-gray-300 text-gray-600 hover:border-gray-400'">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 3H5a2 2 0 00-2 2v14a2 2 0 002 2h4M15 3h4a2 2 0 012 2v14a2 2 0 01-2 2h-4M9 3v18M15 3v18"/>
                                </svg>
                                <span x-text="isSideBySide ? 'Side by side' : 'Stacked'"></span>
                            </button>
                            <button @click="compareOpen = false" class="p-1 text-gray-400 hover:text-gray-600">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                                </svg>
                            </button>
                        </div>
                    </div>

                    {{-- Comparison panels --}}
                    <div class="p-6">
                        <div :class="isSideBySide ? 'grid grid-cols-1 lg:grid-cols-2 gap-4' : 'space-y-4'">
                            <template x-for="item in compareItems" :key="item.id">
                                <div class="border border-gray-200 rounded-lg overflow-hidden" x-data="{ promptOpen: false, copied: false }">

                                    {{-- Panel header --}}
                                    <div class="flex items-center gap-2 px-4 py-3 bg-gray-50 border-b border-gray-200 flex-wrap">
                                        <span class="text-xs font-mono bg-white border border-gray-200 text-gray-600 px-1.5 py-0.5 rounded" x-text="'v' + item.version_number"></span>
                                        <span class="text-sm font-medium text-gray-800 truncate" x-text="item.prompt_name"></span>
                                        <template x-if="item.provider">
                                            <span class="inline-flex items-center px-1.5 py-0.5 rounded text-xs bg-blue-50 text-blue-700 border border-blue-200" x-text="item.provider + ' — ' + item.model_used"></span>
                                        </template>
                                        <template x-if="item.rating">
                                            <span class="text-xs text-yellow-500" x-text="'★'.repeat(item.rating) + '☆'.repeat(5 - item.rating)"></span>
                                        </template>
                                    </div>

                                    {{-- Prompt (collapsible) --}}
                                    <div class="px-4 pt-4">
                                        <button @click="promptOpen = !promptOpen"
                                                class="flex items-center gap-1.5 text-xs text-gray-400 uppercase tracking-wide font-medium mb-2 hover:text-gray-600 transition-colors">
                                            <svg x-bind:class="promptOpen ? 'rotate-90' : ''"
                                                 class="w-3 h-3 transition-transform duration-150"
                                                 fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
                                            </svg>
                                            Prompt
                                        </button>
                                        <div x-show="promptOpen" x-cloak class="mb-3">
                                            <pre class="bg-gray-50 border border-gray-200 rounded p-3 text-xs text-gray-700 whitespace-pre-wrap font-mono overflow-auto max-h-48 leading-relaxed" x-text="item.prompt_content"></pre>
                                        </div>
                                    </div>

                                    {{-- Response --}}
                                    <div class="px-4 pb-4">
                                        <div class="flex items-center gap-2 mb-2">
                                            <p class="text-xs text-gray-400 uppercase tracking-wide font-medium">Response</p>
                                            <template x-if="item.has_response">
                                                <button @click="navigator.clipboard.writeText(item.response_text); copied = true; setTimeout(() => copied = false, 2000)"
                                                        class="inline-flex items-center gap-1 px-2 py-0.5 text-xs font-medium rounded border transition"
                                                        :class="copied ? 'bg-green-50 border-green-300 text-green-700' : 'bg-white border-gray-200 text-gray-400 hover:text-gray-600 hover:border-gray-300'">
                                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                                        <rect x="9" y="9" width="13" height="13" rx="2" stroke-linecap="round"/>
                                                        <path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1" stroke-linecap="round"/>
                                                    </svg>
                                                    <span x-text="copied ? 'Copied!' : 'Copy'"></span>
                                                </button>
                                            </template>
                                        </div>
                                        <template x-if="item.has_response">
                                            <pre class="bg-gray-50 border border-gray-200 rounded p-3 text-sm text-gray-800 whitespace-pre-wrap font-mono overflow-auto max-h-96 leading-relaxed" x-text="item.response_text"></pre>
                                        </template>
                                        <template x-if="!item.has_response">
                                            <p class="text-sm text-gray-400 italic">No response recorded.</p>
                                        </template>
                                    </div>

                                </div>
                            </template>
                        </div>
                    </div>

                </div>
            </div>
        </div>

    </div>

    <script>
    function storyShow(stepsData) {
        return {
            stepsData,
            selected: [],
            compareOpen: false,
            layout: 'sidebyside',
            get compareItems() {
                return this.selected.map(id => this.stepsData[id]).filter(Boolean);
            },
            toggle(id) {
                if (this.selected.includes(id)) {
                    this.selected = this.selected.filter(x => x !== id);
                } else if (this.selected.length < 2) {
                    this.selected.push(id);
                }
            },
            get isSideBySide() { return this.layout === 'sidebyside'; },
            toggleLayout() { this.layout = this.isSideBySide ? 'stacked' : 'sidebyside'; },
        };
    }
    </script>
</x-app-layout>
