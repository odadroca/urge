/**
 * Alpine.js autocomplete component for template variable/include insertion.
 *
 * Usage: <div x-data="autocomplete()" ...>
 *   Wrap around a textarea with x-ref="editor".
 *   Dropdown renders below cursor when {{ or {{> is typed.
 */
export default function autocomplete() {
    return {
        suggestions: [],
        selectedIndex: 0,
        showDropdown: false,
        triggerStart: -1,
        triggerType: null, // 'variable' or 'include'
        query: '',

        // Cached data from internal API
        _variablesCache: null,
        _fragmentsCache: null,

        async fetchVariables() {
            if (this._variablesCache) return this._variablesCache;
            try {
                const res = await fetch('/internal/variables');
                this._variablesCache = await res.json();
            } catch {
                this._variablesCache = [];
            }
            return this._variablesCache;
        },

        async fetchFragments() {
            if (this._fragmentsCache) return this._fragmentsCache;
            try {
                const res = await fetch('/internal/fragments');
                this._fragmentsCache = await res.json();
            } catch {
                this._fragmentsCache = [];
            }
            return this._fragmentsCache;
        },

        async handleInput(event) {
            const ta = event.target;
            const pos = ta.selectionStart;
            const text = ta.value.substring(0, pos);

            // Check for {{> pattern (include trigger)
            const includeMatch = text.match(/\{\{>([a-zA-Z0-9_-]*)$/);
            if (includeMatch) {
                this.triggerStart = pos - includeMatch[0].length;
                this.triggerType = 'include';
                this.query = includeMatch[1].toLowerCase();
                const fragments = await this.fetchFragments();
                this.suggestions = fragments
                    .filter(f => f.slug.toLowerCase().includes(this.query) || f.name.toLowerCase().includes(this.query))
                    .slice(0, 10)
                    .map(f => ({ value: f.slug, label: f.slug, description: f.name }));
                this.selectedIndex = 0;
                this.showDropdown = this.suggestions.length > 0;
                if (this.showDropdown) this.positionDropdown(ta, pos);
                return;
            }

            // Check for {{ pattern (variable trigger)
            const varMatch = text.match(/\{\{([a-zA-Z_][a-zA-Z0-9_]*)?$/);
            if (varMatch && !text.match(/\{\{>[a-zA-Z0-9_-]*$/)) {
                this.triggerStart = pos - varMatch[0].length;
                this.triggerType = 'variable';
                this.query = (varMatch[1] || '').toLowerCase();
                const variables = await this.fetchVariables();
                this.suggestions = variables
                    .filter(v => v.toLowerCase().includes(this.query))
                    .slice(0, 10)
                    .map(v => ({ value: v, label: v, description: 'variable' }));
                this.selectedIndex = 0;
                this.showDropdown = this.suggestions.length > 0;
                if (this.showDropdown) this.positionDropdown(ta, pos);
                return;
            }

            this.dismiss();
        },

        handleKeydown(event) {
            if (!this.showDropdown) return;

            if (event.key === 'ArrowDown') {
                event.preventDefault();
                this.selectedIndex = (this.selectedIndex + 1) % this.suggestions.length;
            } else if (event.key === 'ArrowUp') {
                event.preventDefault();
                this.selectedIndex = (this.selectedIndex - 1 + this.suggestions.length) % this.suggestions.length;
            } else if (event.key === 'Enter' || event.key === 'Tab') {
                event.preventDefault();
                this.insertSelected();
            } else if (event.key === 'Escape') {
                event.preventDefault();
                this.dismiss();
            }
        },

        insertSelected() {
            if (this.suggestions.length === 0) return;

            const suggestion = this.suggestions[this.selectedIndex];
            const ta = this.$refs.editor;
            const before = ta.value.substring(0, this.triggerStart);
            const after = ta.value.substring(ta.selectionStart);

            let insertion;
            if (this.triggerType === 'include') {
                insertion = '{' + '{>' + suggestion.value + '}' + '}';
            } else {
                insertion = '{' + '{' + suggestion.value + '}' + '}';
            }

            ta.value = before + insertion + after;
            const newPos = before.length + insertion.length;
            ta.setSelectionRange(newPos, newPos);
            ta.focus();

            // Trigger Alpine reactivity
            ta.dispatchEvent(new Event('input', { bubbles: true }));

            this.dismiss();
        },

        dismiss() {
            this.showDropdown = false;
            this.suggestions = [];
            this.triggerStart = -1;
            this.triggerType = null;
            this.query = '';
        },

        positionDropdown(ta, cursorPos) {
            const dropdown = this.$refs.autocompleteDropdown;
            if (!dropdown) return;

            // Create a mirror div to measure cursor position
            const mirror = document.createElement('div');
            const style = window.getComputedStyle(ta);
            const props = [
                'fontFamily', 'fontSize', 'fontWeight', 'letterSpacing',
                'lineHeight', 'padding', 'border', 'boxSizing', 'whiteSpace',
                'wordWrap', 'overflowWrap', 'width',
            ];
            props.forEach(p => mirror.style[p] = style[p]);
            mirror.style.position = 'absolute';
            mirror.style.visibility = 'hidden';
            mirror.style.height = 'auto';
            mirror.style.overflow = 'hidden';

            const textBefore = ta.value.substring(0, cursorPos);
            mirror.textContent = textBefore;
            const marker = document.createElement('span');
            marker.textContent = '|';
            mirror.appendChild(marker);

            document.body.appendChild(mirror);
            const markerRect = marker.getBoundingClientRect();
            const taRect = ta.getBoundingClientRect();
            document.body.removeChild(mirror);

            // Position relative to textarea container
            const container = ta.closest('.autocomplete-wrapper') || ta.parentElement;
            const containerRect = container.getBoundingClientRect();

            dropdown.style.top = (taRect.top - containerRect.top + markerRect.top - taRect.top + parseInt(style.lineHeight || style.fontSize)) + 'px';
            dropdown.style.left = Math.min(
                markerRect.left - containerRect.left,
                containerRect.width - 250
            ) + 'px';
        },

        selectSuggestion(index) {
            this.selectedIndex = index;
            this.insertSelected();
        },
    };
}
