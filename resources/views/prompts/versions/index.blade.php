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

    <div class="py-12" x-data="{ selected: [] }">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8 space-y-4">
            @if(session('success'))
                <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded">{{ session('success') }}</div>
            @endif
            @if(session('error'))
                <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded">{{ session('error') }}</div>
            @endif

            {{-- Compare bar (visible when 2 selected) --}}
            <div x-show="selected.length === 2" x-cloak
                 class="flex items-center justify-between bg-indigo-50 border border-indigo-200 rounded-lg px-4 py-3">
                <p class="text-sm text-indigo-700">
                    Comparing
                    <strong x-text="'v' + Math.min(...selected)"></strong>
                    and
                    <strong x-text="'v' + Math.max(...selected)"></strong>
                </p>
                <div class="flex gap-2">
                    <button @click="selected = []"
                        class="px-3 py-1.5 text-xs text-indigo-600 border border-indigo-300 rounded-md hover:bg-white">
                        Clear
                    </button>
                    <button @click="window.location = '{{ route('prompts.versions.compare', $prompt) }}?v1=' + Math.min(...selected) + '&v2=' + Math.max(...selected)"
                        class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold bg-indigo-600 text-white rounded-md hover:bg-indigo-700">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                            <path stroke-linecap="round" d="M8 7h12M8 12h8M8 17h12"/>
                        </svg>
                        View diff
                    </button>
                </div>
            </div>

            {{-- Hint when 1 selected --}}
            <div x-show="selected.length === 1" x-cloak
                 class="px-4 py-2 bg-gray-50 border border-gray-200 rounded-lg text-sm text-gray-500">
                Select one more version to compare.
            </div>

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                @if($versions->isEmpty())
                    <div class="p-12 text-center text-gray-500">No versions yet.</div>
                @else
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 w-10">
                                <span class="sr-only">Select</span>
                            </th>
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
                        <tr class="{{ $prompt->active_version_id === $version->id ? 'bg-green-50' : '' }}"
                            :class="selected.includes({{ $version->version_number }}) ? 'ring-2 ring-inset ring-indigo-300' : ''">
                            <td class="px-4 py-4 text-center">
                                <input type="checkbox"
                                    :value="{{ $version->version_number }}"
                                    x-model="selected"
                                    :disabled="selected.length >= 2 && !selected.includes({{ $version->version_number }})"
                                    class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500 disabled:opacity-30 cursor-pointer disabled:cursor-not-allowed">
                            </td>
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

            @if($versions->count() >= 2)
            <p class="text-xs text-gray-400 text-center">Check two versions to compare them.</p>
            @endif

            {{-- Environments --}}
            @if($versions->isNotEmpty())
            @can('activateVersion', $prompt)
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <h3 class="font-semibold text-gray-800 mb-3">Environments</h3>
                <div class="space-y-3">
                    @foreach($environments as $env)
                    <div class="flex items-center justify-between bg-gray-50 rounded-md px-4 py-2">
                        <div>
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-100 text-blue-800">{{ $env->name }}</span>
                            <span class="text-sm text-gray-500 ml-2">
                                @php $envVersion = $versions->firstWhere('id', $env->prompt_version_id); @endphp
                                {{ $envVersion ? 'v' . $envVersion->version_number : 'unassigned' }}
                            </span>
                        </div>
                        <form method="POST" action="{{ route('prompts.environments.assign', $prompt) }}" class="flex items-center gap-2">
                            @csrf
                            <input type="hidden" name="environment_name" value="{{ $env->name }}">
                            <select name="version_id" class="text-xs border-gray-300 rounded-md">
                                @foreach($versions as $v)
                                <option value="{{ $v->id }}" {{ $env->prompt_version_id === $v->id ? 'selected' : '' }}>v{{ $v->version_number }}</option>
                                @endforeach
                            </select>
                            <button type="submit" class="px-2 py-1 text-xs bg-indigo-600 text-white rounded hover:bg-indigo-700">Assign</button>
                        </form>
                    </div>
                    @endforeach

                    {{-- Add new environment --}}
                    <form method="POST" action="{{ route('prompts.environments.assign', $prompt) }}" class="flex items-center gap-2 pt-2 border-t border-gray-200">
                        @csrf
                        <input type="text" name="environment_name" placeholder="New environment name" required
                            class="text-xs border-gray-300 rounded-md flex-1" list="env-suggestions">
                        <datalist id="env-suggestions">
                            @foreach(config('urge.default_environments', []) as $envName)
                                @if(!$environments->contains('name', $envName))
                                <option value="{{ $envName }}">
                                @endif
                            @endforeach
                        </datalist>
                        <select name="version_id" class="text-xs border-gray-300 rounded-md">
                            @foreach($versions as $v)
                            <option value="{{ $v->id }}">v{{ $v->version_number }}</option>
                            @endforeach
                        </select>
                        <button type="submit" class="px-2 py-1 text-xs bg-green-600 text-white rounded hover:bg-green-700">Add</button>
                    </form>
                </div>
            </div>
            @endcan
            @endif
        </div>
    </div>
</x-app-layout>
