import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
        './resources/js/**/*.vue',
    ],

    theme: {
        extend: {
            colors: {
                brand: {
                    DEFAULT: '#FF1E2D',
                    dark: '#D40F1C',
                    soft: '#FFE8EA',
                },
                charcoal: {
                    DEFAULT: '#0A0B0D',
                    soft: '#14161A',
                    mute: '#22262E',
                },
                mist: '#EEF0F3',
                paper: '#F7F8FA',
            },
            fontFamily: {
                sans: ['Manrope', ...defaultTheme.fontFamily.sans],
                display: ['Syne', 'Manrope', ...defaultTheme.fontFamily.sans],
                editorial: ['Newsreader', 'Georgia', ...defaultTheme.fontFamily.serif],
                oswald: ['Manrope', ...defaultTheme.fontFamily.sans],
            },
            boxShadow: {
                lift: '0 24px 50px -28px rgba(10, 11, 13, 0.45)',
                glow: '0 0 0 1px rgba(255,30,45,0.15), 0 18px 40px -20px rgba(255,30,45,0.35)',
                soft: '0 10px 30px -18px rgba(10, 11, 13, 0.25)',
            },
            borderRadius: {
                xl2: '1.25rem',
            },
            transitionTimingFunction: {
                editorial: 'cubic-bezier(0.22, 1, 0.36, 1)',
            },
        },
    },

    plugins: [forms],
};
