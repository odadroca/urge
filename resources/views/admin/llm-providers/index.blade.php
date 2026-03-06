<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">LLM Providers</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            @if(session('success'))
            <div class="mb-4 bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded">{{ session('success') }}</div>
            @endif

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="px-6 py-4 border-b border-gray-100">
                    <p class="text-sm text-gray-500">Configure LLM providers for the execute feature. API keys are stored encrypted. Enable a provider to make it available when running prompts.</p>
                </div>
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Provider</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Model</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">API Key</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Configure</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Test</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @foreach($providers as $provider)
                        <tr class="hover:bg-gray-50"
                            x-data="{
                                status: null,
                                loading: false,
                                async test() {
                                    this.loading = true;
                                    this.status = null;
                                    try {
                                        const res = await fetch('{{ route('admin.llm-providers.test', $provider) }}', {
                                            method: 'POST',
                                            headers: {
                                                'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                                                'Accept': 'application/json',
                                            },
                                        });
                                        const data = await res.json();
                                        this.status = data;
                                    } catch (e) {
                                        this.status = { success: false, message: 'Network error', duration_ms: 0 };
                                    } finally {
                                        this.loading = false;
                                    }
                                }
                            }">
                            <td class="px-6 py-4">
                                <span class="font-medium text-gray-800">{{ $provider->name }}</span>
                                <span class="ml-2 text-xs text-gray-400 font-mono">{{ $provider->driver }}</span>
                            </td>
                            <td class="px-6 py-4 text-sm font-mono text-gray-600">{{ $provider->model }}</td>
                            <td class="px-6 py-4 text-sm">
                                @if($provider->isOllama())
                                <span class="text-gray-400 text-xs">no key needed</span>
                                @elseif($provider->api_key_encrypted)
                                <span class="font-mono text-xs text-gray-500">{{ $provider->keyPreview() }}</span>
                                @else
                                <span class="text-red-400 text-xs">not set</span>
                                @endif
                            </td>
                            <td class="px-6 py-4">
                                @if($provider->enabled)
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">enabled</span>
                                @else
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-500">disabled</span>
                                @endif
                            </td>
                            <td class="px-6 py-4">
                                <a href="{{ route('admin.llm-providers.edit', $provider) }}" class="text-sm text-indigo-600 hover:underline">Configure</a>
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-2">
                                    <button @click="test()"
                                            :disabled="loading"
                                            class="inline-flex items-center gap-1 px-2.5 py-1 text-xs font-medium border rounded-md transition
                                                   bg-white text-gray-600 border-gray-300 hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed">
                                        <svg x-show="!loading" class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                                        </svg>
                                        <svg x-show="loading" class="w-3 h-3 animate-spin" fill="none" viewBox="0 0 24 24">
                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"/>
                                        </svg>
                                        <span x-text="loading ? 'Testing…' : 'Test'"></span>
                                    </button>

                                    <template x-if="status !== null">
                                        <span class="inline-flex items-center gap-1 text-xs"
                                              :class="status.success ? 'text-green-700' : 'text-red-600'">
                                            <span x-text="status.success ? '✓' : '✗'"></span>
                                            <span x-text="status.success ? status.message + ' (' + status.duration_ms + 'ms)' : status.message" class="max-w-[160px] truncate" :title="status.message"></span>
                                        </span>
                                    </template>
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-app-layout>
