<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <div>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">Visual Designer</h2>
                <p class="text-sm text-gray-500 mt-0.5">
                    <a href="{{ route('prompts.show', $prompt) }}" class="text-indigo-600 hover:underline">{{ $prompt->name }}</a>
                    @isset($promptVersion)
                        &rsaquo; based on v{{ $promptVersion->version_number }}
                    @endisset
                </p>
            </div>
            <a href="{{ route('prompts.versions.create', $prompt) }}" class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 text-gray-700 text-sm rounded-md hover:bg-gray-50">
                Switch to Text Editor
            </a>
        </div>
    </x-slot>

    <div class="py-6" x-data="designerForm()">
        <form method="POST" action="{{ route('prompts.versions.designer.store', $prompt) }}" @submit.prevent="submitForm($event)">
            @csrf
            <input type="hidden" name="content" :value="assembledContent">

            <div class="max-w-full mx-auto px-4 sm:px-6 lg:px-8">
                {{-- Three-panel layout --}}
                <div class="flex gap-4" style="height: calc(100vh - 14rem);">

                    {{-- Left: Palette --}}
                    <div class="w-72 flex-shrink-0 bg-white shadow-sm rounded-lg p-4 overflow-y-auto">
                        <h3 class="text-sm font-semibold text-gray-700 mb-3">Palette</h3>
                        <p class="text-xs text-gray-400 mb-4">Drag items to the canvas, or click to add.</p>

                        {{-- Variables --}}
                        <div class="mb-5">
                            <p class="text-xs font-medium text-indigo-700 mb-2 flex items-center gap-1.5">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M7 20l4-16m2 16l4-16M6 9h14M4 15h14"/></svg>
                                Variables
                            </p>
                            <div x-ref="paletteVars" class="flex flex-wrap gap-1.5">
                                @foreach($knownVariables as $var)
                                <div data-block-type="variable" data-name="{{ $var }}"
                                     @click="addVariable('{{ $var }}')"
                                     class="cursor-grab active:cursor-grabbing inline-flex items-center px-2 py-1 rounded text-xs font-mono bg-indigo-50 text-indigo-700 border border-indigo-200 hover:bg-indigo-100 transition select-none">
                                    &#123;&#123;{{ $var }}&#125;&#125;
                                </div>
                                @endforeach
                            </div>
                            @if($knownVariables->isEmpty())
                            <p class="text-xs text-gray-400 italic">No known variables yet.</p>
                            @endif
                        </div>

                        {{-- New custom variable --}}
                        <div class="mb-5">
                            <div class="flex gap-1.5">
                                <input type="text" x-model="newVarName" placeholder="new_variable"
                                       @keydown.enter.prevent="addCustomVariable()"
                                       class="flex-1 text-xs border-gray-300 rounded-md font-mono px-2 py-1 focus:ring-indigo-500 focus:border-indigo-500">
                                <button type="button" @click="addCustomVariable()"
                                        class="px-2 py-1 text-xs bg-indigo-600 text-white rounded-md hover:bg-indigo-700">+</button>
                            </div>
                        </div>

                        {{-- Includes --}}
                        <div>
                            <p class="text-xs font-medium text-emerald-700 mb-2 flex items-center gap-1.5">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/></svg>
                                Includes
                            </p>
                            <div x-ref="paletteIncludes" class="flex flex-col gap-1.5">
                                @foreach($allPrompts as $p)
                                <div data-block-type="include" data-slug="{{ $p->slug }}" data-name="{{ $p->name }}"
                                     @click="addInclude('{{ $p->slug }}', '{{ addslashes($p->name) }}')"
                                     class="cursor-grab active:cursor-grabbing inline-flex items-center gap-1.5 px-2 py-1 rounded text-xs font-mono bg-emerald-50 text-emerald-700 border border-emerald-200 hover:bg-emerald-100 transition select-none">
                                    <span>&#123;&#123;&gt;{{ $p->slug }}&#125;&#125;</span>
                                    <span class="text-emerald-500 font-sans text-[10px] truncate max-w-[8rem]">{{ $p->name }}</span>
                                </div>
                                @endforeach
                            </div>
                            @if($allPrompts->isEmpty())
                            <p class="text-xs text-gray-400 italic">No other prompts available.</p>
                            @endif
                        </div>
                    </div>

                    {{-- Center: Canvas --}}
                    <div class="flex-1 flex flex-col bg-white shadow-sm rounded-lg overflow-hidden">
                        <div class="px-4 py-3 border-b border-gray-100 flex items-center justify-between">
                            <h3 class="text-sm font-semibold text-gray-700">Canvas</h3>
                            <div class="flex items-center gap-2">
                                <span class="text-xs text-gray-400" x-text="blocks.length + ' block' + (blocks.length !== 1 ? 's' : '')"></span>
                                <button type="button" @click="addTextBlock()"
                                        class="inline-flex items-center gap-1 px-2.5 py-1 text-xs bg-gray-100 text-gray-600 rounded-md hover:bg-gray-200 transition">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
                                    Text Block
                                </button>
                            </div>
                        </div>

                        <div x-ref="canvas" class="flex-1 overflow-y-auto p-4 space-y-2" :class="blocks.length === 0 ? 'flex items-center justify-center' : ''">
                            {{-- Empty state --}}
                            <template x-if="blocks.length === 0">
                                <div class="text-center text-gray-400">
                                    <svg class="w-12 h-12 mx-auto mb-3 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m3.75 9v6m3-3H9m1.5-12H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z"/>
                                    </svg>
                                    <p class="text-sm font-medium">Drop blocks here</p>
                                    <p class="text-xs mt-1">Drag from the palette or click items to add them</p>
                                </div>
                            </template>

                            {{-- Blocks --}}
                            <template x-for="(block, index) in blocks" :key="block.id">
                                <div class="designer-block group rounded-lg border transition-shadow hover:shadow-sm"
                                     :data-block-id="block.id"
                                     :class="{
                                         'border-gray-200 bg-white': block.type === 'text',
                                         'border-indigo-200 bg-indigo-50': block.type === 'variable',
                                         'border-emerald-200 bg-emerald-50': block.type === 'include',
                                     }">
                                    <div class="flex items-start gap-2 p-2">
                                        {{-- Drag handle --}}
                                        <div class="drag-handle cursor-grab active:cursor-grabbing flex-shrink-0 mt-1 text-gray-300 hover:text-gray-500 transition">
                                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
                                                <circle cx="9" cy="5" r="1.5"/><circle cx="15" cy="5" r="1.5"/>
                                                <circle cx="9" cy="12" r="1.5"/><circle cx="15" cy="12" r="1.5"/>
                                                <circle cx="9" cy="19" r="1.5"/><circle cx="15" cy="19" r="1.5"/>
                                            </svg>
                                        </div>

                                        {{-- Block content --}}
                                        <div class="flex-1 min-w-0">
                                            {{-- Text block --}}
                                            <template x-if="block.type === 'text'">
                                                <div>
                                                    <span class="text-[10px] font-medium text-gray-400 uppercase tracking-wider">Text</span>
                                                    <textarea x-model="block.content"
                                                              @input="autoResize($event.target)"
                                                              x-init="$nextTick(() => autoResize($el))"
                                                              placeholder="Type your prompt text here..."
                                                              rows="2"
                                                              class="w-full mt-1 text-sm font-mono border-gray-200 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 resize-none overflow-hidden"></textarea>
                                                </div>
                                            </template>

                                            {{-- Variable block --}}
                                            <template x-if="block.type === 'variable'">
                                                <div class="flex items-center gap-2 py-1">
                                                    <span class="inline-flex items-center px-2.5 py-1 rounded text-xs font-mono font-medium bg-white text-indigo-700 border border-indigo-300" x-text="block.token"></span>
                                                    <span class="text-[10px] text-indigo-400 uppercase tracking-wider">Variable</span>
                                                </div>
                                            </template>

                                            {{-- Include block --}}
                                            <template x-if="block.type === 'include'">
                                                <div class="flex items-center gap-2 py-1">
                                                    <span class="inline-flex items-center px-2.5 py-1 rounded text-xs font-mono font-medium bg-white text-emerald-700 border border-emerald-300" x-text="block.token"></span>
                                                    <span class="text-[10px] text-emerald-400 uppercase tracking-wider">Include</span>
                                                </div>
                                            </template>
                                        </div>

                                        {{-- Delete button --}}
                                        <button type="button" @click="removeBlock(block.id)"
                                                class="flex-shrink-0 mt-1 p-0.5 text-gray-300 hover:text-red-500 opacity-0 group-hover:opacity-100 transition">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                                            </svg>
                                        </button>
                                    </div>
                                </div>
                            </template>
                        </div>

                        {{-- Add block buttons at bottom --}}
                        <div class="px-4 py-2 border-t border-gray-100 flex items-center gap-2">
                            <button type="button" @click="addTextBlock()"
                                    class="inline-flex items-center gap-1 px-2.5 py-1.5 text-xs bg-gray-50 text-gray-600 border border-gray-200 rounded-md hover:bg-gray-100 transition">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
                                Add Text Block
                            </button>
                            <span class="text-xs text-gray-300">|</span>
                            <button type="button" @click="clearAll()" x-show="blocks.length > 0"
                                    class="inline-flex items-center gap-1 px-2.5 py-1.5 text-xs text-red-500 hover:text-red-700 transition">
                                Clear All
                            </button>
                        </div>
                    </div>

                    {{-- Right: Preview --}}
                    <div class="w-80 flex-shrink-0 bg-white shadow-sm rounded-lg flex flex-col overflow-hidden">
                        <div class="px-4 py-3 border-b border-gray-100 flex items-center justify-between">
                            <h3 class="text-sm font-semibold text-gray-700">Preview</h3>
                            <button type="button" x-data="{ copied: false }"
                                    @click="navigator.clipboard.writeText(assembledContent); copied = true; setTimeout(() => copied = false, 2000)"
                                    class="inline-flex items-center gap-1 px-2 py-1 text-xs rounded border transition"
                                    :class="copied ? 'bg-green-50 border-green-300 text-green-700' : 'bg-white border-gray-200 text-gray-400 hover:text-gray-600'">
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                    <rect x="9" y="9" width="13" height="13" rx="2" stroke-linecap="round"/>
                                    <path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1" stroke-linecap="round"/>
                                </svg>
                                <span x-text="copied ? 'Copied!' : 'Copy'"></span>
                            </button>
                        </div>
                        <div class="flex-1 overflow-y-auto p-4">
                            <template x-if="assembledContent.length === 0">
                                <p class="text-xs text-gray-400 italic">Assembled prompt will appear here...</p>
                            </template>
                            <pre x-show="assembledContent.length > 0" class="text-sm font-mono text-gray-800 whitespace-pre-wrap break-words" x-text="assembledContent"></pre>
                        </div>

                        {{-- Detected elements --}}
                        <div class="px-4 py-3 border-t border-gray-100 space-y-2">
                            <div x-show="detectedVariables.length > 0">
                                <p class="text-[10px] font-medium text-gray-400 uppercase tracking-wider mb-1">Variables</p>
                                <div class="flex flex-wrap gap-1">
                                    <template x-for="v in detectedVariables" :key="v">
                                        <span class="px-1.5 py-0.5 rounded text-[10px] font-mono bg-indigo-50 text-indigo-600 border border-indigo-200" x-text="'{{' + v + '}}'"></span>
                                    </template>
                                </div>
                            </div>
                            <div x-show="detectedIncludes.length > 0">
                                <p class="text-[10px] font-medium text-gray-400 uppercase tracking-wider mb-1">Includes</p>
                                <div class="flex flex-wrap gap-1">
                                    <template x-for="s in detectedIncludes" :key="s">
                                        <span class="px-1.5 py-0.5 rounded text-[10px] font-mono bg-emerald-50 text-emerald-600 border border-emerald-200" x-text="'{{>' + s + '}}'"></span>
                                    </template>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Bottom: Metadata + Save --}}
                <div class="mt-4 bg-white shadow-sm rounded-lg p-6">
                    {{-- Variable metadata (collapsible) --}}
                    <div x-show="detectedVariables.length > 0" x-data="{ metaOpen: false }" class="mb-5">
                        <button type="button" @click="metaOpen = !metaOpen"
                                class="flex items-center gap-2 text-sm font-medium text-gray-700 hover:text-gray-900 transition">
                            <svg class="w-4 h-4 transition-transform" :class="metaOpen ? 'rotate-90' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
                            </svg>
                            Variable Metadata
                            <span class="text-gray-400 font-normal">(optional)</span>
                        </button>
                        <div x-show="metaOpen" x-cloak class="mt-3 space-y-3 border border-gray-200 rounded-md p-4 bg-gray-50">
                            <template x-for="v in detectedVariables" :key="v">
                                <div class="grid grid-cols-12 gap-2 items-start">
                                    <div class="col-span-2">
                                        <span class="inline-flex items-center px-2 py-1 rounded text-xs font-mono bg-indigo-50 text-indigo-700 border border-indigo-200" x-text="'{{' + v + '}}'"></span>
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

                    <div class="flex items-end gap-4">
                        <div class="flex-1">
                            <label for="commit_message" class="block text-sm font-medium text-gray-700 mb-1">Commit Message</label>
                            <input type="text" name="commit_message" id="commit_message" maxlength="500"
                                   placeholder="What changed in this version?"
                                   class="w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                        </div>
                        <div class="flex gap-3">
                            <a href="{{ route('prompts.show', $prompt) }}"
                               class="px-4 py-2 text-sm text-gray-700 border border-gray-300 rounded-md hover:bg-gray-50">Cancel</a>
                            <button type="submit" :disabled="assembledContent.length === 0"
                                    class="px-4 py-2 text-sm bg-indigo-600 text-white rounded-md hover:bg-indigo-700 disabled:opacity-40 disabled:cursor-not-allowed">
                                Save Version
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>

    <script>
    function designerForm() {
        const initialBlocks = @json($initialBlocks);
        const previousMetadata = @json($previousMetadata);

        return {
            blocks: initialBlocks.map(b => ({...b})),
            nextId: (initialBlocks.length || 0) + 1,
            newVarName: '',
            prevMetadata: previousMetadata || {},

            get assembledContent() {
                return this.blocks.map(b => {
                    if (b.type === 'text') return b.content || '';
                    if (b.type === 'variable') return b.token;
                    if (b.type === 'include') return b.token;
                    return '';
                }).join('');
            },

            get detectedVariables() {
                const pattern = /\{\{([a-zA-Z_][a-zA-Z0-9_]*)\}\}/g;
                const found = new Set();
                let match;
                const content = this.assembledContent;
                while ((match = pattern.exec(content)) !== null) {
                    found.add(match[1]);
                }
                return [...found];
            },

            get detectedIncludes() {
                const pattern = /\{\{>([a-zA-Z0-9_-]+)\}\}/g;
                const found = new Set();
                let match;
                const content = this.assembledContent;
                while ((match = pattern.exec(content)) !== null) {
                    found.add(match[1]);
                }
                return [...found];
            },

            canvasSortable: null,
            paletteSortableVars: null,
            paletteSortableIncludes: null,

            init() {
                this.$nextTick(() => this.initSortable());
            },

            initSortable() {
                // Canvas: reorderable, receives items from palette
                this.canvasSortable = new Sortable(this.$refs.canvas, {
                    group: { name: 'canvas', put: ['palette-vars', 'palette-includes'] },
                    animation: 150,
                    handle: '.drag-handle',
                    ghostClass: 'opacity-30',
                    draggable: '.designer-block',
                    onEnd: (evt) => {
                        if (evt.from === evt.to) {
                            // Reorder within canvas
                            const moved = this.blocks.splice(evt.oldIndex, 1)[0];
                            this.blocks.splice(evt.newIndex, 0, moved);
                        }
                    },
                    onAdd: (evt) => {
                        const el = evt.item;
                        const type = el.dataset.blockType;
                        const newBlock = { id: this.nextId++ };

                        if (type === 'variable') {
                            const name = el.dataset.name;
                            newBlock.type = 'variable';
                            newBlock.name = name;
                            newBlock.token = '{{' + name + '}}';
                        } else if (type === 'include') {
                            const slug = el.dataset.slug;
                            newBlock.type = 'include';
                            newBlock.slug = slug;
                            newBlock.token = '{{>' + slug + '}}';
                        }

                        this.blocks.splice(evt.newIndex, 0, newBlock);

                        // Remove the cloned DOM element; Alpine will re-render from state
                        el.remove();
                    },
                });

                // Variable palette: clone mode
                if (this.$refs.paletteVars) {
                    this.paletteSortableVars = new Sortable(this.$refs.paletteVars, {
                        group: { name: 'palette-vars', pull: 'clone', put: false },
                        sort: false,
                        animation: 150,
                    });
                }

                // Include palette: clone mode
                if (this.$refs.paletteIncludes) {
                    this.paletteSortableIncludes = new Sortable(this.$refs.paletteIncludes, {
                        group: { name: 'palette-includes', pull: 'clone', put: false },
                        sort: false,
                        animation: 150,
                    });
                }
            },

            addTextBlock() {
                this.blocks.push({
                    id: this.nextId++,
                    type: 'text',
                    content: '',
                });
                this.$nextTick(() => {
                    const canvas = this.$refs.canvas;
                    canvas.scrollTop = canvas.scrollHeight;
                });
            },

            addVariable(name) {
                this.blocks.push({
                    id: this.nextId++,
                    type: 'variable',
                    name: name,
                    token: '{{' + name + '}}',
                });
            },

            addCustomVariable() {
                const name = this.newVarName.trim();
                if (!name || !/^[a-zA-Z_][a-zA-Z0-9_]*$/.test(name)) return;
                this.addVariable(name);
                this.newVarName = '';
            },

            addInclude(slug, name) {
                this.blocks.push({
                    id: this.nextId++,
                    type: 'include',
                    slug: slug,
                    token: '{{>' + slug + '}}',
                });
            },

            removeBlock(blockId) {
                this.blocks = this.blocks.filter(b => b.id !== blockId);
            },

            clearAll() {
                if (confirm('Remove all blocks from the canvas?')) {
                    this.blocks = [];
                }
            },

            autoResize(el) {
                el.style.height = 'auto';
                el.style.height = el.scrollHeight + 'px';
            },

            getMetaValue(varName, field) {
                return this.prevMetadata[varName]?.[field] ?? '';
            },

            submitForm(event) {
                if (this.assembledContent.length === 0) return;
                event.target.submit();
            },
        };
    }
    </script>
</x-app-layout>
