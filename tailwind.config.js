import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './app/**/*.php',
        './resources/views/**/*.blade.php',
        './resources/js/**/*.js',
    ],

    safelist: [
        'bg-red-200',
        'text-red-800',
        'bg-orange-200',
        'text-orange-800',
        'bg-yellow-200',
        'text-amber-900',
        'text-green-700',
        'border-amber-200',
        'bg-amber-50',
        'text-amber-950',
        'text-amber-900',
        'border-[#fcd34d]',
        'bg-[#fde68a]',
        'hover:bg-[#fcd34d]',
        'text-[#78350f]',
        'focus:ring-[#fbbf24]',
    ],

    theme: {
        extend: {
            fontFamily: {
                sans: ['Figtree', ...defaultTheme.fontFamily.sans],
            },
        },
    },

    plugins: [forms],
};
