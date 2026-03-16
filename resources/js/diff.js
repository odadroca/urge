/**
 * Alpine.js diff viewer component using the `diff` npm package.
 *
 * Provides word-level and character-level diff rendering.
 * Usage: <div x-data="diffViewer()" ...>
 */
import { diffWords, diffChars } from 'diff';

export default function diffViewerComponent() {
    return {
        oldText: '',
        newText: '',
        diffMode: 'words', // 'words' or 'chars'
        diffParts: [],
        showDiffModal: false,
        oldLabel: '',
        newLabel: '',

        computeDiff() {
            const fn = this.diffMode === 'chars' ? diffChars : diffWords;
            this.diffParts = fn(this.oldText, this.newText);
        },

        openDiff(oldText, newText, oldLabel, newLabel) {
            this.oldText = oldText || '';
            this.newText = newText || '';
            this.oldLabel = oldLabel || 'Old';
            this.newLabel = newLabel || 'New';
            this.computeDiff();
            this.showDiffModal = true;
        },

        closeDiff() {
            this.showDiffModal = false;
            this.diffParts = [];
        },

        toggleMode() {
            this.diffMode = this.diffMode === 'words' ? 'chars' : 'words';
            this.computeDiff();
        },

        /**
         * Build HTML for unified diff view.
         * Returns HTML string with colored spans.
         */
        get unifiedHtml() {
            return this.diffParts.map(part => {
                const escaped = this.escapeHtml(part.value);
                if (part.added) {
                    return '<span class="bg-green-200 text-green-900">' + escaped + '</span>';
                }
                if (part.removed) {
                    return '<span class="bg-red-200 text-red-900 line-through">' + escaped + '</span>';
                }
                return '<span class="text-gray-700">' + escaped + '</span>';
            }).join('');
        },

        /**
         * Build side-by-side HTML.
         */
        get sideBySideHtml() {
            let leftHtml = '';
            let rightHtml = '';

            for (const part of this.diffParts) {
                const escaped = this.escapeHtml(part.value);
                if (part.removed) {
                    leftHtml += '<span class="bg-red-200 text-red-900">' + escaped + '</span>';
                } else if (part.added) {
                    rightHtml += '<span class="bg-green-200 text-green-900">' + escaped + '</span>';
                } else {
                    leftHtml += '<span>' + escaped + '</span>';
                    rightHtml += '<span>' + escaped + '</span>';
                }
            }

            return { left: leftHtml, right: rightHtml };
        },

        get stats() {
            let additions = 0;
            let removals = 0;
            for (const part of this.diffParts) {
                if (part.added) additions += part.value.length;
                if (part.removed) removals += part.value.length;
            }
            return { additions, removals };
        },

        escapeHtml(str) {
            return str
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;');
        },
    };
}
