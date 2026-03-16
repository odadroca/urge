import './bootstrap';

import Alpine from 'alpinejs';
import Sortable from 'sortablejs';
import autocomplete from './autocomplete.js';
import composer from './composer.js';
import diffViewer from './diff.js';

window.Sortable = Sortable;

window.Alpine = Alpine;

Alpine.data('autocomplete', autocomplete);
Alpine.data('composer', composer);
Alpine.data('diffViewer', diffViewer);

Alpine.store('theme', {
    dark: localStorage.getItem('theme') === 'dark' ||
          (!localStorage.getItem('theme') && window.matchMedia('(prefers-color-scheme: dark)').matches),
    toggle() {
        this.dark = !this.dark;
        localStorage.setItem('theme', this.dark ? 'dark' : 'light');
        document.documentElement.classList.toggle('dark', this.dark);
    },
});

Alpine.start();
