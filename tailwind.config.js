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
                // Refined charcoal — softer than pure black, easy on the eyes
                ink: {
                    900: '#1a1d23',  // page background
                    850: '#22262e',  // card surface
                    800: '#2a2f38',  // raised surface (inputs)
                    700: '#363c47',  // borders / dividers
                    600: '#454c58',  // input borders, hover states
                    500: '#5a626f',  // muted text
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
