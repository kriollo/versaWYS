// Archivo de configuración de VersaCompiler
const config = {
    tsconfig: './tsconfig.json',
    compilerOptions: {
        sourceRoot: './src',
        outDir: './public',
        pathsAlias: {
            '@/*': ['src/*'],
            'P@/*': ['public/*'],
        },
    },
    proxyConfig: {
        proxyUrl: 'http://localhost:8000',
        assetsOmit: true,
    },
    aditionalWatch: ['./app/templates/**/*.twig', './app/templates/**/*.html'],
    // puede dejar en false o no agregarlo si no quiere que se ejecute el compilador de tailwind
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
    // Configuración de bundlers
    bundlers: [
        {
            name: 'appLoader',
            fileInput: './public/module/appLoader.js',
            fileOutput: './public/module/appLoader.prod.js',
        },
        {
            name: 'mainApp',
            fileInput: './src/main.ts',
            fileOutput: './dist/main.bundle.js',
        },
    ],
};

export default config;
