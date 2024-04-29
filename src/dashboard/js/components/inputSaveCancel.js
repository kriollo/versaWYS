'use strict';
import { html } from '@/vendor/code-tag/code-tag-esm.js';
// @ts-ignore
import { computed, ref } from 'vue';
export const inputSaveCancel = {
    props: {
        id: {
            type: Number,
            default: 0,
        },
        data: {
            type: String,
            default: '',
        },
        from: {
            type: String,
        },
        field: {
            type: String,
        },
        type: {
            type: String,
            default: 'text',
        },
    },
    setup(props) {
        const dataProps = computed(() => props.data);
        const idProps = computed(() => props.id);
        const estado_panel = computed(() => props.estado_panel);
        const newData = ref(JSON.parse(JSON.stringify(dataProps.value)));
        const field = computed(() => props.field);
        const type = computed(() => props.type);

        return {
            dataProps,
            idProps,
            estado_panel,
            newData,
            field,
            type,
        };
    },
    methods: {
        accion(
            /** @type {{accion: String; id: Number; newData: String;}} */ accion
        ) {
            const actions = {
                updateData: () => {
                    this.$emit('accion', accion);
                },
                cancelUpdate: () => {
                    this.$emit('accion', accion);
                },
            };

            const selectedAction = actions[accion.accion] || actions['default'];
            if (typeof selectedAction === 'function') {
                selectedAction();
            }
        },
    },
    template: html`
        <div class="relative w-full">
            <input
                :type="type"
                :id="'txtInput_'+idProps+'_'+field"
                v-model="newData"
                class="block p-2.5 w-full z-20 text-sm text-gray-900 bg-gray-50 rounded-e-lg border-s-gray-50 border-s-2 border border-gray-300 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:border-s-gray-700  dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:border-blue-500" />
            <div class="absolute top-0 end-0 flex m-0 p-0">
                <button
                    @click="accion({
                        accion: 'updateData',
                        id: idProps,
                        newData: newData,
                        from: from,
                        field: field
                    })"
                    type="button"
                    class=" p-2.5 text-sm font-medium h-full text-white bg-green-700 rounded-s-lg border border-green-700 hover:bg-green-800 focus:ring-4 focus:outline-none focus:ring-green-300 dark:bg-green-600 dark:hover:bg-green-700 dark:focus:ring-green-800">
                    <i class="bi bi-floppy"></i>
                </button>
                <button
                    @click="accion({
                        id: idProps,
                        from: from,
                        accion: 'cancelUpdate',
                        field: field
                    })"
                    type="button"
                    class="p-2.5 text-sm font-medium h-full text-white bg-orange-700 rounded-e-lg border border-orange-700 hover:bg-orange-800 focus:ring-4 focus:outline-none focus:ring-orange-300 dark:bg-orange-600 dark:hover:bg-orange-700 dark:focus:ring-orange-800">
                    <i class="bi bi-x-lg"></i>
                </button>
            </div>
        </div>
    `,
};
