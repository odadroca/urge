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

    <div class="py-12" x-data="{ onlyDiff: false }">
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

                        <button @click="onlyDiff = !onlyDiff"
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

            {{-- Diff block --}}
            <div class="bg-white shadow-sm sm:rounded-lg overflow-hidden">
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
                                {{-- Equal group: individual lines, hidden when showing only differences --}}
                                @foreach($group['items'] as $item)
                                <tr class="hover:bg-gray-50" x-show="!onlyDiff">
                                    <td class="w-10 text-right pr-3 pl-2 py-0.5 text-gray-300 select-none border-r border-gray-100">{{ $item['lineA'] }}</td>
                                    <td class="w-10 text-right pr-3 py-0.5 text-gray-300 select-none border-r border-gray-100">{{ $item['lineB'] }}</td>
                                    <td class="w-6 py-0.5 border-r border-gray-100 select-none"></td>
                                    <td class="pl-4 pr-6 py-0.5 text-gray-500 whitespace-pre-wrap break-all">{{ $item['line'] }}</td>
                                </tr>
                                @endforeach
                                {{-- Equal group: summary row, visible only when showing differences --}}
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

        </div>
    </div>
</x-app-layout>
