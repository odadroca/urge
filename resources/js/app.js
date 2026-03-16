import './bootstrap';

import Alpine from 'alpinejs';
import Sortable from 'sortablejs';

window.Sortable = Sortable;

window.Alpine = Alpine;

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
