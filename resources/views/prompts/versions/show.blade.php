<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-start">
            <div>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    v{{ $version->version_number }}
                    @if($prompt->active_version_id === $version->id)
                    <span class="ml-2 inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">active</span>
                    @endif
                </h2>
                <p class="text-sm text-gray-500 mt-0.5">
                    <a href="{{ route('prompts.show', $prompt) }}" class="text-indigo-600 hover:underline">{{ $prompt->name }}</a>
                    &rsaquo; <a href="{{ route('prompts.versions.index', $prompt) }}" class="text-indigo-600 hover:underline">versions</a>
                </p>
            </div>
            <div class="flex gap-2">
                @can('activateVersion', $prompt)
                @if($prompt->active_version_id !== $version->id)
                <form method="POST" action="{{ route('prompts.versions.activate', [$prompt, $version->version_number]) }}">
                    @csrf
                    <button type="submit" class="inline-flex items-center px-4 py-2 bg-green-600 text-white text-sm rounded-md hover:bg-green-700">Set as Active</button>
                </form>
                @endif
                @endcan
            </div>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if(session('success'))
                <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded">{{ session('success') }}</div>
            @endif

            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                <div class="flex flex-wrap gap-6 text-sm text-gray-500 mb-4">
                    <span>By <strong>{{ $version->creator?->name }}</strong></span>
                    <span>{{ $version->created_at->format('Y-m-d H:i') }}</span>
                    @if($version->commit_message)
                    <span class="italic">"{{ $version->commit_message }}"</span>
                    @endif
                </div>

                @if($version->variables && count($version->variables))
                <div class="mb-4">
                    <p class="text-xs text-gray-500 mb-1.5 font-medium uppercase tracking-wider">Variables</p>
                    <div class="flex flex-wrap gap-1.5">
                        @foreach($version->variables as $var)
                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-mono bg-indigo-50 text-indigo-700 border border-indigo-200">&#123;&#123;{{ $var }}&#125;&#125;</span>
                        @endforeach
                    </div>
                </div>
                @endif

                <div>
                    <p class="text-xs text-gray-500 mb-1.5 font-medium uppercase tracking-wider">Content</p>
                    <pre class="bg-gray-50 border border-gray-200 rounded p-4 text-sm text-gray-800 whitespace-pre-wrap font-mono overflow-auto">{{ $version->content }}</pre>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
