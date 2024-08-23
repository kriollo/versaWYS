import { app } from '@/dashboard/js/vue-instancia';
import { html } from '@/vendor/code-tag/code-tag-esm.js';
app.component('perfilapp', {
    setup() {
        return {};
    },
    methods: {
        accion(accion) {
            const actions = {};

            const selectedAction = actions[accion.accion] || actions['default'];
            if (typeof selectedAction === 'function') {
                selectedAction();
            }
        },
    },
    template: html`
        <div class="relative overflow-x-auto shadow-md sm:rounded-lg mx-4">
            <hr class="h-px mt-8 mb-4 bg-gray-200 border-0 dark:bg-gray-700" />
        </div>
    `,
});

app.component('perfiles', {
    props: {},
    setup() {
        return {};
    },
    methods: {},
    template: html`
        <div></div>
    `,
});

app.mount('.content-wrapper');
