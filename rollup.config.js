import commonjs from '@rollup/plugin-commonjs';
import { nodeResolve } from '@rollup/plugin-node-resolve';
import terser from '@rollup/plugin-terser';
import copy from 'rollup-plugin-copy';
import sass from 'rollup-plugin-sass';

export default {
    input: 'assets/js/main.js',
    output: {
        file: 'public/storia.js',
        format: 'iife',
    },
    plugins: [
        sass({
            output: 'public/storia.css',
            options: {
                outputStyle: 'compressed',
                silenceDeprecations: ['legacy-js-api'],
            },
        }),
        nodeResolve(),
        commonjs(),
        terser(),
        copy({
            targets: [{ src: 'assets/fonts', dest: 'public' }],
        }),
    ],
};
