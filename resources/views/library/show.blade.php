<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <div>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">Library Entry</h2>
                <p class="text-sm text-gray-500 mt-0.5">
                    <a href="{{ route('library.index') }}" class="text-indigo-600 hover:underline">Library</a>
                    &rsaquo; #{{ $entry->id }}
                </p>
            </div>
            <div class="flex gap-2">
                <a href="{{ route('library.edit', $entry) }}" class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 text-gray-700 text-sm rounded-md hover:bg-gray-50">Edit</a>
                <form method="POST" action="{{ route('library.destroy', $entry) }}" onsubmit="return confirm('Delete this library entry?')">
                    @csrf @method('DELETE')
                    <button type="submit" class="inline-flex items-center px-4 py-2 bg-red-600 text-white text-sm rounded-md hover:bg-red-700">Delete</button>
                </form>
            </div>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-6">

            @if(session('success'))
            <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded">{{ session('success') }}</div>
            @endif

            {{-- Metadata --}}
            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                <div class="grid grid-cols-2 gap-x-8 gap-y-3 text-sm">
                    <div>
                        <p class="text-xs text-gray-400 uppercase tracking-wide mb-0.5">Prompt</p>
                        <a href="{{ route('prompts.show', $entry->prompt) }}" class="text-indigo-600 font-medium hover:underline">{{ $entry->prompt->name }}</a>
                        <span class="ml-2 text-xs text-gray-400 font-mono">{{ $entry->prompt->slug }}</span>
                    </div>
                    <div>
                        <p class="text-xs text-gray-400 uppercase tracking-wide mb-0.5">Version</p>
                        <a href="{{ route('prompts.versions.show', [$entry->prompt, $entry->version->version_number]) }}"
                           class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-700 hover:bg-gray-200">
                            v{{ $entry->version->version_number }}
                        </a>
                        @if($entry->version->commit_message)
                        <span class="ml-1 text-gray-500">— {{ $entry->version->commit_message }}</span>
                        @endif
                    </div>
                    <div>
                        <p class="text-xs text-gray-400 uppercase tracking-wide mb-0.5">Provider</p>
                        @if($entry->provider)
                        <span class="text-gray-800">{{ $entry->provider->name }}</span>
                        @else
                        <span class="text-gray-400 italic">Custom</span>
                        @endif
                    </div>
                    <div>
                        <p class="text-xs text-gray-400 uppercase tracking-wide mb-0.5">Model</p>
                        <span class="font-mono text-gray-700">{{ $entry->model_used }}</span>
                    </div>
                    <div>
                        <p class="text-xs text-gray-400 uppercase tracking-wide mb-0.5">Rating</p>
                        @if($entry->rating)
                        <span class="text-yellow-500 text-lg">{{ str_repeat('★', $entry->rating) }}<span class="text-gray-200">{{ str_repeat('★', 5 - $entry->rating) }}</span></span>
                        <span class="ml-1 text-xs text-gray-400">{{ $entry->rating }}/5</span>
                        @else
                        <span class="text-gray-400">Not rated</span>
                        @endif
                    </div>
                    <div>
                        <p class="text-xs text-gray-400 uppercase tracking-wide mb-0.5">Added by</p>
                        <span class="text-gray-700">{{ $entry->creator?->name }}</span>
                        <span class="text-gray-400"> &bull; {{ $entry->created_at->format('Y-m-d H:i') }}</span>
                    </div>
                </div>

                @if($entry->notes)
                <div class="mt-4 pt-4 border-t border-gray-100">
                    <p class="text-xs text-gray-400 uppercase tracking-wide mb-1">Notes</p>
                    <p class="text-sm text-gray-700 whitespace-pre-wrap">{{ $entry->notes }}</p>
                </div>
                @endif
            </div>

            {{-- Response text --}}
            <div class="bg-white shadow-sm sm:rounded-lg p-6" x-data="{ copied: false }">
                <div class="flex items-center justify-between mb-3">
                    <h3 class="font-semibold text-gray-800">Response</h3>
                    <div class="flex items-center gap-2">
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
                        <a href="{{ route('library.export', $entry) }}"
                           class="inline-flex items-center gap-1 text-xs text-gray-500 hover:text-gray-700 px-2.5 py-1.5 border border-gray-300 rounded">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                            </svg>
                            Export .md
                        </a>
                    </div>
                </div>
                <pre x-ref="responseText" class="bg-gray-50 border border-gray-200 rounded p-4 text-sm text-gray-800 whitespace-pre-wrap font-mono overflow-auto max-h-[32rem] leading-relaxed">{{ $entry->response_text }}</pre>
            </div>

        </div>
    </div>
</x-app-layout>
