<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <div>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">New Version</h2>
                <p class="text-sm text-gray-500 mt-0.5">
                    <a href="{{ route('prompts.show', $prompt) }}" class="text-indigo-600 hover:underline">{{ $prompt->name }}</a>
                </p>
            </div>
            <a href="{{ route('prompts.versions.designer', $prompt) }}" class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 text-gray-700 text-sm rounded-md hover:bg-gray-50">
                Full Designer
            </a>
        </div>
    </x-slot>

    <div class="py-12" x-data="versionForm()">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <form method="POST" action="{{ route('prompts.versions.store', $prompt) }}">
                    @csrf
                    <div class="mb-5">
                        <div class="flex items-center justify-between mb-1">
                            <label for="content" class="block text-sm font-medium text-gray-700">
                                Prompt Content <span class="text-red-500">*</span>
                            </label>
                            {{-- Editor mode toggle --}}
                            <div class="inline-flex rounded-md shadow-sm">
                                <button type="button" @click="switchMode('text')"
                                        class="px-3 py-1 text-xs font-medium rounded-l-md border transition"
                                        :class="editorMode === 'text' ? 'bg-indigo-600 text-white border-indigo-600' : 'bg-white text-gray-600 border-gray-300 hover:bg-gray-50'">
                                    Text
                                </button>
                                <button type="button" @click="switchMode('visual')"
                                        class="px-3 py-1 text-xs font-medium rounded-r-md border-t border-r border-b transition"
                                        :class="editorMode === 'visual' ? 'bg-indigo-600 text-white border-indigo-600' : 'bg-white text-gray-600 border-gray-300 hover:bg-gray-50'">
                                    Visual
                                </button>
                            </div>
                        </div>
                        <p class="text-xs text-gray-400 mb-2" x-show="editorMode === 'text'">Use <code class="font-mono bg-gray-100 px-1 rounded">&#123;&#123;variable_name&#125;&#125;</code> for placeholders. Use <code class="font-mono bg-gray-100 px-1 rounded">&#123;&#123;&gt;slug&#125;&#125;</code> to include another prompt. Type <code class="font-mono bg-gray-100 px-1 rounded">&#123;&#123;</code> for autocomplete suggestions.</p>
                        <p class="text-xs text-gray-400 mb-2" x-show="editorMode === 'visual'" x-cloak>Drag blocks to reorder. Click variables or includes from the reference panels below to add them.</p>

                        {{-- Hidden input to always carry content --}}
                        <input type="hidden" name="content" :value="content">

                        {{-- TEXT MODE --}}
                        <div x-show="editorMode === 'text'">
                            <div class="relative autocomplete-wrapper" x-data="autocomplete()">
                                <textarea id="content" rows="16" x-model="content" x-ref="editor"
                                    @input="detectVariables(); handleInput($event)"
                                    @keydown="handleKeydown($event)"
                                    @click="updateCursor(); dismiss()"
                                    @blur="setTimeout(() => dismiss(), 200)"
                                    class="w-full font-mono text-sm border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 @error('content') border-red-300 @enderror"
                                ></textarea>

                                {{-- Autocomplete dropdown --}}
                                <div x-ref="autocompleteDropdown"
                                     x-show="showDropdown" x-cloak
                                     class="absolute z-50 w-64 bg-white border border-gray-200 rounded-lg shadow-lg overflow-hidden"
                                     style="max-height: 240px;">
                                    <template x-for="(suggestion, idx) in suggestions" :key="suggestion.value">
                                        <button type="button"
                                                @click="selectSuggestion(idx)"
                                                @mouseenter="selectedIndex = idx"
                                                class="w-full text-left px-3 py-2 text-sm flex items-center justify-between hover:bg-indigo-50 transition-colors"
                                                :class="idx === selectedIndex ? 'bg-indigo-50 text-indigo-700' : 'text-gray-700'">
                                            <span class="font-mono font-medium" x-text="suggestion.label"></span>
                                            <span class="text-xs text-gray-400 truncate ml-2 max-w-[8rem]" x-text="suggestion.description"></span>
                                        </button>
                                    </template>
                                </div>
                            </div>
                        </div>

                        {{-- VISUAL MODE --}}
                        <div x-show="editorMode === 'visual'" x-cloak>
                            <div class="border border-gray-200 rounded-md bg-gray-50 min-h-[16rem]">
                                {{-- Composer toolbar --}}
                                <div class="px-3 py-2 border-b border-gray-200 flex items-center justify-between bg-white rounded-t-md">
                                    <span class="text-xs text-gray-400" x-text="composerBlocks.length + ' block' + (composerBlocks.length !== 1 ? 's' : '')"></span>
                                    <button type="button" @click="addComposerTextBlock()"
                                            class="inline-flex items-center gap-1 px-2.5 py-1 text-xs bg-gray-100 text-gray-600 rounded-md hover:bg-gray-200 transition">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
                                        Text Block
                                    </button>
                                </div>

                                {{-- Blocks --}}
                                <div x-ref="composerCanvas" class="p-3 space-y-2">
                                    <template x-if="composerBlocks.length === 0">
                                        <div class="text-center text-gray-400 py-8">
                                            <p class="text-sm">No blocks yet. Add text, variables, or includes.</p>
                                        </div>
                                    </template>
                                    <template x-for="(block, index) in composerBlocks" :key="block.id">
                                        <div class="composer-block group rounded-md border flex items-start gap-2 p-2 transition-shadow hover:shadow-sm"
                                             :class="{
                                                 'border-gray-200 bg-white': block.type === 'text',
                                                 'border-indigo-200 bg-indigo-50': block.type === 'variable',
                                                 'border-emerald-200 bg-emerald-50': block.type === 'include',
                                             }">
                                            {{-- Drag handle --}}
                                            <div class="composer-handle cursor-grab active:cursor-grabbing flex-shrink-0 mt-1 text-gray-300 hover:text-gray-500">
                                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
                                                    <circle cx="9" cy="5" r="1.5"/><circle cx="15" cy="5" r="1.5"/>
                                                    <circle cx="9" cy="12" r="1.5"/><circle cx="15" cy="12" r="1.5"/>
                                                    <circle cx="9" cy="19" r="1.5"/><circle cx="15" cy="19" r="1.5"/>
                                                </svg>
                                            </div>

                                            {{-- Block content --}}
                                            <div class="flex-1 min-w-0">
                                                <template x-if="block.type === 'text'">
                                                    <textarea x-model="block.content"
                                                              @input="syncComposerToContent(); autoResizeComposer($event.target)"
                                                              x-init="$nextTick(() => autoResizeComposer($el))"
                                                              placeholder="Type text..."
                                                              rows="1"
                                                              class="w-full text-sm font-mono border-gray-200 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 resize-none overflow-hidden"></textarea>
                                                </template>
                                                <template x-if="block.type === 'variable'">
                                                    <span class="inline-flex items-center px-2.5 py-1 rounded text-xs font-mono font-medium bg-white text-indigo-700 border border-indigo-300" x-text="block.token"></span>
                                                </template>
                                                <template x-if="block.type === 'include'">
                                                    <span class="inline-flex items-center px-2.5 py-1 rounded text-xs font-mono font-medium bg-white text-emerald-700 border border-emerald-300" x-text="block.token"></span>
                                                </template>
                                            </div>

                                            {{-- Delete --}}
                                            <button type="button" @click="removeComposerBlock(block.id)"
                                                    class="flex-shrink-0 mt-1 p-0.5 text-gray-300 hover:text-red-500 opacity-0 group-hover:opacity-100 transition">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                                                </svg>
                                            </button>
                                        </div>
                                    </template>
                                </div>
                            </div>
                        </div>

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

                    {{-- Variable metadata (collapsible) --}}
                    <div class="mb-5" x-show="variables.length > 0" x-cloak x-data="{ metaOpen: Object.keys(prevMetadata).length > 0 }">
                        <button type="button" @click="metaOpen = !metaOpen"
                                class="flex items-center gap-2 text-sm font-medium text-gray-700 hover:text-gray-900 transition mb-2">
                            <svg class="w-4 h-4 transition-transform" :class="metaOpen ? 'rotate-90' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
                            </svg>
                            Variable Metadata
                            <span class="text-gray-400 font-normal">(optional)</span>
                            <span class="inline-flex items-center px-1.5 py-0.5 rounded-full text-[10px] font-medium bg-indigo-100 text-indigo-700" x-text="variables.length"></span>
                        </button>
                        <div x-show="metaOpen" x-cloak class="space-y-3 border border-gray-200 rounded-md p-4 bg-gray-50">
                            <template x-for="v in variables" :key="v">
                                <div class="space-y-2 pb-3 border-b border-gray-200 last:border-0 last:pb-0">
                                    <div class="grid grid-cols-12 gap-2 items-start">
                                        <div class="col-span-2">
                                            <span class="inline-flex items-center px-2 py-1 rounded text-xs font-mono bg-indigo-50 text-indigo-700 border border-indigo-200" x-text="'{' + '{' + v + '}' + '}'"></span>
                                        </div>
                                        <div class="col-span-2">
                                            <select :name="'variable_metadata[' + v + '][type]'"
                                                    @change="setMetaType(v, $event.target.value)"
                                                    class="w-full text-xs border-gray-300 rounded-md">
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
                                    {{-- Enum options row (shown only when type is enum) --}}
                                    <div x-show="getMetaType(v) === 'enum'" class="ml-[16.6667%] col-span-10">
                                        <div class="flex items-center gap-2">
                                            <label class="text-[10px] font-medium text-gray-500 uppercase tracking-wider whitespace-nowrap">Options:</label>
                                            <input type="text"
                                                   :name="'variable_metadata[' + v + '][options_csv]'"
                                                   :value="getMetaOptions(v)"
                                                   placeholder="option1, option2, option3"
                                                   class="flex-1 text-xs border-gray-300 rounded-md font-mono">
                                        </div>
                                        <p class="text-[10px] text-gray-400 mt-0.5 ml-[calc(3.5rem)]">Comma-separated list of allowed values</p>
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
            editorMode: 'text',
            variables: [],
            includes: [],
            prevMetadata: prevMetadata || {},
            metaTypes: {},
            cursorPos: 0,
            composerBlocks: [],
            composerNextId: 1,
            composerSortable: null,
            init() {
                if (this.prevMetadata) {
                    Object.keys(this.prevMetadata).forEach(v => {
                        if (this.prevMetadata[v]?.type) {
                            this.metaTypes[v] = this.prevMetadata[v].type;
                        }
                    });
                }
                this.detectVariables();
            },
            switchMode(mode) {
                if (mode === this.editorMode) return;
                if (mode === 'visual') {
                    this.parseContentToBlocks();
                    this.editorMode = 'visual';
                    this.$nextTick(() => this.initComposerSortable());
                } else {
                    this.content = this.serializeBlocks();
                    this.editorMode = 'text';
                    this.detectVariables();
                }
            },
            parseContentToBlocks() {
                const pattern = /(\{\{>[a-zA-Z0-9_-]+\}\}|\{\{[a-zA-Z_][a-zA-Z0-9_]*\}\})/;
                const parts = this.content.split(pattern);
                this.composerBlocks = [];
                this.composerNextId = 1;
                for (const part of parts) {
                    if (part === '') continue;
                    const inclMatch = part.match(/^\{\{>([a-zA-Z0-9_-]+)\}\}$/);
                    const varMatch = part.match(/^\{\{([a-zA-Z_][a-zA-Z0-9_]*)\}\}$/);
                    if (inclMatch) {
                        this.composerBlocks.push({ id: this.composerNextId++, type: 'include', slug: inclMatch[1], token: part });
                    } else if (varMatch) {
                        this.composerBlocks.push({ id: this.composerNextId++, type: 'variable', name: varMatch[1], token: part });
                    } else {
                        this.composerBlocks.push({ id: this.composerNextId++, type: 'text', content: part });
                    }
                }
            },
            serializeBlocks() {
                return this.composerBlocks.map(b => {
                    if (b.type === 'text') return b.content || '';
                    return b.token;
                }).join('');
            },
            syncComposerToContent() {
                this.content = this.serializeBlocks();
                this.detectVariables();
            },
            initComposerSortable() {
                const canvas = this.$refs.composerCanvas;
                if (!canvas || !window.Sortable) return;
                if (this.composerSortable) this.composerSortable.destroy();
                this.composerSortable = new Sortable(canvas, {
                    animation: 150,
                    handle: '.composer-handle',
                    ghostClass: 'opacity-30',
                    draggable: '.composer-block',
                    onEnd: (evt) => {
                        const moved = this.composerBlocks.splice(evt.oldIndex, 1)[0];
                        this.composerBlocks.splice(evt.newIndex, 0, moved);
                        this.syncComposerToContent();
                    },
                });
            },
            addComposerTextBlock() {
                this.composerBlocks.push({ id: this.composerNextId++, type: 'text', content: '' });
            },
            addComposerVariable(name) {
                this.composerBlocks.push({
                    id: this.composerNextId++,
                    type: 'variable',
                    name: name,
                    token: '{' + '{' + name + '}' + '}',
                });
                this.syncComposerToContent();
            },
            addComposerInclude(slug) {
                this.composerBlocks.push({
                    id: this.composerNextId++,
                    type: 'include',
                    slug: slug,
                    token: '{' + '{>' + slug + '}' + '}',
                });
                this.syncComposerToContent();
            },
            removeComposerBlock(blockId) {
                this.composerBlocks = this.composerBlocks.filter(b => b.id !== blockId);
                this.syncComposerToContent();
            },
            autoResizeComposer(el) {
                el.style.height = 'auto';
                el.style.height = el.scrollHeight + 'px';
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
                if (ta) this.cursorPos = ta.selectionStart;
            },
            insert(text) {
                if (this.editorMode === 'visual') {
                    // In visual mode, add as a block instead
                    const inclMatch = text.match(/^\{\{>([a-zA-Z0-9_-]+)\}\}$/);
                    const varMatch = text.match(/^\{\{([a-zA-Z_][a-zA-Z0-9_]*)\}\}$/);
                    if (inclMatch) {
                        this.addComposerInclude(inclMatch[1]);
                    } else if (varMatch) {
                        this.addComposerVariable(varMatch[1]);
                    }
                    return;
                }
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
            },
            getMetaType(varName) {
                return this.metaTypes[varName] || this.getMetaValue(varName, 'type') || '';
            },
            setMetaType(varName, type) {
                this.metaTypes[varName] = type;
            },
            getMetaOptions(varName) {
                const opts = this.prevMetadata[varName]?.options;
                if (Array.isArray(opts)) return opts.join(', ');
                return '';
            },
        };
    }
    </script>
</x-app-layout>
