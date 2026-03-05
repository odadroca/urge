<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">New Prompt</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <form method="POST" action="{{ route('prompts.store') }}">
                    @csrf
                    <div class="mb-5">
                        <label for="name" class="block text-sm font-medium text-gray-700 mb-1">Name <span class="text-red-500">*</span></label>
                        <input type="text" name="name" id="name" value="{{ old('name') }}" required
                            class="w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 @error('name') border-red-300 @enderror">
                        @error('name')<p class="mt-1 text-sm text-red-500">{{ $message }}</p>@enderror
                        <p class="mt-1 text-xs text-gray-400">A URL-safe slug will be generated automatically.</p>
                    </div>
                    <div class="mb-5">
                        <label for="description" class="block text-sm font-medium text-gray-700 mb-1">Description</label>
                        <textarea name="description" id="description" rows="3"
                            class="w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">{{ old('description') }}</textarea>
                        @error('description')<p class="mt-1 text-sm text-red-500">{{ $message }}</p>@enderror
                    </div>
                    <div class="mb-6">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Tags</label>
                        <x-tag-input :tags="old('tags', [])" />
                    </div>
                    <div class="flex justify-end gap-3">
                        <a href="{{ route('prompts.index') }}" class="px-4 py-2 text-sm text-gray-700 border border-gray-300 rounded-md hover:bg-gray-50">Cancel</a>
                        <button type="submit" class="px-4 py-2 text-sm bg-indigo-600 text-white rounded-md hover:bg-indigo-700">Create Prompt</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
