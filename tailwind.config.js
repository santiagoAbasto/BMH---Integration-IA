import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/**
 * Tokens de BMH. Los valores salen de docs/design-system.md, que a su vez los
 * extrae del CSS de producción (#0098DA aparece 49 veces, #ABD430 marca los
 * productos NUEVO).
 *
 * @type {import('tailwindcss').Config}
 */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
        './resources/js/**/*.{ts,tsx}',
    ],

    theme: {
        extend: {
            colors: {
                brand: {
                    50: '#E6F6FD',
                    100: '#CCEDFA',
                    200: '#99DBF5',
                    400: '#33B4E4',
                    500: '#0098DA',
                    600: '#007CB2',
                    700: '#005E87',
                    900: '#00405C',
                },
                // Verde de estado de BMH. Nunca como color de texto sobre
                // blanco: 1.9:1. Para texto está signal-700.
                signal: {
                    400: '#C2E05F',
                    500: '#ABD430',
                    700: '#6E8A1C',
                },
                surface: {
                    base: '#F4F6F8',
                    raised: '#FFFFFF',
                    sunken: '#EAEEF2',
                    inverse: '#0E1620',
                },
                edge: {
                    subtle: '#E1E7ED',
                    DEFAULT: '#CBD5DF',
                    strong: '#9AA9B8',
                },
                ink: {
                    primary: '#0E1620',
                    secondary: '#4A5A6B',
                    tertiary: '#748496',
                    inverse: '#F4F6F8',
                },
                state: {
                    success: '#1F8A4C',
                    warning: '#B26A00',
                    danger: '#C0392B',
                    info: '#0098DA',
                },
            },

            fontFamily: {
                sans: ['Inter', ...defaultTheme.fontFamily.sans],
                mono: ['JetBrains Mono', ...defaultTheme.fontFamily.mono],
            },

            fontSize: {
                micro: ['0.6875rem', { lineHeight: '1rem', letterSpacing: '0.04em', fontWeight: '600' }],
                caption: ['0.8125rem', { lineHeight: '1.125rem', fontWeight: '500' }],
                body: ['0.9375rem', { lineHeight: '1.5rem' }],
                subtitle: ['1rem', { lineHeight: '1.5rem', fontWeight: '600' }],
                title: ['1.25rem', { lineHeight: '1.75rem', fontWeight: '600' }],
                display: ['1.875rem', { lineHeight: '2.25rem', fontWeight: '700' }],
            },

            borderRadius: {
                bubble: '0.75rem',
                card: '0.5rem',
            },

            boxShadow: {
                // El borde manda, la sombra acompaña. Nada de glow.
                raised: '0 1px 2px rgba(14, 22, 32, 0.06)',
                lifted: '0 4px 12px rgba(14, 22, 32, 0.08)',
                overlay: '0 12px 32px rgba(14, 22, 32, 0.16)',
            },

            keyframes: {
                'bubble-in': {
                    from: { opacity: '0', transform: 'translateY(6px)' },
                    to: { opacity: '1', transform: 'translateY(0)' },
                },
                'card-in': {
                    from: { opacity: '0', transform: 'translateY(8px)' },
                    to: { opacity: '1', transform: 'translateY(0)' },
                },
                shimmer: {
                    '100%': { transform: 'translateX(100%)' },
                },
            },

            animation: {
                'bubble-in': 'bubble-in 180ms cubic-bezier(0.2, 0, 0, 1) both',
                'card-in': 'card-in 220ms cubic-bezier(0.2, 0, 0, 1) both',
            },

            transitionTimingFunction: {
                bmh: 'cubic-bezier(0.2, 0, 0, 1)',
            },

            maxWidth: {
                bubble: '38.75rem',
            },
        },
    },

    plugins: [forms],
};
