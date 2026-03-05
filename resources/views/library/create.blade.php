<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Add Library Entry</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6"
                 x-data="libraryForm({{ Js::from($promptVersionMap) }}, {{ Js::from($defaults) }})">

                <form method="POST" action="{{ route('library.store') }}">
                    @csrf

                    {{-- Prompt selection --}}
                    <div class="mb-5">
                        <label for="prompt_id" class="block text-sm font-medium text-gray-700 mb-1">Prompt <span class="text-red-500">*</span></label>
                        <select name="prompt_id" id="prompt_id"
                                x-model="selectedPromptId"
                                @change="onPromptChange()"
                                required
                                class="w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 @error('prompt_id') border-red-300 @enderror">
                            <option value="">Select a prompt…</option>
                            @foreach($prompts as $p)
                            <option value="{{ $p->id }}">{{ $p->name }}</option>
                            @endforeach
                        </select>
                        @error('prompt_id')<p class="mt-1 text-sm text-red-500">{{ $message }}</p>@enderror
                    </div>

                    {{-- Version selection (dynamic) --}}
                    <div class="mb-5">
                        <label for="prompt_version_id" class="block text-sm font-medium text-gray-700 mb-1">Version <span class="text-red-500">*</span></label>
                        <select name="prompt_version_id" id="prompt_version_id"
                                x-model="selectedVersionId"
                                required
                                :disabled="!selectedPromptId"
                                class="w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 @error('prompt_version_id') border-red-300 @enderror">
                            <option value="">Select a version…</option>
                            <template x-for="v in versions" :key="v.id">
                                <option :value="v.id" x-text="'v' + v.version_number + (v.commit_message ? ' — ' + v.commit_message : '')"></option>
                            </template>
                        </select>
                        @error('prompt_version_id')<p class="mt-1 text-sm text-red-500">{{ $message }}</p>@enderror
                    </div>

                    {{-- Provider (optional) --}}
                    <div class="mb-5">
                        <label for="llm_provider_id" class="block text-sm font-medium text-gray-700 mb-1">LLM Provider</label>
                        <select name="llm_provider_id" id="llm_provider_id"
                                x-model="selectedProviderId"
                                @change="onProviderChange()"
                                class="w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                            <option value="">Custom / Unknown</option>
                            @foreach($providers as $prov)
                            <option value="{{ $prov->id }}" data-model="{{ $prov->model }}">{{ $prov->name }}</option>
                            @endforeach
                        </select>
                        @error('llm_provider_id')<p class="mt-1 text-sm text-red-500">{{ $message }}</p>@enderror
                    </div>

                    {{-- Model used --}}
                    <div class="mb-5">
                        <label for="model_used" class="block text-sm font-medium text-gray-700 mb-1">Model <span class="text-red-500">*</span></label>
                        <input type="text" name="model_used" id="model_used"
                               x-model="modelUsed"
                               required placeholder="e.g. gpt-4o-mini"
                               class="w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 font-mono text-sm @error('model_used') border-red-300 @enderror">
                        @error('model_used')<p class="mt-1 text-sm text-red-500">{{ $message }}</p>@enderror
                    </div>

                    {{-- Response text --}}
                    <div class="mb-5">
                        <label for="response_text" class="block text-sm font-medium text-gray-700 mb-1">Response <span class="text-red-500">*</span></label>
                        <textarea name="response_text" id="response_text" rows="10" required
                                  class="w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 font-mono text-sm @error('response_text') border-red-300 @enderror">{{ old('response_text', $defaults['response_text'] ?? '') }}</textarea>
                        @error('response_text')<p class="mt-1 text-sm text-red-500">{{ $message }}</p>@enderror
                    </div>

                    {{-- Rating --}}
                    <div class="mb-5" x-data="{ rating: {{ old('rating', $defaults['rating'] ?? 0) }} }">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Rating</label>
                        <input type="hidden" name="rating" :value="rating || ''">
                        <div class="flex items-center gap-1">
                            @for($i = 1; $i <= 5; $i++)
                            <button type="button"
                                    @click="rating = (rating === {{ $i }} ? 0 : {{ $i }})"
                                    class="text-2xl leading-none focus:outline-none transition-colors"
                                    :class="rating >= {{ $i }} ? 'text-yellow-400' : 'text-gray-200 hover:text-yellow-300'"
                                    title="{{ $i }} star{{ $i > 1 ? 's' : '' }}">&#9733;</button>
                            @endfor
                            <span x-show="rating > 0" class="ml-2 text-sm text-gray-500" x-text="rating + '/5'"></span>
                            <button type="button" x-show="rating > 0" @click="rating = 0"
                                    class="ml-2 text-xs text-gray-400 hover:text-gray-600">Clear</button>
                        </div>
                        @error('rating')<p class="mt-1 text-sm text-red-500">{{ $message }}</p>@enderror
                    </div>

                    {{-- Notes --}}
                    <div class="mb-6">
                        <label for="notes" class="block text-sm font-medium text-gray-700 mb-1">Notes</label>
                        <textarea name="notes" id="notes" rows="3"
                                  placeholder="Any observations, context, or follow-up thoughts…"
                                  class="w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 text-sm">{{ old('notes') }}</textarea>
                        @error('notes')<p class="mt-1 text-sm text-red-500">{{ $message }}</p>@enderror
                    </div>

                    <div class="flex justify-end gap-3">
                        <a href="{{ route('library.index') }}" class="px-4 py-2 text-sm text-gray-700 border border-gray-300 rounded-md hover:bg-gray-50">Cancel</a>
                        <button type="submit" class="px-4 py-2 text-sm bg-indigo-600 text-white rounded-md hover:bg-indigo-700">Save to Library</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
    function libraryForm(promptVersionMap, defaults) {
        return {
            promptVersionMap,
            selectedPromptId: defaults.prompt_id ? String(defaults.prompt_id) : '{{ old("prompt_id", "") }}',
            selectedVersionId: defaults.prompt_version_id ? String(defaults.prompt_version_id) : '{{ old("prompt_version_id", "") }}',
            selectedProviderId: defaults.llm_provider_id ? String(defaults.llm_provider_id) : '{{ old("llm_provider_id", "") }}',
            modelUsed: defaults.model_used || '{{ old("model_used", "") }}',
            get versions() {
                return this.promptVersionMap[this.selectedPromptId] || [];
            },
            onPromptChange() {
                const vs = this.versions;
                this.selectedVersionId = vs.length === 1 ? String(vs[0].id) : '';
            },
            onProviderChange() {
                if (!this.selectedProviderId) return;
                const opt = document.querySelector(`#llm_provider_id option[value="${this.selectedProviderId}"]`);
                if (opt && opt.dataset.model && !this.modelUsed) {
                    this.modelUsed = opt.dataset.model;
                }
            },
            init() {
                // If pre-filled from query params, sync versions list
                if (this.selectedPromptId && !this.selectedVersionId) {
                    this.onPromptChange();
                }
            }
        };
    }
    </script>
</x-app-layout>
