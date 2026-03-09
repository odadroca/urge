import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

// Category colors used dynamically from DB — must be safelisted so Tailwind keeps them.
const categoryColors = [
    'gray', 'red', 'orange', 'amber', 'yellow', 'lime', 'green',
    'emerald', 'teal', 'cyan', 'sky', 'blue', 'indigo', 'violet',
    'purple', 'fuchsia', 'pink', 'rose',
];
const categorySafelist = categoryColors.flatMap(c => [
    `bg-${c}-50`, `bg-${c}-100`, `bg-${c}-600`,
    `text-${c}-700`, `text-white`,
    `border-${c}-200`, `border-${c}-400`, `border-${c}-600`,
    `hover:border-${c}-400`, `hover:bg-${c}-200`,
]);

/** @type {import('tailwindcss').Config} */
export default {
    darkMode: 'class',
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
    ],

    safelist: categorySafelist,

    theme: {
        extend: {
            fontFamily: {
                sans: ['Figtree', ...defaultTheme.fontFamily.sans],
            },
        },
    },

    plugins: [forms],
};
