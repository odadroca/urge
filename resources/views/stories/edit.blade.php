<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <div>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">Edit Story</h2>
                <p class="text-sm text-gray-500 mt-0.5">
                    <a href="{{ route('stories.index') }}" class="text-indigo-600 hover:underline">Stories</a>
                    &rsaquo; {{ $story->title }}
                </p>
            </div>
            <a href="{{ route('stories.show', $story) }}" class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 text-gray-700 text-sm rounded-md hover:bg-gray-50">
                View Story
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-6">

            @if(session('success'))
            <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded">{{ session('success') }}</div>
            @endif

            {{-- Title / description --}}
            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                <h3 class="font-semibold text-gray-800 mb-4">Story Details</h3>
                <form method="POST" action="{{ route('stories.update', $story) }}">
                    @csrf @method('PUT')
                    <div class="mb-4">
                        <label for="title" class="block text-sm font-medium text-gray-700 mb-1">Title <span class="text-red-500">*</span></label>
                        <input type="text" name="title" id="title" value="{{ old('title', $story->title) }}" required
                               class="w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 @error('title') border-red-300 @enderror">
                        @error('title')<p class="mt-1 text-sm text-red-500">{{ $message }}</p>@enderror
                    </div>
                    <div class="mb-4">
                        <label for="description" class="block text-sm font-medium text-gray-700 mb-1">Description</label>
                        <textarea name="description" id="description" rows="2"
                                  class="w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">{{ old('description', $story->description) }}</textarea>
                    </div>
                    <div class="flex justify-end gap-3">
                        <button type="submit" class="px-4 py-2 text-sm bg-indigo-600 text-white rounded-md hover:bg-indigo-700">Save Details</button>
                    </div>
                </form>
            </div>

            {{-- Steps list --}}
            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                <h3 class="font-semibold text-gray-800 mb-4">Steps <span class="text-gray-400 font-normal text-sm">({{ $story->steps->count() }})</span></h3>

                @if($story->steps->isEmpty())
                <p class="text-sm text-gray-400 italic mb-4">No steps yet — add one below.</p>
                @else
                <div class="divide-y divide-gray-100">
                    @foreach($story->steps as $i => $step)
                    <div class="py-3" x-data="{ editing: false }">
                        <div class="flex items-start gap-3">
                            {{-- Step number --}}
                            <span class="mt-0.5 flex-shrink-0 w-6 h-6 rounded-full bg-indigo-100 text-indigo-700 text-xs font-bold flex items-center justify-center">{{ $i + 1 }}</span>

                            {{-- Step info --}}
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center gap-2 flex-wrap">
                                    <span class="font-medium text-gray-800 text-sm">{{ $step->prompt->name }}</span>
                                    <span class="inline-flex items-center px-1.5 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-600">v{{ $step->version->version_number }}</span>
                                    @if($step->libraryEntry)
                                    <span class="inline-flex items-center px-1.5 py-0.5 rounded text-xs font-medium bg-blue-50 text-blue-700 border border-blue-200">
                                        {{ $step->libraryEntry->provider?->name ?? 'Custom' }} — {{ $step->libraryEntry->model_used }}
                                    </span>
                                    @else
                                    <span class="text-xs text-gray-400">No response</span>
                                    @endif
                                </div>
                                @if($step->notes)
                                <p class="mt-0.5 text-xs text-gray-400 truncate max-w-lg">{{ $step->notes }}</p>
                                @endif
                            </div>

                            {{-- Actions --}}
                            <div class="flex-shrink-0 flex items-center gap-1">
                                <button type="button" @click="editing = !editing" title="Edit step"
                                        :class="editing ? 'text-indigo-500' : 'text-gray-300 hover:text-indigo-400'"
                                        class="p-1">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.232 5.232l3.536 3.536M9 13l6.586-6.586a2 2 0 012.828 2.828L11.828 15.828a2 2 0 01-1.414.586H9v-2a2 2 0 01.586-1.414z"/>
                                    </svg>
                                </button>

                                @if(!$loop->first)
                                <form method="POST" action="{{ route('story-steps.move-up', [$story, $step]) }}">
                                    @csrf
                                    <button type="submit" title="Move up" class="p-1 text-gray-400 hover:text-gray-600">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 15l7-7 7 7"/>
                                        </svg>
                                    </button>
                                </form>
                                @else
                                <span class="w-6"></span>
                                @endif

                                @if(!$loop->last)
                                <form method="POST" action="{{ route('story-steps.move-down', [$story, $step]) }}">
                                    @csrf
                                    <button type="submit" title="Move down" class="p-1 text-gray-400 hover:text-gray-600">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/>
                                        </svg>
                                    </button>
                                </form>
                                @else
                                <span class="w-6"></span>
                                @endif

                                <form method="POST" action="{{ route('story-steps.destroy', [$story, $step]) }}"
                                      onsubmit="return confirm('Remove this step?')">
                                    @csrf @method('DELETE')
                                    <button type="submit" title="Remove" class="p-1 text-gray-300 hover:text-red-500">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                                        </svg>
                                    </button>
                                </form>
                            </div>
                        </div>

                        {{-- Inline edit form --}}
                        <div x-show="editing" x-cloak class="mt-3 ml-9">
                            <form method="POST" action="{{ route('story-steps.update', [$story, $step]) }}">
                                @csrf @method('PATCH')
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                    <div>
                                        <label class="block text-xs font-medium text-gray-600 mb-1">Library Response</label>
                                        <select name="library_entry_id"
                                                class="w-full border-gray-300 rounded-md shadow-sm text-sm focus:ring-indigo-500 focus:border-indigo-500">
                                            <option value="">None</option>
                                            @foreach(($versionLibraryMap[$step->prompt_version_id] ?? []) as $entry)
                                            <option value="{{ $entry['id'] }}" {{ $step->library_entry_id == $entry['id'] ? 'selected' : '' }}>
                                                {{ $entry['label'] }}
                                            </option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div>
                                        <label class="block text-xs font-medium text-gray-600 mb-1">Notes</label>
                                        <input type="text" name="notes" value="{{ $step->notes }}" placeholder="Commentary on this step…"
                                               class="w-full border-gray-300 rounded-md shadow-sm text-sm focus:ring-indigo-500 focus:border-indigo-500">
                                    </div>
                                </div>
                                <div class="flex justify-end gap-2 mt-3">
                                    <button type="button" @click="editing = false"
                                            class="px-3 py-1.5 text-xs text-gray-600 border border-gray-300 rounded-md hover:bg-gray-50">Cancel</button>
                                    <button type="submit"
                                            class="px-3 py-1.5 text-xs bg-indigo-600 text-white rounded-md hover:bg-indigo-700">Save</button>
                                </div>
                            </form>
                        </div>
                    </div>
                    @endforeach
                </div>
                @endif
            </div>

            {{-- Add step form --}}
            <div class="bg-white shadow-sm sm:rounded-lg p-6"
                 x-data="addStepForm({{ Js::from($promptVersionMap) }}, {{ Js::from($versionLibraryMap) }})">
                <h3 class="font-semibold text-gray-800 mb-4">Add Step</h3>
                <form method="POST" action="{{ route('story-steps.store', $story) }}">
                    @csrf

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-4">
                        {{-- Prompt --}}
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Prompt <span class="text-red-500">*</span></label>
                            <select name="prompt_id" x-model="promptId" @change="onPromptChange()" required
                                    class="w-full border-gray-300 rounded-md shadow-sm text-sm focus:ring-indigo-500 focus:border-indigo-500">
                                <option value="">Select a prompt…</option>
                                @foreach($prompts as $p)
                                <option value="{{ $p->id }}">{{ $p->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        {{-- Version --}}
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Version <span class="text-red-500">*</span></label>
                            <select name="prompt_version_id" x-model="versionId" @change="onVersionChange()" required
                                    :disabled="!promptId"
                                    class="w-full border-gray-300 rounded-md shadow-sm text-sm focus:ring-indigo-500 focus:border-indigo-500 disabled:bg-gray-50 disabled:text-gray-400">
                                <option value="">Select a version…</option>
                                <template x-for="v in versions" :key="v.id">
                                    <option :value="v.id" x-text="'v' + v.version_number + (v.commit_message ? ' — ' + v.commit_message : '')"></option>
                                </template>
                            </select>
                        </div>

                        {{-- Library response --}}
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Library Response <span class="text-gray-400 font-normal">(optional)</span></label>
                            <select name="library_entry_id" x-model="libraryEntryId"
                                    :disabled="!versionId"
                                    class="w-full border-gray-300 rounded-md shadow-sm text-sm focus:ring-indigo-500 focus:border-indigo-500 disabled:bg-gray-50 disabled:text-gray-400">
                                <option value="">None</option>
                                <template x-for="e in libraryEntries" :key="e.id">
                                    <option :value="e.id" x-text="e.label"></option>
                                </template>
                            </select>
                            <template x-if="selectedEntry">
                                <p class="mt-1 text-xs text-gray-400 italic truncate" x-text="selectedEntry.preview"></p>
                            </template>
                        </div>

                        {{-- Notes --}}
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Notes <span class="text-gray-400 font-normal">(optional)</span></label>
                            <input type="text" name="notes" placeholder="Commentary on this step…"
                                   class="w-full border-gray-300 rounded-md shadow-sm text-sm focus:ring-indigo-500 focus:border-indigo-500">
                        </div>
                    </div>

                    <div class="flex justify-end">
                        <button type="submit" :disabled="!promptId || !versionId"
                                class="px-4 py-2 text-sm bg-indigo-600 text-white rounded-md hover:bg-indigo-700 disabled:opacity-40 disabled:cursor-not-allowed">
                            Add Step
                        </button>
                    </div>
                </form>
            </div>

            {{-- Danger zone --}}
            <div class="bg-white shadow-sm sm:rounded-lg p-6 border-t-4 border-red-300">
                <h3 class="font-semibold text-red-700 mb-2">Danger Zone</h3>
                <form method="POST" action="{{ route('stories.destroy', $story) }}" onsubmit="return confirm('Delete this story and all its steps?')">
                    @csrf @method('DELETE')
                    <button type="submit" class="px-4 py-2 bg-red-600 text-white text-sm rounded-md hover:bg-red-700">Delete Story</button>
                </form>
            </div>

        </div>
    </div>

    <script>
    function addStepForm(promptVersionMap, versionLibraryMap) {
        return {
            promptVersionMap,
            versionLibraryMap,
            promptId: '',
            versionId: '',
            libraryEntryId: '',
            get versions() {
                return this.promptVersionMap[this.promptId] || [];
            },
            get libraryEntries() {
                return this.versionLibraryMap[this.versionId] || [];
            },
            get selectedEntry() {
                if (!this.libraryEntryId) return null;
                return this.libraryEntries.find(e => String(e.id) === String(this.libraryEntryId)) || null;
            },
            onPromptChange() {
                this.versionId = '';
                this.libraryEntryId = '';
                const vs = this.versions;
                if (vs.length === 1) this.versionId = String(vs[0].id);
            },
            onVersionChange() {
                this.libraryEntryId = '';
            },
        };
    }
    </script>
</x-app-layout>
