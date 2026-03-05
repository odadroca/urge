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
                            <th class="relative px-6 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @foreach($providers as $provider)
                        <tr class="hover:bg-gray-50">
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
                            <td class="px-6 py-4 text-right">
                                <a href="{{ route('admin.llm-providers.edit', $provider) }}" class="text-sm text-indigo-600 hover:underline">Configure</a>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-app-layout>
