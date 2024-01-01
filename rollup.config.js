import copy from 'rollup-plugin-copy';
import postcss from 'rollup-plugin-postcss';
import autoprefixer from 'autoprefixer';
import tailwindcss from 'tailwindcss';

export default [
    {
        input: 'assets/js/main.js',
        output: {
            file: 'public/arqui.js',
            format: 'esm',
        },
        external: [
            '@hotwired/stimulus',
            'highlight.js/lib/core',
            'highlight.js/lib/languages/twig',
            'highlight.js/lib/languages/xml',
        ],
        plugins: [
            postcss({
                extract: true,
                extensions: ['.css'],
                plugins: [tailwindcss, autoprefixer],
            }),
            copy({
                targets: [
                    { src: 'node_modules/@hotwired/stimulus/dist/stimulus.js', dest: 'public/vendor/stimulus' },
                ],
            }),
        ],
    },
];