<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-start">
            <div>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    Compare v{{ $versionA->version_number }} → v{{ $versionB->version_number }}
                </h2>
                <p class="text-sm text-gray-500 mt-0.5">
                    <a href="{{ route('prompts.show', $prompt) }}" class="text-indigo-600 hover:underline">{{ $prompt->name }}</a>
                    &rsaquo; <a href="{{ route('prompts.versions.index', $prompt) }}" class="text-indigo-600 hover:underline">versions</a>
                </p>
            </div>
            <a href="{{ route('prompts.versions.index', $prompt) }}"
               class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 text-gray-700 text-sm rounded-md hover:bg-gray-50">
                ← Back
            </a>
        </div>
    </x-slot>

    <div class="py-12" x-data="{ onlyDiff: false, diffView: 'lines', ...diffViewer() }"
         x-init="openDiff(@js($versionA->content), @js($versionB->content), 'v{{ $versionA->version_number }}', 'v{{ $versionB->version_number }}'); showDiffModal = false;">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8 space-y-4">

            {{-- Version meta + stats --}}
            <div class="bg-white shadow-sm sm:rounded-lg p-5">
                <div class="flex flex-wrap items-center justify-between gap-4">

                    {{-- Version cards --}}
                    <div class="flex items-center gap-3 text-sm">
                        <div class="px-3 py-2 bg-red-50 border border-red-200 rounded-md">
                            <span class="font-mono font-semibold text-red-700">v{{ $versionA->version_number }}</span>
                            <span class="text-gray-500 ml-2">{{ $versionA->commit_message ?? 'no message' }}</span>
                            <span class="text-gray-400 ml-2">· {{ $versionA->creator?->name }} · {{ $versionA->created_at->format('Y-m-d') }}</span>
                        </div>
                        <span class="text-gray-400">→</span>
                        <div class="px-3 py-2 bg-green-50 border border-green-200 rounded-md">
                            <span class="font-mono font-semibold text-green-700">v{{ $versionB->version_number }}</span>
                            <span class="text-gray-500 ml-2">{{ $versionB->commit_message ?? 'no message' }}</span>
                            <span class="text-gray-400 ml-2">· {{ $versionB->creator?->name }} · {{ $versionB->created_at->format('Y-m-d') }}</span>
                        </div>
                    </div>

                    {{-- Stats + toggle --}}
                    <div class="flex items-center gap-4">
                        <div class="flex items-center gap-3 text-sm font-mono">
                            @if($additions > 0)
                            <span class="text-green-700 font-semibold">+{{ $additions }}</span>
                            @endif
                            @if($removals > 0)
                            <span class="text-red-700 font-semibold">−{{ $removals }}</span>
                            @endif
                            @if($additions === 0 && $removals === 0)
                            <span class="text-gray-400">identical</span>
                            @endif
                        </div>

                        {{-- Diff view mode selector --}}
                        <div class="inline-flex rounded-md shadow-sm">
                            <button @click="diffView = 'lines'"
                                    class="px-3 py-1.5 text-xs font-medium rounded-l-md border transition"
                                    :class="diffView === 'lines' ? 'bg-indigo-600 text-white border-indigo-600' : 'bg-white text-gray-600 border-gray-300 hover:bg-gray-50'">
                                Lines
                            </button>
                            <button @click="diffView = 'words'"
                                    class="px-3 py-1.5 text-xs font-medium border-t border-r border-b transition"
                                    :class="diffView === 'words' ? 'bg-indigo-600 text-white border-indigo-600' : 'bg-white text-gray-600 border-gray-300 hover:bg-gray-50'">
                                Words
                            </button>
                            <button @click="diffView = 'chars'"
                                    class="px-3 py-1.5 text-xs font-medium rounded-r-md border-t border-r border-b transition"
                                    :class="diffView === 'chars' ? 'bg-indigo-600 text-white border-indigo-600' : 'bg-white text-gray-600 border-gray-300 hover:bg-gray-50'">
                                Chars
                            </button>
                        </div>

                        <button @click="onlyDiff = !onlyDiff"
                            x-show="diffView === 'lines'"
                            :class="onlyDiff
                                ? 'bg-indigo-600 text-white border-indigo-600'
                                : 'bg-white text-gray-600 border-gray-300 hover:bg-gray-50'"
                            class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium border rounded-md transition">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                <path stroke-linecap="round" d="M4 6h16M4 12h8M4 18h12"/>
                            </svg>
                            <span x-text="onlyDiff ? 'Show all lines' : 'Show only differences'"></span>
                        </button>
                    </div>
                </div>
            </div>

            {{-- Line-based diff block --}}
            <div class="bg-white shadow-sm sm:rounded-lg overflow-hidden" x-show="diffView === 'lines'">
                @if($additions === 0 && $removals === 0)
                    <div class="p-10 text-center text-gray-400 text-sm">
                        These two versions have identical content.
                    </div>
                @else
                <div class="overflow-x-auto">
                    <table class="w-full border-collapse font-mono text-xs leading-5">
                        <tbody>
                        @foreach($groups as $group)

                            @if($group['type'] === 'removed')
                                @foreach($group['items'] as $item)
                                <tr class="bg-red-50 hover:bg-red-100">
                                    <td class="w-10 text-right pr-3 pl-2 py-0.5 text-red-400 select-none border-r border-red-200">{{ $item['lineA'] }}</td>
                                    <td class="w-10 text-right pr-3 py-0.5 text-gray-300 select-none border-r border-red-200"></td>
                                    <td class="w-6 text-center py-0.5 text-red-500 select-none border-r border-red-200">−</td>
                                    <td class="pl-4 pr-6 py-0.5 text-red-900 whitespace-pre-wrap break-all">{{ $item['line'] }}</td>
                                </tr>
                                @endforeach

                            @elseif($group['type'] === 'added')
                                @foreach($group['items'] as $item)
                                <tr class="bg-green-50 hover:bg-green-100">
                                    <td class="w-10 text-right pr-3 pl-2 py-0.5 text-gray-300 select-none border-r border-green-200"></td>
                                    <td class="w-10 text-right pr-3 py-0.5 text-green-500 select-none border-r border-green-200">{{ $item['lineB'] }}</td>
                                    <td class="w-6 text-center py-0.5 text-green-600 select-none border-r border-green-200">+</td>
                                    <td class="pl-4 pr-6 py-0.5 text-green-900 whitespace-pre-wrap break-all">{{ $item['line'] }}</td>
                                </tr>
                                @endforeach

                            @else
                                @php $equalCount = count($group['items']); @endphp
                                @foreach($group['items'] as $item)
                                <tr class="hover:bg-gray-50" x-show="!onlyDiff">
                                    <td class="w-10 text-right pr-3 pl-2 py-0.5 text-gray-300 select-none border-r border-gray-100">{{ $item['lineA'] }}</td>
                                    <td class="w-10 text-right pr-3 py-0.5 text-gray-300 select-none border-r border-gray-100">{{ $item['lineB'] }}</td>
                                    <td class="w-6 py-0.5 border-r border-gray-100 select-none"></td>
                                    <td class="pl-4 pr-6 py-0.5 text-gray-500 whitespace-pre-wrap break-all">{{ $item['line'] }}</td>
                                </tr>
                                @endforeach
                                <tr class="bg-gray-50 border-t border-b border-gray-200" x-show="onlyDiff">
                                    <td colspan="4" class="px-4 py-1.5 text-gray-400 text-xs select-none">
                                        ··· {{ $equalCount }} unchanged line{{ $equalCount !== 1 ? 's' : '' }}
                                    </td>
                                </tr>
                            @endif

                        @endforeach
                        </tbody>
                    </table>
                </div>
                @endif
            </div>

            {{-- Word/char-level diff block --}}
            <div class="bg-white shadow-sm sm:rounded-lg overflow-hidden" x-show="diffView === 'words' || diffView === 'chars'" x-cloak
                 x-effect="if (diffView === 'words') { diffMode = 'words'; computeDiff(); } else if (diffView === 'chars') { diffMode = 'chars'; computeDiff(); }">
                <div class="p-5">
                    <div class="flex items-center justify-between mb-4">
                        <p class="text-xs font-medium text-gray-500 uppercase tracking-wider">
                            <span x-text="diffView === 'words' ? 'Word-level' : 'Character-level'"></span> Diff
                        </p>
                        <div class="flex items-center gap-3 text-xs font-mono">
                            <template x-if="stats.additions > 0">
                                <span class="text-green-700 font-semibold">+<span x-text="stats.additions"></span></span>
                            </template>
                            <template x-if="stats.removals > 0">
                                <span class="text-red-700 font-semibold">-<span x-text="stats.removals"></span></span>
                            </template>
                        </div>
                    </div>
                    <pre class="font-mono text-sm whitespace-pre-wrap break-words leading-relaxed bg-gray-50 border border-gray-200 rounded-md p-4 overflow-auto max-h-[32rem]" x-html="unifiedHtml"></pre>
                </div>
            </div>

        </div>
    </div>
</x-app-layout>
