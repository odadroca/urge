<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <div>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">Run History</h2>
                <p class="text-sm text-gray-500 mt-0.5">
                    <a href="{{ route('prompts.show', $prompt) }}" class="text-indigo-600 hover:underline">{{ $prompt->name }}</a>
                </p>
            </div>
            <a href="{{ route('prompt-runs.create', $prompt) }}" class="inline-flex items-center px-4 py-2 bg-indigo-600 text-white text-sm rounded-md hover:bg-indigo-700">
                New Run
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                @if($runs->isEmpty())
                <div class="p-12 text-center text-gray-500">
                    <p>No runs yet.</p>
                    <a href="{{ route('prompt-runs.create', $prompt) }}" class="mt-3 inline-flex items-center text-sm text-indigo-600 hover:underline">Create the first run</a>
                </div>
                @else
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">#</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Version</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Models</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Rated</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">By</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                            <th class="relative px-6 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @foreach($runs as $run)
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 text-sm text-gray-400">#{{ $run->id }}</td>
                            <td class="px-6 py-4 text-sm font-mono text-gray-600">v{{ $run->version?->version_number }}</td>
                            <td class="px-6 py-4 text-sm">
                                <div class="flex flex-wrap gap-1">
                                    @foreach($run->responses as $resp)
                                    <span class="inline-flex items-center px-1.5 py-0.5 rounded text-xs
                                        {{ $resp->isSuccess() ? 'bg-gray-100 text-gray-600' : 'bg-red-50 text-red-500' }}">
                                        {{ $resp->provider->name }}
                                    </span>
                                    @endforeach
                                </div>
                            </td>
                            <td class="px-6 py-4 text-sm">
                                @php $rated = $run->responses->whereNotNull('rating')->count(); @endphp
                                @if($rated > 0)
                                <span class="text-yellow-500 font-medium">{{ $rated }}/{{ $run->responses->count() }} ★</span>
                                @else
                                <span class="text-gray-300">—</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-500">{{ $run->creator?->name }}</td>
                            <td class="px-6 py-4 text-sm text-gray-400">{{ $run->created_at->format('Y-m-d H:i') }}</td>
                            <td class="px-6 py-4 text-right">
                                <a href="{{ route('prompt-runs.show', [$prompt, $run]) }}" class="text-sm text-indigo-600 hover:underline">View</a>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
                @if($runs->hasPages())
                <div class="px-6 py-4 border-t">{{ $runs->links() }}</div>
                @endif
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
