<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Edit Category</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <form method="POST" action="{{ route('categories.update', $category) }}">
                    @csrf @method('PUT')
                    <div class="mb-5">
                        <label for="name" class="block text-sm font-medium text-gray-700 mb-1">Name <span class="text-red-500">*</span></label>
                        <input type="text" name="name" id="name" value="{{ old('name', $category->name) }}" required
                            class="w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 @error('name') border-red-300 @enderror">
                        @error('name')<p class="mt-1 text-sm text-red-500">{{ $message }}</p>@enderror
                        <p class="mt-1 text-xs text-gray-400">Slug: <code class="font-mono">{{ $category->slug }}</code> (locked)</p>
                    </div>
                    <div class="mb-5">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Color</label>
                        <div class="flex flex-wrap gap-2">
                            @foreach($colors as $color)
                            <label class="cursor-pointer">
                                <input type="radio" name="color" value="{{ $color }}" class="sr-only peer" {{ old('color', $category->color) === $color ? 'checked' : '' }}>
                                <span class="inline-flex items-center px-3 py-1.5 rounded-full text-xs font-medium border-2 transition
                                    bg-{{ $color }}-100 text-{{ $color }}-700 border-transparent
                                    peer-checked:border-{{ $color }}-500 peer-checked:ring-2 peer-checked:ring-{{ $color }}-200">
                                    {{ $color }}
                                </span>
                            </label>
                            @endforeach
                        </div>
                        @error('color')<p class="mt-1 text-sm text-red-500">{{ $message }}</p>@enderror
                    </div>
                    <div class="mb-6">
                        <label for="sort_order" class="block text-sm font-medium text-gray-700 mb-1">Sort Order</label>
                        <input type="number" name="sort_order" id="sort_order" value="{{ old('sort_order', $category->sort_order) }}" min="0"
                            class="w-24 border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                        <p class="mt-1 text-xs text-gray-400">Lower numbers appear first.</p>
                    </div>
                    <div class="flex justify-end gap-3">
                        <a href="{{ route('categories.index') }}" class="px-4 py-2 text-sm text-gray-700 border border-gray-300 rounded-md hover:bg-gray-50">Cancel</a>
                        <button type="submit" class="px-4 py-2 text-sm bg-indigo-600 text-white rounded-md hover:bg-indigo-700">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
