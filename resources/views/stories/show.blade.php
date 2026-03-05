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
                @foreach($story->steps as $i => $step)
                <div class="bg-white shadow-sm sm:rounded-lg overflow-hidden" x-data="{ open: false, copied: false }">

                    {{-- Collapsed header --}}
                    <button type="button"
                            @click="open = !open"
                            class="w-full flex items-start gap-4 px-6 py-4 text-left hover:bg-gray-50 transition-colors">

                        {{-- Step number --}}
                        <span class="flex-shrink-0 mt-0.5 w-7 h-7 rounded-full bg-indigo-100 text-indigo-700 text-xs font-bold flex items-center justify-center">{{ $i + 1 }}</span>

                        {{-- Summary --}}
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center gap-2 flex-wrap">
                                <span class="font-semibold text-gray-800">{{ $step->prompt->name }}</span>
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-600">v{{ $step->version->version_number }}</span>
                                @if($step->libraryEntry)
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-blue-50 text-blue-700 border border-blue-200">
                                    {{ $step->libraryEntry->provider?->name ?? 'Custom' }} &mdash; {{ $step->libraryEntry->model_used }}
                                    @if($step->libraryEntry->rating)
                                    &nbsp;&#9733; {{ $step->libraryEntry->rating }}/5
                                    @endif
                                </span>
                                @else
                                <span class="text-xs text-gray-400 italic">No response</span>
                                @endif
                            </div>
                            @if($step->notes)
                            <p class="mt-1 text-xs text-gray-500 truncate max-w-xl">{{ $step->notes }}</p>
                            @endif
                        </div>

                        {{-- Chevron --}}
                        <svg x-bind:class="open ? 'rotate-90' : ''"
                             class="flex-shrink-0 w-4 h-4 text-gray-400 transition-transform duration-150 mt-1"
                             fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
                        </svg>
                    </button>

                    {{-- Expanded body --}}
                    <div x-show="open" x-cloak class="border-t border-gray-100 px-6 py-5 space-y-5">

                        {{-- Prompt content --}}
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

                        {{-- Library response --}}
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

                        {{-- Step notes --}}
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
            @endif

        </div>
    </div>
</x-app-layout>
