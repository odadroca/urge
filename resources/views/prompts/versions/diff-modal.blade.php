{{-- Inline diff modal partial --}}
{{-- Include within an Alpine scope that has diffViewer() mixed in --}}
<div x-show="showDiffModal" x-cloak
     class="fixed inset-0 z-50 overflow-y-auto"
     @keydown.escape.window="closeDiff()">
    {{-- Backdrop --}}
    <div class="fixed inset-0 bg-black/50 transition-opacity" @click="closeDiff()"></div>

    {{-- Modal --}}
    <div class="relative min-h-screen flex items-start justify-center p-4 pt-16">
        <div class="relative bg-white rounded-lg shadow-xl w-full max-w-5xl overflow-hidden" @click.stop>
            {{-- Header --}}
            <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <h3 class="font-semibold text-gray-800">Quick Diff</h3>
                    <span class="px-2 py-0.5 bg-red-50 border border-red-200 rounded text-xs font-mono text-red-700" x-text="oldLabel"></span>
                    <span class="text-gray-400">→</span>
                    <span class="px-2 py-0.5 bg-green-50 border border-green-200 rounded text-xs font-mono text-green-700" x-text="newLabel"></span>
                </div>
                <div class="flex items-center gap-3">
                    <button @click="toggleMode()"
                            class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium border rounded-md transition bg-white border-gray-300 text-gray-600 hover:bg-gray-50">
                        <span x-text="diffMode === 'words' ? 'Switch to chars' : 'Switch to words'"></span>
                    </button>
                    <button @click="closeDiff()" class="text-gray-400 hover:text-gray-600">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>
            </div>

            {{-- Stats --}}
            <div class="px-6 py-2 border-b border-gray-100 flex items-center gap-4 text-xs">
                <template x-if="stats.additions > 0">
                    <span class="text-green-700 font-mono font-semibold">+<span x-text="stats.additions"></span> added</span>
                </template>
                <template x-if="stats.removals > 0">
                    <span class="text-red-700 font-mono font-semibold">-<span x-text="stats.removals"></span> removed</span>
                </template>
                <template x-if="stats.additions === 0 && stats.removals === 0">
                    <span class="text-gray-400">Identical content</span>
                </template>
            </div>

            {{-- Diff content --}}
            <div class="p-6 max-h-[60vh] overflow-auto">
                <pre class="font-mono text-sm whitespace-pre-wrap break-words leading-relaxed" x-html="unifiedHtml"></pre>
            </div>
        </div>
    </div>
</div>
