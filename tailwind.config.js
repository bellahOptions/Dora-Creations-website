import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
    ],

    theme: {
        extend: {
            fontFamily: {
                sans: ['"Inter"', ...defaultTheme.fontFamily.sans],
                display: ['"Archivo Black"', ...defaultTheme.fontFamily.sans],
            },
            colors: {
                ink: {
                    DEFAULT: '#15130F',
                    50: '#F5F4F2',
                    100: '#E7E4DE',
                    200: '#C9C3B8',
                    300: '#A29A89',
                    400: '#726A5B',
                    500: '#4A4438',
                    600: '#332F27',
                    700: '#25221C',
                    800: '#1B1913',
                    900: '#15130F',
                },
                paper: {
                    DEFAULT: '#F8F4EC',
                    soft: '#F1EBDC',
                },
                brand: {
                    50: '#FCF0E8',
                    100: '#F8DCC7',
                    200: '#F0B98C',
                    300: '#E6934F',
                    400: '#DD7936',
                    500: '#CC5E1E',
                    600: '#A94A17',
                    700: '#833914',
                    800: '#5D2A12',
                    900: '#3D1C0D',
                },
                forest: {
                    50: '#E9F2ED',
                    100: '#C6DFD1',
                    200: '#8FBFA3',
                    300: '#559974',
                    400: '#2F7A54',
                    500: '#1F6F54',
                    600: '#175641',
                    700: '#12402F',
                    800: '#0C2A1F',
                    900: '#071810',
                },
                gold: {
                    DEFAULT: '#C9A227',
                    light: '#E6C862',
                },
            },
            boxShadow: {
                soft: '0 20px 45px -20px rgba(21, 19, 15, 0.35)',
            },
            keyframes: {
                'fade-up': {
                    '0%': { opacity: '0', transform: 'translateY(24px)' },
                    '100%': { opacity: '1', transform: 'translateY(0)' },
                },
                'fade-in': {
                    '0%': { opacity: '0' },
                    '100%': { opacity: '1' },
                },
                marquee: {
                    '0%': { transform: 'translateX(0%)' },
                    '100%': { transform: 'translateX(-50%)' },
                },
                'pop-in': {
                    '0%': { opacity: '0', transform: 'scale(0.92)' },
                    '100%': { opacity: '1', transform: 'scale(1)' },
                },
            },
            animation: {
                'fade-up': 'fade-up 0.7s cubic-bezier(0.16, 1, 0.3, 1) both',
                'fade-in': 'fade-in 0.6s ease both',
                marquee: 'marquee 24s linear infinite',
                'pop-in': 'pop-in 0.25s cubic-bezier(0.16, 1, 0.3, 1) both',
            },
        },
    },

    plugins: [forms],
};
