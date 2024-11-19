import copy from 'rollup-plugin-copy';
import sass from 'rollup-plugin-sass';

export default [
    {
        input: 'assets/js/main.js',
        output: {
            file: 'public/storia.js',
            format: 'esm',
        },
        external: [
            '@hotwired/stimulus',
            'highlight.js/lib/core',
            'highlight.js/lib/languages/twig',
            'highlight.js/lib/languages/xml',
        ],
        plugins: [
            sass({
                output: 'public/storia.css',
                options: {
                    outputStyle: 'compressed',
                    silenceDeprecations: ['legacy-js-api'],
                },
            }),
            copy({
                targets: [
                    { src: 'node_modules/@hotwired/stimulus/dist/stimulus.js', dest: 'public/vendor/stimulus' },
                    { src: 'assets/fonts', dest: 'public' },
                ],
            }),
        ],
    },
];