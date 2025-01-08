import { existeCookieBuild } from '@/dashboard/js/functions';
import { createApp } from 'vue';

export const debug = existeCookieBuild();
export const app = createApp({
    template: `
        <vue-loader-components />
    `,
});
if (debug) {
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
