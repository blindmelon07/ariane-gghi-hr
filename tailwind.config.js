import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    darkMode: 'class',

    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
    ],

    // Status badges build their colour at runtime (bg-{{ $c }}-100 …), which the
    // content scan can't see — without this, missing shades render unreadable.
    safelist: [
        { pattern: /^(bg|text)-(gray|yellow|blue|green|red|orange|purple)-(100|800)$/ },
        { pattern: /^bg-(gray|yellow|blue|green|red|orange|purple)-900\/30$/, variants: ['dark'] },
        { pattern: /^text-(gray|yellow|blue|green|red|orange|purple)-300$/, variants: ['dark'] },
    ],

    theme: {
        extend: {
            fontFamily: {
                sans: ['Inter', ...defaultTheme.fontFamily.sans],
            },
        },
    },

    plugins: [forms],
};
