'use strict';
import { existeCookieBuild } from '@/dashboard/js/functions.js';
// @ts-ignore
import { createPinia } from 'pinia';
// @ts-ignore
import { createApp } from 'vue';

export const app = createApp({});
export const pinia = createPinia();

if (existeCookieBuild()) {
    app.config.devtools = true;
    app.config.warnHandler = function (msg, vm, trace) {
        console.warn(msg, vm, trace);
    };
    app.config.errorHandler = function (err, vm, info) {
        console.error(err, vm, info);
    };
    app.config.compilerOptions.comments = true;
} else {
    app.config.devtools = false;
    app.config.compilerOptions.comments = false;
}
app.config.performance = true;
app.config.compilerOptions.delimiters = ['{{', '}}'];
app.config.compilerOptions.whitespace = 'condense';
app.config.compilerOptions.preserveWhitespace = false;

app.use(pinia);
