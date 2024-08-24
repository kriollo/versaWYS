import { existeCookieBuild } from '@/dashboard/js/functions.js';
import { createPinia } from 'pinia';
import { createApp } from 'vue';

export const app = createApp({});
export const pinia = createPinia();

if (existeCookieBuild()) {
    app.config.warnHandler = function (msg, vm, trace) {
        console.warn(msg, vm, trace);
    };
    app.config.errorHandler = function (err, vm, info) {
        console.error(err, vm, info);
    };
    app.config.compilerOptions.comments = true;
} else {
    app.config.compilerOptions.comments = false;
}
app.config.performance = true;
app.config.compilerOptions.whitespace = 'condense';

app.use(pinia);
