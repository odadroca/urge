<x-app-layout>
    <x-slot name="header">
        <div>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Configure Provider</h2>
            <p class="text-sm text-gray-500 mt-0.5">
                <a href="{{ route('admin.llm-providers.index') }}" class="text-indigo-600 hover:underline">LLM Providers</a>
                &rsaquo; {{ $provider->name }}
            </p>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                <form method="POST" action="{{ route('admin.llm-providers.update', $provider) }}">
                    @csrf
                    @method('PUT')

                    <div class="space-y-5">
                        <div>
                            <label for="name" class="block text-sm font-medium text-gray-700 mb-1">Display Name</label>
                            <input type="text" name="name" id="name"
                                   value="{{ old('name', $provider->name) }}" required
                                   class="w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                            @error('name')<p class="mt-1 text-sm text-red-500">{{ $message }}</p>@enderror
                        </div>

                        <div>
                            <label for="model" class="block text-sm font-medium text-gray-700 mb-1">Model</label>
                            <input type="text" name="model" id="model"
                                   value="{{ old('model', $provider->model) }}" required
                                   class="w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 font-mono text-sm"
                                   placeholder="{{ match($provider->driver) {
                                       'openai' => 'e.g. gpt-4o, gpt-4o-mini',
                                       'anthropic' => 'e.g. claude-opus-4-6, claude-haiku-4-5-20251001',
                                       'mistral' => 'e.g. mistral-small-latest, mistral-large-latest',
                                       'gemini' => 'e.g. gemini-1.5-flash, gemini-1.5-pro',
                                       'ollama' => 'e.g. llama3.2, mistral, phi3',
                                       'openrouter' => 'e.g. openai/gpt-4o-mini, anthropic/claude-haiku-4-5-20251001, google/gemini-flash-1.5',
                                       default => ''
                                   } }}">
                            @error('model')<p class="mt-1 text-sm text-red-500">{{ $message }}</p>@enderror
                        </div>

                        @if($provider->isOllama())
                        <div>
                            <label for="base_url" class="block text-sm font-medium text-gray-700 mb-1">Base URL</label>
                            <input type="text" name="base_url" id="base_url"
                                   value="{{ old('base_url', $provider->base_url ?? 'http://localhost:11434') }}"
                                   class="w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 font-mono text-sm">
                            <p class="mt-1 text-xs text-gray-400">URL of your Ollama instance. Default: <code class="font-mono">http://localhost:11434</code></p>
                            @error('base_url')<p class="mt-1 text-sm text-red-500">{{ $message }}</p>@enderror
                        </div>
                        @else
                        <div>
                            <label for="api_key" class="block text-sm font-medium text-gray-700 mb-1">API Key</label>
                            <input type="password" name="api_key" id="api_key"
                                   value="" autocomplete="new-password"
                                   placeholder="{{ $provider->api_key_encrypted ? 'Leave blank to keep existing key' : 'Enter API key' }}"
                                   class="w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 font-mono text-sm">
                            @if($provider->api_key_encrypted)
                            <p class="mt-1 text-xs text-gray-400">Current key: <span class="font-mono">{{ $provider->keyPreview() }}</span>. Leave blank to keep it unchanged.</p>
                            @endif
                            @error('api_key')<p class="mt-1 text-sm text-red-500">{{ $message }}</p>@enderror
                        </div>
                        @endif

                        <div class="flex items-center gap-3 pt-1">
                            <input type="hidden" name="enabled" value="0">
                            <input type="checkbox" name="enabled" id="enabled" value="1"
                                   {{ old('enabled', $provider->enabled) ? 'checked' : '' }}
                                   class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                            <label for="enabled" class="text-sm font-medium text-gray-700">Enable this provider</label>
                        </div>
                    </div>

                    <div class="flex justify-end gap-3 mt-6">
                        <a href="{{ route('admin.llm-providers.index') }}" class="px-4 py-2 text-sm text-gray-700 border border-gray-300 rounded-md hover:bg-gray-50">Cancel</a>
                        <button type="submit" class="px-4 py-2 text-sm bg-indigo-600 text-white rounded-md hover:bg-indigo-700">Save</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
