<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Categories</h2>
            <a href="{{ route('categories.create') }}" class="inline-flex items-center px-4 py-2 bg-indigo-600 text-white text-sm rounded-md hover:bg-indigo-700">
                New Category
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            @if(session('success'))
            <div class="mb-4 bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded">{{ session('success') }}</div>
            @endif

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                @if($categories->isEmpty())
                <div class="p-12 text-center text-gray-500">
                    <p class="text-lg">No categories yet.</p>
                    <a href="{{ route('categories.create') }}" class="mt-4 inline-flex items-center px-4 py-2 bg-indigo-600 text-white text-sm rounded-md hover:bg-indigo-700">Create your first category</a>
                </div>
                @else
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Order</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Color</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Prompts</th>
                            <th class="relative px-6 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @foreach($categories as $category)
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 text-sm text-gray-400">{{ $category->sort_order }}</td>
                            <td class="px-6 py-4">
                                <span class="font-medium text-gray-800">{{ $category->name }}</span>
                                <span class="ml-2 text-xs text-gray-400 font-mono">{{ $category->slug }}</span>
                            </td>
                            <td class="px-6 py-4">
                                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-{{ $category->color }}-100 text-{{ $category->color }}-700 border border-{{ $category->color }}-200">
                                    {{ $category->color }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-sm">
                                @if($category->prompts_count > 0)
                                <a href="{{ route('prompts.index', ['category' => $category->slug]) }}" class="text-indigo-600 hover:underline">{{ $category->prompts_count }}</a>
                                @else
                                <span class="text-gray-300">0</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-right text-sm font-medium">
                                <a href="{{ route('categories.edit', $category) }}" class="text-indigo-600 hover:text-indigo-800 mr-3">Edit</a>
                                <form method="POST" action="{{ route('categories.destroy', $category) }}" class="inline"
                                      onsubmit="return confirm('Delete this category? Prompts will become uncategorized.')">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="text-red-600 hover:text-red-800">Delete</button>
                                </form>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
