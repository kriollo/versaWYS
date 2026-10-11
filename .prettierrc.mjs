const config = {
    arrowParens: 'avoid',
    bracketSameLine: true,
    bracketSpacing: true,
    embeddedLanguageFormatting: 'auto',
    endOfLine: 'auto',
    htmlWhitespaceSensitivity: 'ignore',
    insertPragma: false,
    jsxSingleQuote: false,
    printWidth: 120, // Aumentar línea para evitar división de imports
    proseWrap: 'preserve',
    quoteProps: 'as-needed',
    requirePragma: false,
    semi: true,
    singleQuote: true,
    tabWidth: 4,
    trailingComma: 'all',
    useTabs: false,
    vueIndentScriptAndStyle: true,
    plugins: ['@prettier/plugin-php', '@trivago/prettier-plugin-sort-imports', '@zackad/prettier-plugin-twig'],

    // === CONFIGURACIÓN DE ORDENAMIENTO DE IMPORTS ===
    importOrder: [
        // 1. Side effects (CSS, polyfills, etc.)
        String.raw`^\./.*\.(css|scss|sass|less)$`,
        String.raw`^[^.].*\.(css|scss|sass|less)$`,

        // 2. Bibliotecas externas (node_modules)
        '<THIRD_PARTY_MODULES>',

        // 3. Imports absolutos internos (con alias @/)
        String.raw`^@\/`,

        // 4. Imports relativos (../ y ./)
        String.raw`^\.\.\/`,
        String.raw`^\.\/`,
    ],
    importOrderSeparation: true,
    importOrderSortSpecifiers: true,
    importOrderCaseInsensitive: true,
    importOrderParserPlugins: ['typescript', 'decorators-legacy', 'jsx'],

    overrides: [
        {
            files: '*.json',
            options: {
                tabWidth: 4,
            },
        },
        {
            files: ['*.yaml', '*.yml'],
            options: {
                tabWidth: 2,
                singleQuote: false,
            },
        },
        {
            files: '*.vue',
            options: {
                singleAttributePerLine: false,
            },
        },
        {
            parser: 'php',
            files: '*.php',
            options: {
                braceStyle: 'psr-2',
                printWidth: 120,
                tabWidth: 4,
                trailingCommaPHP: true,
                phpVersion: '8.3',
            },
        },
        {
            files: '*.twig',
            options: {
                parser: 'twig',
                tabWidth: 4,
            },
        },
    ],
};

export default config;
