<x-app-layout>
    <x-slot name="header">
        <div>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">New Version</h2>
            <p class="text-sm text-gray-500 mt-0.5">
                <a href="{{ route('prompts.show', $prompt) }}" class="text-indigo-600 hover:underline">{{ $prompt->name }}</a>
            </p>
        </div>
    </x-slot>

    <div class="py-12" x-data="versionForm()">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <form method="POST" action="{{ route('prompts.versions.store', $prompt) }}">
                    @csrf
                    <div class="mb-5">
                        <label for="content" class="block text-sm font-medium text-gray-700 mb-1">
                            Prompt Content <span class="text-red-500">*</span>
                        </label>
                        <p class="text-xs text-gray-400 mb-2">Use <code class="font-mono bg-gray-100 px-1 rounded">&#123;&#123;variable_name&#125;&#125;</code> for placeholders. Use <code class="font-mono bg-gray-100 px-1 rounded">&#123;&#123;&gt;slug&#125;&#125;</code> to include another prompt. Variables and includes are detected automatically.</p>
                        <textarea name="content" id="content" rows="16" required x-model="content" @input="detectVariables()" @click="updateCursor()" @keyup="updateCursor()"
                            class="w-full font-mono text-sm border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 @error('content') border-red-300 @enderror"
                        >{{ old('content', $latest?->content) }}</textarea>
                        @error('content')<p class="mt-1 text-sm text-red-500">{{ $message }}</p>@enderror
                    </div>

                    {{-- Reference panels --}}
                    @if($allPrompts->isNotEmpty() || $knownVariables->isNotEmpty())
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 mb-5">

                        @if($allPrompts->isNotEmpty())
                        <div class="border border-emerald-200 dark:border-emerald-800 rounded-md bg-emerald-50 dark:bg-emerald-950/40 p-3">
                            <p class="text-xs font-medium text-emerald-700 dark:text-emerald-400 mb-2">Available includes <span class="font-normal text-emerald-500 dark:text-emerald-600">(click to insert)</span></p>
                            <div class="flex flex-wrap gap-1.5">
                                @foreach($allPrompts as $p)
                                <button type="button"
                                        @click="insert('{' + '{>' + @js($p->slug) + '}' + '}')"
                                        title="{{ $p->name }}"
                                        class="inline-flex items-center px-2 py-0.5 rounded text-xs font-mono bg-white dark:bg-slate-800 text-emerald-700 dark:text-emerald-400 border border-emerald-300 dark:border-emerald-700 hover:bg-emerald-100 dark:hover:bg-emerald-900/50 transition">
                                    <span x-text="'{' + '{>' + @js($p->slug) + '}' + '}'"></span>
                                </button>
                                @endforeach
                            </div>
                        </div>
                        @endif

                        @if($knownVariables->isNotEmpty())
                        <div class="border border-indigo-200 dark:border-indigo-800 rounded-md bg-indigo-50 dark:bg-indigo-950/40 p-3">
                            <p class="text-xs font-medium text-indigo-700 dark:text-indigo-400 mb-2">Known variables <span class="font-normal text-indigo-400 dark:text-indigo-600">(click to insert)</span></p>
                            <div class="flex flex-wrap gap-1.5">
                                @foreach($knownVariables as $var)
                                <button type="button"
                                        @click="insert('{' + '{' + @js($var) + '}' + '}')"
                                        class="inline-flex items-center px-2 py-0.5 rounded text-xs font-mono bg-white dark:bg-slate-800 text-indigo-700 dark:text-indigo-400 border border-indigo-300 dark:border-indigo-700 hover:bg-indigo-100 dark:hover:bg-indigo-900/50 transition">
                                    <span x-text="'{' + '{' + @js($var) + '}' + '}'"></span>
                                </button>
                                @endforeach
                            </div>
                        </div>
                        @endif

                    </div>
                    @endif

                    {{-- Detected includes --}}
                    <div class="mb-5" x-show="includes.length > 0" x-cloak>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Includes</label>
                        <div class="flex flex-wrap gap-1.5">
                            <template x-for="slug in includes" :key="slug">
                                <a :href="'/prompts/' + slug" target="_blank"
                                   class="inline-flex items-center px-2.5 py-1 rounded text-xs font-mono bg-emerald-50 text-emerald-700 border border-emerald-200 hover:bg-emerald-100 transition">
                                    <span x-text="'{' + '{>' + slug + '}' + '}'"></span>
                                </a>
                            </template>
                        </div>
                    </div>

                    {{-- Variable metadata --}}
                    <div class="mb-5" x-show="variables.length > 0" x-cloak>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Variable Metadata <span class="text-gray-400 font-normal">(optional)</span></label>
                        <div class="space-y-3 border border-gray-200 rounded-md p-4 bg-gray-50">
                            <template x-for="v in variables" :key="v">
                                <div class="grid grid-cols-12 gap-2 items-start">
                                    <div class="col-span-2">
                                        <span class="inline-flex items-center px-2 py-1 rounded text-xs font-mono bg-indigo-50 text-indigo-700 border border-indigo-200" x-text="'{' + '{' + v + '}' + '}'"></span>
                                    </div>
                                    <div class="col-span-2">
                                        <select :name="'variable_metadata[' + v + '][type]'" class="w-full text-xs border-gray-300 rounded-md">
                                            <option value="">Type...</option>
                                            <option value="string" :selected="getMetaValue(v, 'type') === 'string'">string</option>
                                            <option value="text" :selected="getMetaValue(v, 'type') === 'text'">text</option>
                                            <option value="enum" :selected="getMetaValue(v, 'type') === 'enum'">enum</option>
                                            <option value="number" :selected="getMetaValue(v, 'type') === 'number'">number</option>
                                            <option value="boolean" :selected="getMetaValue(v, 'type') === 'boolean'">boolean</option>
                                        </select>
                                    </div>
                                    <div class="col-span-3">
                                        <input type="text" :name="'variable_metadata[' + v + '][default]'" placeholder="Default value"
                                            :value="getMetaValue(v, 'default')"
                                            class="w-full text-xs border-gray-300 rounded-md">
                                    </div>
                                    <div class="col-span-5">
                                        <input type="text" :name="'variable_metadata[' + v + '][description]'" placeholder="Description"
                                            :value="getMetaValue(v, 'description')"
                                            class="w-full text-xs border-gray-300 rounded-md">
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>

                    <div class="mb-6">
                        <label for="commit_message" class="block text-sm font-medium text-gray-700 mb-1">Commit Message</label>
                        <input type="text" name="commit_message" id="commit_message" value="{{ old('commit_message') }}" maxlength="500"
                            placeholder="What changed in this version?"
                            class="w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                        @error('commit_message')<p class="mt-1 text-sm text-red-500">{{ $message }}</p>@enderror
                    </div>
                    <div class="flex justify-end gap-3">
                        <a href="{{ route('prompts.versions.index', $prompt) }}" class="px-4 py-2 text-sm text-gray-700 border border-gray-300 rounded-md hover:bg-gray-50">Cancel</a>
                        <button type="submit" class="px-4 py-2 text-sm bg-indigo-600 text-white rounded-md hover:bg-indigo-700">Save Version</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
    function versionForm() {
        const prevMetadata = @json($latest?->variable_metadata ?? (object)[]);
        return {
            content: @json(old('content', $latest?->content ?? '')),
            variables: [],
            includes: [],
            prevMetadata: prevMetadata || {},
            cursorPos: 0,
            init() {
                this.detectVariables();
            },
            detectVariables() {
                const varPattern = /\{\{([a-zA-Z_][a-zA-Z0-9_]*)\}\}/g;
                const inclPattern = /\{\{>([a-zA-Z0-9_-]+)\}\}/g;
                const foundVars = new Set();
                const foundIncl = new Set();
                let match;
                while ((match = varPattern.exec(this.content)) !== null) {
                    foundVars.add(match[1]);
                }
                while ((match = inclPattern.exec(this.content)) !== null) {
                    foundIncl.add(match[1]);
                }
                this.variables = [...foundVars];
                this.includes = [...foundIncl];
            },
            updateCursor() {
                const ta = document.getElementById('content');
                this.cursorPos = ta.selectionStart;
            },
            insert(text) {
                const ta = document.getElementById('content');
                const pos = this.cursorPos;
                this.content = this.content.slice(0, pos) + text + this.content.slice(pos);
                this.$nextTick(() => {
                    ta.focus();
                    const newPos = pos + text.length;
                    ta.setSelectionRange(newPos, newPos);
                    this.cursorPos = newPos;
                    this.detectVariables();
                });
            },
            getMetaValue(varName, field) {
                return this.prevMetadata[varName]?.[field] ?? '';
            }
        };
    }
    </script>
</x-app-layout>
