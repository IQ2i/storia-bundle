/** @type {import('tailwindcss').Config} */
module.exports = {
    content: ['./templates/**/*.html.twig'],
    theme: {
        extend: {},
    },
    safelist: [
        'pl-[24px]',
        'pl-[48px]',
        'pl-[60px]',
        'pl-[72px]',
        'pl-[84px]',
        'pl-[96px]',
        'pl-[108px]',
        'pl-[120px]',
        'pl-[132px]',
    ],
    plugins: [
        require('@tailwindcss/typography'),
    ],
}