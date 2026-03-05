<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Prompts</h2>
            @can('create', App\Models\Prompt::class)
            <a href="{{ route('prompts.create') }}" class="inline-flex items-center px-4 py-2 bg-indigo-600 text-white text-sm rounded-md hover:bg-indigo-700">
                New Prompt
            </a>
            @endcan
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            @if(session('success'))
                <div class="mb-4 bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded">{{ session('success') }}</div>
            @endif

            {{-- Tag filter bar --}}
            @if(!empty($allTags))
            <div class="mb-4 flex flex-wrap items-center gap-2">
                <span class="text-xs text-gray-400 font-medium uppercase tracking-wider mr-1">Filter:</span>
                <a href="{{ route('prompts.index') }}"
                   class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium border transition
                          {{ !$tag ? 'bg-gray-800 text-white border-gray-800' : 'bg-white text-gray-600 border-gray-300 hover:border-gray-400' }}">
                    All
                </a>
                @foreach($allTags as $t)
                <a href="{{ route('prompts.index', ['tag' => $t]) }}"
                   class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium border transition
                          {{ $tag === $t ? 'bg-purple-600 text-white border-purple-600' : 'bg-white text-purple-700 border-purple-200 hover:border-purple-400' }}">
                    {{ $t }}
                </a>
                @endforeach
            </div>
            @endif

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                @if($prompts->isEmpty())
                    <div class="p-12 text-center text-gray-500">
                        @if($tag)
                            <p class="text-lg">No prompts tagged "{{ $tag }}".</p>
                            <a href="{{ route('prompts.index') }}" class="mt-3 inline-flex items-center text-sm text-indigo-600 hover:underline">← Clear filter</a>
                        @else
                            <p class="text-lg">No prompts yet.</p>
                            @can('create', App\Models\Prompt::class)
                            <a href="{{ route('prompts.create') }}" class="mt-4 inline-flex items-center px-4 py-2 bg-indigo-600 text-white text-sm rounded-md hover:bg-indigo-700">Create your first prompt</a>
                            @endcan
                        @endif
                    </div>
                @else
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-8"></th>
                            <th class="px-6 py-3 w-8"></th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tags</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Version</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Variables</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Created</th>
                            <th class="relative px-6 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @foreach($prompts as $prompt)
                        <tbody x-data="{ open: false, copied: false }" class="block contents">
                        <tr class="hover:bg-gray-50 cursor-pointer" @click="open = !open">
                            <td class="px-6 py-4 text-gray-400">
                                <svg x-bind:class="open ? 'rotate-90' : ''" class="w-4 h-4 transition-transform duration-150" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                                </svg>
                            </td>
                            <td class="px-3 py-4" @click.stop>
                                <a href="{{ route('prompts.show', $prompt) }}" title="View" class="text-gray-400 hover:text-indigo-600">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                    </svg>
                                </a>
                            </td>
                            <td class="px-6 py-4">
                                <span class="text-indigo-600 font-medium">{{ $prompt->name }}</span>
                                @if($prompt->description)
                                <p class="text-xs text-gray-400 mt-0.5 truncate max-w-xs">{{ $prompt->description }}</p>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-sm">
                                @if($prompt->tags && count($prompt->tags))
                                <div class="flex flex-wrap gap-1">
                                    @foreach($prompt->tags as $t)
                                    <a href="{{ route('prompts.index', ['tag' => $t]) }}"
                                       @click.stop
                                       class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-purple-50 text-purple-700 border border-purple-200 hover:bg-purple-100 transition">{{ $t }}</a>
                                    @endforeach
                                </div>
                                @else
                                <span class="text-gray-300">—</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-sm">
                                @if($prompt->activeVersion)
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">v{{ $prompt->activeVersion->version_number }}</span>
                                @else
                                    <span class="text-gray-300">None</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-sm">
                                @if($prompt->activeVersion && $prompt->activeVersion->variables)
                                    <div class="flex flex-wrap gap-1">
                                        @foreach(array_slice($prompt->activeVersion->variables, 0, 3) as $var)
                                        <span class="inline-flex items-center px-1.5 py-0.5 rounded text-xs font-mono bg-indigo-50 text-indigo-600 border border-indigo-100">{{ $var }}</span>
                                        @endforeach
                                        @if(count($prompt->activeVersion->variables) > 3)
                                        <span class="text-xs text-gray-400">+{{ count($prompt->activeVersion->variables) - 3 }} more</span>
                                        @endif
                                    </div>
                                @else
                                    <span class="text-gray-300">—</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-400">{{ $prompt->created_at->format('Y-m-d') }}</td>
                            <td class="px-6 py-4 text-right text-sm font-medium" @click.stop>
                                <a href="{{ route('prompts.versions.index', $prompt) }}" class="text-gray-500 hover:text-gray-700">History</a>
                            </td>
                        </tr>
                        <tr x-show="open" x-cloak class="bg-gray-50 border-b border-gray-100">
                            <td colspan="2"></td>
                            <td colspan="6" class="px-6 py-4">
                                @if($prompt->activeVersion)
                                    @if($prompt->activeVersion->variables && count($prompt->activeVersion->variables))
                                    <div class="mb-3 flex flex-wrap gap-1.5">
                                        @foreach($prompt->activeVersion->variables as $var)
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-mono bg-indigo-50 text-indigo-700 border border-indigo-200">&#123;&#123;{{ $var }}&#125;&#125;</span>
                                        @endforeach
                                    </div>
                                    @endif
                                    <div class="relative">
                                        <pre x-ref="preContent" class="text-xs text-gray-700 font-mono bg-white border border-gray-200 rounded p-3 pr-20 whitespace-pre-wrap max-h-40 overflow-auto leading-relaxed">{{ $prompt->activeVersion->content }}</pre>
                                        <button
                                            @click.stop="navigator.clipboard.writeText($refs.preContent.textContent.trim()); copied = true; setTimeout(() => copied = false, 2000)"
                                            class="absolute top-2 right-2 inline-flex items-center gap-1 px-2 py-1 text-xs rounded border transition"
                                            :class="copied ? 'bg-green-50 border-green-300 text-green-700' : 'bg-white border-gray-200 text-gray-500 hover:border-gray-300 hover:text-gray-700'">
                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                                <rect x="9" y="9" width="13" height="13" rx="2" stroke-linecap="round"/>
                                                <path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1" stroke-linecap="round"/>
                                            </svg>
                                            <span x-text="copied ? 'Copied!' : 'Copy'"></span>
                                        </button>
                                    </div>
                                    @if($prompt->activeVersion->commit_message)
                                    <p class="mt-2 text-xs text-gray-400 italic">{{ $prompt->activeVersion->commit_message }}</p>
                                    @endif
                                @else
                                    <p class="text-sm text-gray-400 italic">No active version — <a href="{{ route('prompts.versions.create', $prompt) }}" class="text-indigo-600 hover:underline">create one</a>.</p>
                                @endif
                            </td>
                        </tr>
                        </tbody>
                        @endforeach
                    </tbody>
                </table>
                @if($prompts->hasPages())
                <div class="px-6 py-4 border-t">{{ $prompts->links() }}</div>
                @endif
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
