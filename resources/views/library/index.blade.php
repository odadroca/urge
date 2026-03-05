<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Response Library</h2>
            <a href="{{ route('library.create') }}" class="inline-flex items-center px-4 py-2 bg-indigo-600 text-white text-sm rounded-md hover:bg-indigo-700">
                Add Entry
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

            @if(session('success'))
                <div class="mb-4 bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded">{{ session('success') }}</div>
            @endif

            {{-- Filters --}}
            <form method="GET" class="mb-4 flex flex-wrap gap-3 items-end">
                <div>
                    <label class="block text-xs text-gray-500 mb-1">Prompt</label>
                    <select name="prompt_id" class="border-gray-300 rounded-md shadow-sm text-sm focus:ring-indigo-500 focus:border-indigo-500">
                        <option value="">All prompts</option>
                        @foreach($prompts as $p)
                        <option value="{{ $p->id }}" {{ request('prompt_id') == $p->id ? 'selected' : '' }}>{{ $p->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs text-gray-500 mb-1">Provider</label>
                    <select name="provider_id" class="border-gray-300 rounded-md shadow-sm text-sm focus:ring-indigo-500 focus:border-indigo-500">
                        <option value="">All providers</option>
                        @foreach($providers as $prov)
                        <option value="{{ $prov->id }}" {{ request('provider_id') == $prov->id ? 'selected' : '' }}>{{ $prov->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="flex items-center gap-2 pb-0.5">
                    <input type="checkbox" name="rated" id="rated" value="1" {{ request('rated') ? 'checked' : '' }}
                           class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                    <label for="rated" class="text-sm text-gray-600">Rated only</label>
                </div>
                <button type="submit" class="px-3 py-2 bg-white border border-gray-300 text-gray-700 text-sm rounded-md hover:bg-gray-50">Filter</button>
                @if(request()->hasAny(['prompt_id', 'provider_id', 'rated']))
                <a href="{{ route('library.index') }}" class="text-sm text-indigo-600 hover:underline self-end pb-1">Clear</a>
                @endif
            </form>

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                @if($entries->isEmpty())
                <div class="p-12 text-center text-gray-500">
                    <p class="text-lg">No library entries yet.</p>
                    <a href="{{ route('library.create') }}" class="mt-4 inline-flex items-center px-4 py-2 bg-indigo-600 text-white text-sm rounded-md hover:bg-indigo-700">Add your first entry</a>
                </div>
                @else
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Prompt</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Version</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Model</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Rating</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Added</th>
                            <th class="relative px-6 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @foreach($entries as $entry)
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4">
                                <a href="{{ route('prompts.show', $entry->prompt) }}" class="text-indigo-600 font-medium hover:underline">{{ $entry->prompt->name }}</a>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-500">
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-700">v{{ $entry->version->version_number }}</span>
                            </td>
                            <td class="px-6 py-4 text-sm">
                                @if($entry->provider)
                                <span class="text-gray-700">{{ $entry->provider->name }}</span>
                                <span class="ml-1 text-xs text-gray-400 font-mono">{{ $entry->model_used }}</span>
                                @else
                                <span class="text-gray-700 font-mono text-xs">{{ $entry->model_used }}</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-sm">
                                @if($entry->rating)
                                <span class="text-yellow-500">{{ str_repeat('★', $entry->rating) }}<span class="text-gray-200">{{ str_repeat('★', 5 - $entry->rating) }}</span></span>
                                @else
                                <span class="text-gray-300">—</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-400">{{ $entry->created_at->format('Y-m-d') }}</td>
                            <td class="px-6 py-4 text-right text-sm font-medium space-x-3">
                                <a href="{{ route('library.show', $entry) }}" class="text-indigo-600 hover:text-indigo-800">View</a>
                                <a href="{{ route('library.edit', $entry) }}" class="text-gray-500 hover:text-gray-700">Edit</a>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
                @if($entries->hasPages())
                <div class="px-6 py-4 border-t">{{ $entries->links() }}</div>
                @endif
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
