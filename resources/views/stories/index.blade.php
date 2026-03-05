<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Stories</h2>
            <a href="{{ route('stories.create') }}" class="inline-flex items-center px-4 py-2 bg-indigo-600 text-white text-sm rounded-md hover:bg-indigo-700">
                New Story
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8">

            @if(session('success'))
            <div class="mb-4 bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded">{{ session('success') }}</div>
            @endif

            @if($stories->isEmpty())
            <div class="bg-white shadow-sm sm:rounded-lg p-12 text-center text-gray-500">
                <p class="text-lg">No stories yet.</p>
                <p class="mt-1 text-sm text-gray-400">A story chains prompts and library responses into a narrative thread.</p>
                <a href="{{ route('stories.create') }}" class="mt-4 inline-flex items-center px-4 py-2 bg-indigo-600 text-white text-sm rounded-md hover:bg-indigo-700">Create your first story</a>
            </div>
            @else
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5">
                @foreach($stories as $story)
                <a href="{{ route('stories.show', $story) }}"
                   class="bg-white shadow-sm sm:rounded-lg p-5 hover:shadow-md transition-shadow flex flex-col">
                    <div class="flex-1">
                        <h3 class="font-semibold text-gray-800 text-base leading-snug">{{ $story->title }}</h3>
                        @if($story->description)
                        <p class="mt-1 text-sm text-gray-500 line-clamp-2">{{ $story->description }}</p>
                        @endif
                    </div>
                    <div class="mt-4 flex items-center justify-between text-xs text-gray-400">
                        <span>
                            <span class="font-medium text-indigo-600">{{ $story->steps_count }}</span>
                            step{{ $story->steps_count !== 1 ? 's' : '' }}
                        </span>
                        <span>{{ $story->updated_at->format('Y-m-d') }}</span>
                    </div>
                    @if($story->creator)
                    <p class="mt-1 text-xs text-gray-400">by {{ $story->creator->name }}</p>
                    @endif
                </a>
                @endforeach
            </div>
            @if($stories->hasPages())
            <div class="mt-6">{{ $stories->links() }}</div>
            @endif
            @endif

        </div>
    </div>
</x-app-layout>
