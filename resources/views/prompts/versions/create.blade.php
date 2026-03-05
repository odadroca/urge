<x-app-layout>
    <x-slot name="header">
        <div>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">New Version</h2>
            <p class="text-sm text-gray-500 mt-0.5">
                <a href="{{ route('prompts.show', $prompt) }}" class="text-indigo-600 hover:underline">{{ $prompt->name }}</a>
            </p>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <form method="POST" action="{{ route('prompts.versions.store', $prompt) }}">
                    @csrf
                    <div class="mb-5">
                        <label for="content" class="block text-sm font-medium text-gray-700 mb-1">
                            Prompt Content <span class="text-red-500">*</span>
                        </label>
                        <p class="text-xs text-gray-400 mb-2">Use <code class="font-mono bg-gray-100 px-1 rounded">&#123;&#123;variable_name&#125;&#125;</code> for placeholders. Variables will be extracted automatically.</p>
                        <textarea name="content" id="content" rows="16" required
                            class="w-full font-mono text-sm border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 @error('content') border-red-300 @enderror"
                        >{{ old('content', $latest?->content) }}</textarea>
                        @error('content')<p class="mt-1 text-sm text-red-500">{{ $message }}</p>@enderror
                    </div>
                    <div class="mb-6">
                        <label for="commit_message" class="block text-sm font-medium text-gray-700 mb-1">Commit Message</label>
                        <input type="text" name="commit_message" id="commit_message" value="{{ old('commit_message') }}" maxlength="500"
                            placeholder="What changed in this version?"
                            class="w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                        @error('commit_message')<p class="mt-1 text-sm text-red-500">{{ $message }}</p>@enderror
                    </div>
                    <div class="flex justify-end gap-3">
                        <a href="{{ route('prompts.versions.index', $prompt) }}" class="px-4 py-2 text-sm text-gray-700 border border-gray-300 rounded-md hover:bg-gray-50">Cancel</a>
                        <button type="submit" class="px-4 py-2 text-sm bg-indigo-600 text-white rounded-md hover:bg-indigo-700">Save Version</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
