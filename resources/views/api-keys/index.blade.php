<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">API Keys</h2>
            <a href="{{ route('api-keys.create') }}" class="inline-flex items-center px-4 py-2 bg-indigo-600 text-white text-sm rounded-md hover:bg-indigo-700">
                New API Key
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8">
            @if(session('new_key'))
            <div class="mb-6 bg-yellow-50 border border-yellow-300 text-yellow-900 px-5 py-4 rounded-lg" x-data="{ copied: false }">
                <p class="font-semibold mb-2">Your new API key (shown once — copy it now):</p>
                <div class="flex items-center gap-3">
                    <code class="flex-1 font-mono text-sm bg-white border border-yellow-200 rounded px-3 py-2 break-all">{{ session('new_key') }}</code>
                    <button @click="navigator.clipboard.writeText('{{ session('new_key') }}'); copied = true; setTimeout(() => copied = false, 2000)"
                        class="px-3 py-2 bg-yellow-600 text-white text-sm rounded hover:bg-yellow-700 whitespace-nowrap">
                        <span x-text="copied ? 'Copied!' : 'Copy'">Copy</span>
                    </button>
                </div>
            </div>
            @endif

            @if(session('success'))
                <div class="mb-4 bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded">{{ session('success') }}</div>
            @endif

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                @if($keys->isEmpty())
                <div class="p-12 text-center text-gray-500">
                    <p>No API keys yet.</p>
                    <a href="{{ route('api-keys.create') }}" class="mt-2 inline-flex text-sm text-indigo-600 hover:underline">Create one</a>
                </div>
                @else
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Name</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Key</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Scope</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Last Used</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Expires</th>
                            <th class="relative px-6 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @foreach($keys as $key)
                        <tr class="{{ $key->isExpired() ? 'opacity-60' : '' }}">
                            <td class="px-6 py-4 font-medium text-gray-800">
                                {{ $key->name }}
                                @if($key->isExpired())
                                <span class="ml-1 inline-flex items-center px-1.5 py-0.5 rounded text-xs bg-red-100 text-red-700">expired</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 font-mono text-sm text-gray-500">{{ $key->key_preview }}</td>
                            <td class="px-6 py-4 text-sm">
                                @if($key->prompts->isEmpty())
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-green-50 text-green-700 border border-green-200">All prompts</span>
                                @else
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-amber-50 text-amber-700 border border-amber-200">{{ $key->prompts->count() }} prompt{{ $key->prompts->count() !== 1 ? 's' : '' }}</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-500">{{ $key->last_used_at?->diffForHumans() ?? 'Never' }}</td>
                            <td class="px-6 py-4 text-sm text-gray-500">{{ $key->expires_at?->format('Y-m-d') ?? 'Never' }}</td>
                            <td class="px-6 py-4 text-right space-x-2">
                                @can('rotate', $key)
                                <form method="POST" action="{{ route('api-keys.rotate', $key) }}" class="inline" onsubmit="return confirm('Rotate this key? A new key will be generated and the old key will expire in {{ config('urge.key_rotation_overlap_hours') }} hours.')">
                                    @csrf
                                    <button type="submit" class="text-indigo-600 hover:underline text-sm">Rotate</button>
                                </form>
                                @endcan
                                @can('delete', $key)
                                <form method="POST" action="{{ route('api-keys.destroy', $key) }}" class="inline" onsubmit="return confirm('Revoke this key?')">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="text-red-600 hover:underline text-sm">Revoke</button>
                                </form>
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
