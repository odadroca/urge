<x-app-layout>
    <x-slot name="header">
        <div>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Run Results</h2>
            <p class="text-sm text-gray-500 mt-0.5">
                <a href="{{ route('prompts.show', $prompt) }}" class="text-indigo-600 hover:underline">{{ $prompt->name }}</a>
                &rsaquo; <a href="{{ route('prompt-runs.index', $prompt) }}" class="text-indigo-600 hover:underline">Runs</a>
                &rsaquo; #{{ $run->id }}
            </p>
        </div>
    </x-slot>

    <div class="py-12" x-data="{ compareIds: [], showCompare: false }">
        <div class="max-w-6xl mx-auto sm:px-6 lg:px-8 space-y-6">

            @if(session('success'))
            <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded">{{ session('success') }}</div>
            @endif

            {{-- Rendered prompt --}}
            <div class="bg-white shadow-sm sm:rounded-lg p-6" x-data="{ open: false }">
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="font-semibold text-gray-800">Rendered Prompt</h3>
                        <p class="text-xs text-gray-400 mt-0.5">
                            v{{ $run->version->version_number }}
                            &bull; {{ $run->created_at->format('Y-m-d H:i') }}
                            &bull; by {{ $run->creator?->name }}
                        </p>
                    </div>
                    <button type="button" @click="open = !open" class="text-sm text-indigo-600 hover:underline">
                        <span x-text="open ? 'Hide' : 'Show'">Show</span>
                    </button>
                </div>
                @if($run->variables_used && count($run->variables_used))
                <div class="mt-3 flex flex-wrap gap-1.5">
                    @foreach($run->variables_used as $key => $val)
                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-xs bg-indigo-50 text-indigo-700 border border-indigo-200">
                        <span class="font-mono">{{ $key }}</span><span class="text-indigo-400">=</span><span class="truncate max-w-[12rem]">{{ $val }}</span>
                    </span>
                    @endforeach
                </div>
                @endif
                <div x-show="open" x-cloak class="mt-3">
                    <pre class="bg-gray-50 border border-gray-200 rounded p-3 text-sm text-gray-800 whitespace-pre-wrap font-mono max-h-60 overflow-auto">{{ $run->rendered_content }}</pre>
                </div>
            </div>

            {{-- Response cards --}}
            @if($run->responses->isEmpty())
            <div class="bg-white shadow-sm sm:rounded-lg p-8 text-center text-gray-400">No responses recorded for this run.</div>
            @else
            @if($run->responses->count() > 1)
            {{-- Compare bar --}}
            <div x-show="compareIds.length > 0" x-cloak
                 class="bg-indigo-50 border border-indigo-200 rounded-lg px-4 py-3 flex items-center justify-between">
                <span class="text-sm text-indigo-700 font-medium">
                    <span x-text="compareIds.length"></span> response<span x-show="compareIds.length !== 1">s</span> selected for comparison
                </span>
                <div class="flex items-center gap-3">
                    <button @click="compareIds = []" class="text-xs text-indigo-500 hover:text-indigo-700">Clear</button>
                    <button @click="showCompare = true"
                            x-show="compareIds.length >= 2"
                            class="inline-flex items-center gap-1.5 px-4 py-1.5 text-sm bg-indigo-600 text-white font-medium rounded-md hover:bg-indigo-700 transition">
                        Compare Selected
                    </button>
                </div>
            </div>
            @endif

            <div class="grid grid-cols-1 {{ $run->responses->count() > 1 ? 'lg:grid-cols-2' : '' }} gap-6">
                @foreach($run->responses as $response)
                <div class="bg-white shadow-sm sm:rounded-lg p-6 flex flex-col"
                     x-data="{ rating: {{ $response->rating ?? 0 }}, saving: false }"
                     id="response-{{ $response->id }}"
                     :class="compareIds.includes({{ $response->id }}) ? 'ring-2 ring-indigo-300' : ''">

                    {{-- Card header --}}
                    <div class="flex items-start justify-between mb-3">
                        <div class="flex items-center gap-2">
                            @if($run->responses->count() > 1)
                            <input type="checkbox"
                                   value="{{ $response->id }}"
                                   x-model.number="compareIds"
                                   :disabled="compareIds.length >= 4 && !compareIds.includes({{ $response->id }})"
                                   class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500 disabled:opacity-30 cursor-pointer disabled:cursor-not-allowed">
                            @endif
                            <span class="font-semibold text-gray-800">{{ $response->provider->name }}</span>
                            <span class="text-xs text-gray-400 font-mono">{{ $response->model_used }}</span>
                        </div>
                        @if($response->isSuccess())
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-700">success</span>
                        @else
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-700">error</span>
                        @endif
                    </div>

                    {{-- Meta --}}
                    <div class="flex flex-wrap gap-4 text-xs text-gray-400 mb-3">
                        @if($response->duration_ms)
                        <span>{{ number_format($response->duration_ms / 1000, 2) }}s</span>
                        @endif
                        @if($response->input_tokens || $response->output_tokens)
                        <span>{{ $response->input_tokens ?? '?' }} in / {{ $response->output_tokens ?? '?' }} out tokens</span>
                        @endif
                    </div>

                    {{-- Response body --}}
                    <div class="flex-1 mb-4">
                        @if($response->isSuccess())
                        <pre class="bg-gray-50 border border-gray-200 rounded p-3 text-sm text-gray-800 whitespace-pre-wrap font-mono overflow-auto max-h-96 leading-relaxed">{{ $response->response_text }}</pre>
                        @else
                        <div class="bg-red-50 border border-red-200 rounded p-3 text-sm text-red-700">
                            <strong>Error:</strong> {{ $response->error_message }}
                        </div>
                        @endif
                    </div>

                    {{-- Rating + export --}}
                    <div class="flex items-center justify-between pt-3 border-t border-gray-100">
                        {{-- Star rating --}}
                        <div class="flex items-center gap-1" x-data>
                            @for($i = 1; $i <= 5; $i++)
                            <button type="button"
                                    @click="
                                        saving = true;
                                        rating = {{ $i }};
                                        fetch('{{ route('llm-responses.rate', [$run, $response]) }}', {
                                            method: 'POST',
                                            headers: {
                                                'Content-Type': 'application/json',
                                                'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content
                                            },
                                            body: JSON.stringify({ rating: {{ $i }} })
                                        }).then(() => saving = false);
                                    "
                                    class="text-2xl leading-none focus:outline-none transition-colors"
                                    x-bind:class="rating >= {{ $i }} ? 'text-yellow-400' : 'text-gray-200 hover:text-yellow-300'"
                                    title="Rate {{ $i }} star{{ $i > 1 ? 's' : '' }}">&#9733;</button>
                            @endfor
                            <span x-show="saving" class="ml-1 text-xs text-gray-400">Saving…</span>
                            <span x-show="!saving && rating > 0" class="ml-1 text-xs text-gray-400" x-text="rating + '/5'"></span>
                        </div>

                        {{-- Export + Save to Library --}}
                        @if($response->isSuccess())
                        <div class="flex items-center gap-3">
                            <a href="{{ route('llm-responses.export', [$run, $response]) }}"
                               class="inline-flex items-center gap-1 text-xs text-gray-500 hover:text-gray-700">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                                </svg>
                                Export .md
                            </a>
                            <form method="POST" action="{{ route('library.store') }}">
                                @csrf
                                <input type="hidden" name="prompt_id" value="{{ $run->prompt_id }}">
                                <input type="hidden" name="prompt_version_id" value="{{ $run->prompt_version_id }}">
                                <input type="hidden" name="llm_provider_id" value="{{ $response->llm_provider_id }}">
                                <input type="hidden" name="model_used" value="{{ $response->model_used }}">
                                <input type="hidden" name="response_text" value="{{ $response->response_text }}">
                                @if($response->rating)
                                <input type="hidden" name="rating" value="{{ $response->rating }}">
                                @endif
                                <button type="submit"
                                        class="inline-flex items-center gap-1 text-xs text-indigo-600 hover:text-indigo-800">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 5a2 2 0 012-2h10a2 2 0 012 2v16l-7-3.5L5 21V5z"/>
                                    </svg>
                                    Save to Library
                                </button>
                            </form>
                        </div>
                        @endif
                    </div>
                </div>
                @endforeach
            </div>
            @endif

            <div class="flex gap-3">
                <a href="{{ route('prompt-runs.create', $prompt) }}" class="px-4 py-2 bg-indigo-600 text-white text-sm rounded-md hover:bg-indigo-700">New Run</a>
                <a href="{{ route('prompt-runs.index', $prompt) }}" class="px-4 py-2 bg-white border border-gray-300 text-gray-700 text-sm rounded-md hover:bg-gray-50">All Runs</a>
            </div>

            {{-- Compare modal --}}
            @if($run->responses->count() > 1)
            @php
                $responseData = $run->responses->mapWithKeys(function ($r) {
                    return [$r->id => [
                        'id' => $r->id,
                        'provider' => $r->provider->name,
                        'model' => $r->model_used,
                        'status' => $r->status,
                        'text' => $r->response_text,
                        'error' => $r->error_message,
                        'rating' => $r->rating,
                        'duration_ms' => $r->duration_ms,
                        'input_tokens' => $r->input_tokens,
                        'output_tokens' => $r->output_tokens,
                    ]];
                });
            @endphp
            <div x-show="showCompare" x-cloak
                 class="fixed inset-0 z-50 overflow-y-auto"
                 @keydown.escape.window="showCompare = false"
                 x-data="{ allResponses: {{ $responseData->toJson() }} }">
                {{-- Backdrop --}}
                <div class="fixed inset-0 bg-black/50" @click="showCompare = false"></div>

                {{-- Modal --}}
                <div class="relative min-h-screen flex items-start justify-center p-4 pt-8">
                    <div class="relative bg-white rounded-lg shadow-xl w-full max-w-7xl overflow-hidden" @click.stop>
                        {{-- Header --}}
                        <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between">
                            <h3 class="font-semibold text-gray-800 text-lg">Compare Responses</h3>
                            <button @click="showCompare = false" class="text-gray-400 hover:text-gray-600">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                                </svg>
                            </button>
                        </div>

                        {{-- Side-by-side comparison --}}
                        <div class="p-6 overflow-x-auto">
                            <div class="flex gap-4" :style="'min-width: ' + (compareIds.length * 350) + 'px'">
                                <template x-for="rid in compareIds" :key="rid">
                                    <div class="flex-1 min-w-[320px] border border-gray-200 rounded-lg overflow-hidden">
                                        {{-- Column header --}}
                                        <div class="px-4 py-3 bg-gray-50 border-b border-gray-200">
                                            <div class="flex items-center justify-between">
                                                <div>
                                                    <span class="font-semibold text-gray-800 text-sm" x-text="allResponses[rid]?.provider"></span>
                                                    <span class="text-xs text-gray-400 font-mono ml-1" x-text="allResponses[rid]?.model"></span>
                                                </div>
                                                <template x-if="allResponses[rid]?.rating">
                                                    <span class="text-yellow-500 text-sm" x-text="'★'.repeat(allResponses[rid].rating) + '☆'.repeat(5 - allResponses[rid].rating)"></span>
                                                </template>
                                            </div>
                                            <div class="flex gap-3 text-[10px] text-gray-400 mt-1">
                                                <template x-if="allResponses[rid]?.duration_ms">
                                                    <span x-text="(allResponses[rid].duration_ms / 1000).toFixed(2) + 's'"></span>
                                                </template>
                                                <template x-if="allResponses[rid]?.input_tokens || allResponses[rid]?.output_tokens">
                                                    <span x-text="(allResponses[rid].input_tokens || '?') + ' in / ' + (allResponses[rid].output_tokens || '?') + ' out'"></span>
                                                </template>
                                            </div>
                                        </div>

                                        {{-- Response body --}}
                                        <div class="p-4 max-h-[60vh] overflow-auto">
                                            <template x-if="allResponses[rid]?.status === 'success'">
                                                <pre class="text-sm font-mono text-gray-800 whitespace-pre-wrap break-words leading-relaxed" x-text="allResponses[rid].text"></pre>
                                            </template>
                                            <template x-if="allResponses[rid]?.status !== 'success'">
                                                <div class="bg-red-50 border border-red-200 rounded p-3 text-sm text-red-700">
                                                    <strong>Error:</strong> <span x-text="allResponses[rid]?.error"></span>
                                                </div>
                                            </template>
                                        </div>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            @endif
        </div>
    </div>
</x-app-layout>
