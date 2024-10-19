<script setup>
    import { computed, nextTick, ref, watch } from 'vue';

    const props = defineProps({
        id: {
            type: String,
            default: 'inputEditable',
        },
        field: {
            type: String,
        },
        type: {
            type: String,
            default: 'text',
        },
        placeholder: {
            type: String,
            default: 'Escribe algo...',
        },
        showCancel: {
            type: Boolean,
            default: false,
        },
    });
    const emit = defineEmits(['accion']);
    const dataProps = defineModel();

    // const dataProps = computed(() => props.data);
    const idProps = computed(() => props.id);
    const newData = ref(null);
    const field = computed(() => props.field);
    const type = computed(() => props.type);
    const placeholder = computed(() => props.placeholder);

    const txtInput = ref(null);

    watch(
        () => dataProps.value,
        () => {
            newData.value = dataProps.value;
        },
    );

    nextTick(() => {
        txtInput.value.focus();
    });

    const accion = (/** @type {Object} */ accion) => {
        const actions = {
            updateData: () => {
                emit('accion', accion);
            },
            cancelUpdate: () => {
                newData.value = dataProps.value;
                emit('accion', accion);
            },
        };

        const selectedAction = actions[accion.accion] || actions['default'];
        if (typeof selectedAction === 'function') {
            selectedAction();
        }
    };
</script>
<template>
    <div class="flex w-full">
        <input
            autofocus
            ref="txtInput"
            :type="type"
            :id="'txtInput_' + idProps + '_' + field"
            :placeholder="placeholder"
            v-model="newData"
            class="block p-2.5 w-full text-sm text-gray-900 bg-gray-50 rounded-l-lg border-s-gray-50 border-s-2 border border-gray-300 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:border-s-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:border-blue-500"
            style="overflow: auto; max-height: 100px" />
        <div class="flex m-0 p-0">
            <button
                @click="
                    accion({
                        accion: 'updateData',
                        id: idProps,
                        newData,
                        field,
                    })
                "
                type="button"
                title="Guardar"
                class="p-2.5 text-sm font-medium h-full text-white bg-green-700 rounded-none border border-green-700 hover:bg-green-800 focus:ring-4 focus:outline-none focus:ring-green-300 dark:bg-green-600 dark:hover:bg-green-700 dark:focus:ring-green-800">
                <i class="bi bi-floppy"></i>
            </button>
            <button
                v-if="showCancel"
                @click="
                    accion({
                        accion: 'cancelUpdate',
                        id: idProps,
                        field,
                    })
                "
                type="button"
                title="Cancelar"
                class="p-2.5 text-sm font-medium h-full text-white bg-orange-700 rounded-r-lg border border-orange-700 hover:bg-orange-800 focus:ring-4 focus:outline-none focus:ring-orange-300 dark:bg-orange-600 dark:hover:bg-orange-700 dark:focus:ring-orange-800">
                <i class="bi bi-x-lg"></i>
            </button>
        </div>
    </div>
</template>
