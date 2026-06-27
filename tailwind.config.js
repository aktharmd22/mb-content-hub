import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    darkMode: 'class',
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
        './app/Livewire/**/*.php',
    ],

    theme: {
        extend: {
            fontFamily: {
                sans: ['Inter', ...defaultTheme.fontFamily.sans],
            },
            colors: {
                // Theme-aware "ink" surface palette — driven by CSS variables so the whole
                // app flips between dark and light without per-file edits. Values live in
                // resources/css/app.css (dark under .dark, light under :root).
                ink: {
                    900: 'rgb(var(--ink-900) / <alpha-value>)',  // page background
                    850: 'rgb(var(--ink-850) / <alpha-value>)',  // card surface
                    800: 'rgb(var(--ink-800) / <alpha-value>)',  // raised surface (inputs)
                    700: 'rgb(var(--ink-700) / <alpha-value>)',  // borders / dividers
                    600: 'rgb(var(--ink-600) / <alpha-value>)',  // input borders, hover states
                    500: 'rgb(var(--ink-500) / <alpha-value>)',  // muted text
                },
            },
            borderWidth: {
                '0.5': '0.5px',
            },
            backgroundImage: {
                'glow-indigo':  'radial-gradient(circle at top right, rgba(99, 102, 241, 0.15), transparent 70%)',
                'glow-emerald': 'radial-gradient(circle at top right, rgba(16, 185, 129, 0.15), transparent 70%)',
                'glow-amber':   'radial-gradient(circle at top right, rgba(245, 158, 11, 0.15), transparent 70%)',
                'glow-rose':    'radial-gradient(circle at top right, rgba(244, 63, 94, 0.15), transparent 70%)',
                'glow-pink':    'radial-gradient(circle at top right, rgba(236, 72, 153, 0.15), transparent 70%)',
                'glow-blue':    'radial-gradient(circle at top right, rgba(59, 130, 246, 0.15), transparent 70%)',
                'glow-violet':  'radial-gradient(circle at top right, rgba(139, 92, 246, 0.15), transparent 70%)',
            },
        },
    },

    plugins: [forms],
};
