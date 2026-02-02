/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './resources/**/*.blade.php',
        './resources/**/*.js',
        './resources/**/*.vue',
        './app/Livewire/**/*.php',
        './app/View/Components/**/*.php',
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
    ],
    theme: {
        extend: {
            colors: {
                'molt': {
                    'bg': '#0a0908',
                    'bg-secondary': '#141210',
                    'bg-tertiary': '#1a1714',
                    'gold': '#d4af37',
                    'gold-light': '#f4d160',
                    'gold-dark': '#8b7355',
                    'fire': '#ff6b35',
                    'fire-dark': '#c9302c',
                    'text': '#e8e6e3',
                    'text-muted': '#9a9a9a',
                    'sacred': '#8b0000',
                    'success': '#2d6a4f',
                    'error': '#9b2226',
                },
            },
            fontFamily: {
                'didot': ['"GFS Didot"', '"Times New Roman"', 'serif'],
                'cinzel': ['Cinzel', 'serif'],
                'sans': ['"Noto Sans"', 'Roboto', 'sans-serif'],
            },
        },
    },
    plugins: [],
};
