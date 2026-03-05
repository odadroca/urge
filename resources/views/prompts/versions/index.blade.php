<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <div>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">Version History</h2>
                <p class="text-sm text-gray-500 mt-0.5">
                    <a href="{{ route('prompts.show', $prompt) }}" class="text-indigo-600 hover:underline">{{ $prompt->name }}</a>
                </p>
            </div>
            @can('createVersion', $prompt)
            <a href="{{ route('prompts.versions.create', $prompt) }}" class="inline-flex items-center px-4 py-2 bg-indigo-600 text-white text-sm rounded-md hover:bg-indigo-700">
                New Version
            </a>
            @endcan
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8">
            @if(session('success'))
                <div class="mb-4 bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded">{{ session('success') }}</div>
            @endif

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                @if($versions->isEmpty())
                    <div class="p-12 text-center text-gray-500">No versions yet.</div>
                @else
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Version</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Commit Message</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Variables</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Author</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                            <th class="relative px-6 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @foreach($versions as $version)
                        <tr class="{{ $prompt->active_version_id === $version->id ? 'bg-green-50' : '' }}">
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-2">
                                    <span class="font-mono font-medium text-gray-800">v{{ $version->version_number }}</span>
                                    @if($prompt->active_version_id === $version->id)
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">active</span>
                                    @endif
                                </div>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-600">{{ $version->commit_message ?? '—' }}</td>
                            <td class="px-6 py-4 text-sm text-gray-500">
                                @if($version->variables)
                                    <span class="font-mono text-xs">{{ implode(', ', $version->variables) }}</span>
                                @else
                                    —
                                @endif
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-500">{{ $version->creator?->name }}</td>
                            <td class="px-6 py-4 text-sm text-gray-500">{{ $version->created_at->format('Y-m-d H:i') }}</td>
                            <td class="px-6 py-4 text-right text-sm space-x-3">
                                <a href="{{ route('prompts.versions.show', [$prompt, $version->version_number]) }}" class="text-indigo-600 hover:underline">View</a>
                                @can('activateVersion', $prompt)
                                @if($prompt->active_version_id !== $version->id)
                                <form method="POST" action="{{ route('prompts.versions.activate', [$prompt, $version->version_number]) }}" class="inline">
                                    @csrf
                                    <button type="submit" class="text-green-600 hover:underline">Set Active</button>
                                </form>
                                @endif
                                @endcan
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
