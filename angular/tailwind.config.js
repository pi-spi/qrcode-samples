/** @type {import('tailwindcss').Config} */
module.exports = {
    content: ['./src/**/*.{html,ts}'],
    theme: {
        extend: {
            colors: {
                'pispi-amber': '#F2A900',
                'pispi-brown': '#542D00',
            },
            fontFamily: {
                sans: [
                    'Inter',
                    'ui-sans-serif',
                    'system-ui',
                    '-apple-system',
                    'BlinkMacSystemFont',
                    'Segoe UI',
                    'sans-serif',
                ],
                mono: [
                    'Roboto Mono',
                    'ui-monospace',
                    'SFMono-Regular',
                    'Menlo',
                    'Monaco',
                    'Consolas',
                    'Liberation Mono',
                    'monospace',
                ],
            },
            borderRadius: {
                '3xl': '1.75rem',
            },
            letterSpacing: {
                extra: '0.3em',
            },
        },
    },
    plugins: [],
};
