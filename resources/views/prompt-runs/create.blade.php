<x-app-layout>
    <x-slot name="header">
        <div>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Run Prompt</h2>
            <p class="text-sm text-gray-500 mt-0.5">
                <a href="{{ route('prompts.show', $prompt) }}" class="text-indigo-600 hover:underline">{{ $prompt->name }}</a>
                &rsaquo; v{{ $prompt->activeVersion->version_number }}
            </p>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-6">

            @if($providers->isEmpty())
            <div class="bg-yellow-50 border border-yellow-200 text-yellow-800 px-4 py-4 rounded-lg">
                <p class="font-medium">No LLM providers are enabled.</p>
                @if(auth()->user()->isAdmin())
                <p class="text-sm mt-1">Go to <a href="{{ route('admin.llm-providers.index') }}" class="underline">LLM Providers</a> to configure and enable at least one.</p>
                @else
                <p class="text-sm mt-1">Ask an admin to enable at least one provider under Admin → LLM Providers.</p>
                @endif
            </div>
            @else

            <form method="POST" action="{{ route('prompt-runs.store', $prompt) }}" x-data="{ submitting: false }" @submit="submitting = true">
                @csrf

                {{-- Variables --}}
                @if($prompt->activeVersion->variables && count($prompt->activeVersion->variables))
                <div class="bg-white shadow-sm sm:rounded-lg p-6">
                    <h3 class="font-semibold text-gray-800 mb-4">Variables</h3>
                    <div class="space-y-4">
                        @foreach($prompt->activeVersion->variables as $var)
                        <div>
                            <label for="var_{{ $var }}" class="block text-sm font-medium text-gray-700 mb-1">
                                <span class="font-mono bg-indigo-50 text-indigo-700 px-1.5 py-0.5 rounded border border-indigo-200 text-xs">&#123;&#123;{{ $var }}&#125;&#125;</span>
                            </label>
                            <input type="text"
                                   name="variables[{{ $var }}]"
                                   id="var_{{ $var }}"
                                   value="{{ old('variables.' . $var) }}"
                                   class="w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 text-sm">
                        </div>
                        @endforeach
                    </div>
                    <p class="mt-3 text-xs text-gray-400">Leave a variable blank to keep the <code class="font-mono">&#123;&#123;placeholder&#125;&#125;</code> in the rendered prompt.</p>
                </div>
                @else
                <div class="bg-white shadow-sm sm:rounded-lg p-4 text-sm text-gray-500">
                    This prompt has no variables — the content will be sent as-is.
                </div>
                @endif

                {{-- Prompt preview --}}
                <div class="bg-white shadow-sm sm:rounded-lg p-6" x-data="{ open: false }">
                    <button type="button" @click="open = !open" class="flex items-center gap-2 text-sm font-medium text-gray-600 hover:text-gray-800">
                        <svg x-bind:class="open ? 'rotate-90' : ''" class="w-4 h-4 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                        </svg>
                        Preview prompt content (v{{ $prompt->activeVersion->version_number }})
                    </button>
                    <div x-show="open" x-cloak class="mt-3">
                        <pre class="bg-gray-50 border border-gray-200 rounded p-3 text-xs text-gray-700 whitespace-pre-wrap font-mono max-h-48 overflow-auto">{{ $prompt->activeVersion->content }}</pre>
                    </div>
                </div>

                {{-- Provider selection --}}
                <div class="bg-white shadow-sm sm:rounded-lg p-6">
                    <h3 class="font-semibold text-gray-800 mb-4">Send to LLMs</h3>
                    <div class="space-y-3">
                        @foreach($providers as $provider)
                        <label class="flex items-center gap-3 p-3 rounded-lg border border-gray-200 hover:bg-gray-50 cursor-pointer">
                            <input type="checkbox"
                                   name="providers[]"
                                   value="{{ $provider->id }}"
                                   checked
                                   class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                            <div>
                                <span class="font-medium text-gray-800 text-sm">{{ $provider->name }}</span>
                                <span class="ml-2 text-xs text-gray-400 font-mono">{{ $provider->model }}</span>
                            </div>
                        </label>
                        @endforeach
                    </div>
                    @error('providers')
                    <p class="mt-2 text-sm text-red-500">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Auto-save to Library --}}
                <div class="bg-white shadow-sm sm:rounded-lg p-6">
                    <label class="flex items-center gap-3 cursor-pointer">
                        <input type="hidden" name="save_to_library" value="0">
                        <input type="checkbox"
                               name="save_to_library"
                               value="1"
                               checked
                               class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                        <div>
                            <span class="font-medium text-gray-800 text-sm">Save successful responses to Library</span>
                            <p class="text-xs text-gray-400 mt-0.5">Automatically add each successful response as a library entry.</p>
                        </div>
                    </label>
                </div>

                <div class="flex justify-end gap-3">
                    <a href="{{ route('prompts.show', $prompt) }}" class="px-4 py-2 text-sm text-gray-700 border border-gray-300 rounded-md hover:bg-gray-50">Cancel</a>
                    <button type="submit"
                            x-bind:disabled="submitting"
                            class="inline-flex items-center gap-2 px-6 py-2 bg-indigo-600 text-white text-sm rounded-md hover:bg-indigo-700 disabled:opacity-60 disabled:cursor-not-allowed">
                        <svg x-show="submitting" class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                        </svg>
                        <span x-text="submitting ? 'Running…' : 'Run Prompt'"></span>
                    </button>
                </div>
            </form>

            @endif
        </div>
    </div>
</x-app-layout>
