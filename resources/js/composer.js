/**
 * Alpine.js visual composer component for inline block editing.
 *
 * Parses template content into typed blocks (text, variable, include),
 * provides SortableJS drag-and-drop reordering, and serializes back to string.
 */
export default function composer() {
    return {
        blocks: [],
        nextId: 1,

        /**
         * Parse template string into blocks.
         */
        parseContent(content) {
            const pattern = /(\{\{>[a-zA-Z0-9_-]+\}\}|\{\{[a-zA-Z_][a-zA-Z0-9_]*\}\})/;
            const parts = content.split(pattern);
            this.blocks = [];
            this.nextId = 1;

            for (const part of parts) {
                if (part === '') continue;

                const includeMatch = part.match(/^\{\{>([a-zA-Z0-9_-]+)\}\}$/);
                const varMatch = part.match(/^\{\{([a-zA-Z_][a-zA-Z0-9_]*)\}\}$/);

                if (includeMatch) {
                    this.blocks.push({
                        id: this.nextId++,
                        type: 'include',
                        slug: includeMatch[1],
                        token: part,
                    });
                } else if (varMatch) {
                    this.blocks.push({
                        id: this.nextId++,
                        type: 'variable',
                        name: varMatch[1],
                        token: part,
                    });
                } else {
                    this.blocks.push({
                        id: this.nextId++,
                        type: 'text',
                        content: part,
                    });
                }
            }
        },

        /**
         * Serialize blocks back into template string.
         */
        serialize() {
            return this.blocks.map(b => {
                if (b.type === 'text') return b.content || '';
                if (b.type === 'variable') return b.token;
                if (b.type === 'include') return b.token;
                return '';
            }).join('');
        },

        addTextBlock() {
            this.blocks.push({ id: this.nextId++, type: 'text', content: '' });
        },

        addVariableBlock(name) {
            this.blocks.push({
                id: this.nextId++,
                type: 'variable',
                name: name,
                token: '{' + '{' + name + '}' + '}',
            });
        },

        addIncludeBlock(slug) {
            this.blocks.push({
                id: this.nextId++,
                type: 'include',
                slug: slug,
                token: '{' + '{>' + slug + '}' + '}',
            });
        },

        removeBlock(blockId) {
            this.blocks = this.blocks.filter(b => b.id !== blockId);
        },

        initSortable(el) {
            if (!window.Sortable || !el) return;
            new Sortable(el, {
                animation: 150,
                handle: '.composer-handle',
                ghostClass: 'opacity-30',
                draggable: '.composer-block',
                onEnd: (evt) => {
                    const moved = this.blocks.splice(evt.oldIndex, 1)[0];
                    this.blocks.splice(evt.newIndex, 0, moved);
                },
            });
        },

        autoResize(el) {
            el.style.height = 'auto';
            el.style.height = el.scrollHeight + 'px';
        },
    };
}
