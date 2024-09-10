import pluginJs from '@eslint/js';
import html from '@html-eslint/eslint-plugin';
// import htmlParser from '@html-eslint/parser';
import eslintConfigPrettier from 'eslint-config-prettier';
//import eslintImport from 'eslint-plugin-import';
import eslintPluginPrettier from 'eslint-plugin-prettier';
import pluginVue from 'eslint-plugin-vue';

import globals from 'globals';

const globalsLocal = {};
export default [
    pluginJs.configs.recommended,
    {
        ...html.configs['flat/essential'],
        plugins: {
            //import: eslintImport,
            prettier: eslintPluginPrettier,
            '@html-eslint': html,
            vue: pluginVue,
        },
        files: ['src/**/*.js', 'versaCompileJS/**/*.js'],
        ignores: [
            'assets/**/*.js',
            'node_modules/**/*.js',
            'src/jscontrollers/devDeclaraciones.js',
        ],
        languageOptions: {
            ecmaVersion: 2020,
            globals: {
                ...globals.browser,
                ...globals.node,
                ...globalsLocal,
            },
            // parser: htmlParser,
            sourceType: 'module',
            parserOptions: {
                sourceType: 'module',
            },
        },
        rules: {
            'vue/no-deprecated-data-object-declaration': 'error',
            'vue/no-computed-properties-in-data': 'error',
            'vue/no-deprecated-destroyed-lifecycle': 'error',
            'vue/no-deprecated-dollar-listeners-api': 'error',
            'vue/v-on-function-call': 'error',
            'vue/no-setup-props-destructure': 'error',
            'vue/prefer-template': 'error',
            'vue/no-deprecated-filter': 'error',
            '@html-eslint/sort-attrs': 'error',
            '@html-eslint/no-duplicate-attrs': 'error',
            '@html-eslint/no-duplicate-id': 'error',
            '@html-eslint/no-obsolete-tags': 'error',
            '@html-eslint/element-newline': 'error',
            '@html-eslint/no-multiple-empty-lines': 'error',
            '@html-eslint/quotes': 'error',
            'prefer-template': 'warn',
            eqeqeq: 'off',
            semi: 'warn',
            'prefer-const': 'error',
            'no-use-before-define': 'warn',
            'no-unused-vars': 'warn',
            'no-unused-expressions': 'error',
            'no-undef': 'error',
            'arrow-body-style': 'warn',
            'no-alert': 'warn',
            strict: 'warn',
            'getter-return': 'warn',
            'no-irregular-whitespace': 'warn',
            'vue/no-unused-vars': 'error',
            // 'import/no-named-default': 'error',
            camelcase: 'off',
            'sort-keys': 'off',
            'no-magic-numbers': 'off',
            'max-lines-per-function': 'off',
            'max-statements': 'off',
            'one-var': 'off',
            'new-cap': 'off',
        },
    },
    eslintConfigPrettier,
];
