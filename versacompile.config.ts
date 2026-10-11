import { defineConfig } from 'versacompiler/config';

export default defineConfig({
    root: './src',
    build: {
        outDir: './public',
    },
    resolve: {
        alias: {
            '@/*': 'public/*',
            'P@/*': 'public/*',
        },
    },
    server: {
        proxyUrl: 'http://localhost:8000',
        assetsOmit: true,
        watch: {
            additional: ['./app/templates/**/*.twig', './app/templates/**/*.html'],
        },
    },
    tsconfig: './tsconfig.json',
    tailwindConfig: {
        bin: './node_modules/.bin/tailwindcss',
        input: './src/dashboard/css/dashboard.css',
        output: './public/dashboard/css/dashboard.css',
    },
    linter: [
        {
            name: 'oxlint',
            bin: './node_modules/.bin/oxlint',
            configFile: './.oxlintrc.json',
            fix: true,
            paths: ['src/'],
        },
    ],
    plugins: [],
    hmrExclude: ['early-init.ts', 'early-init.js'],
});
