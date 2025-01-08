<script setup lang="ts">
    import modal from '@/dashboard/js/components/modal.vue';
    import { versaAlert, versaFetch } from '@/dashboard/js/functions';
    import { html } from 'P@/vendor/code-tag/code-tag-esm';
    import type { AccionData, actionsType, VersaParamsFetch } from 'versaTypes';
    import { inject, onWatcherCleanup, ref, watch } from 'vue';

    type ShowModalSubForm = {
        ShowModalSubForm: boolean;
        itemSelected: any;
    };
    const ShowModalSubForm = inject<ShowModalSubForm>('ShowModalSubForm');
    const csrf_token = inject<string>('csrf_token');
    const id_menu = inject<string>('id_menu');

    const showModal = ref(false);
    const newModule = {
        id_menu: 0,
        action: 'create',
        nombre: '',
        descripcion: '',
        url: '',
        estado: true,
        csrf_token,
    };

    const localFormData = ref(JSON.parse(JSON.stringify(newModule)));

    watch(
        () => ShowModalSubForm,
        value => {
            if (value.ShowModalSubForm) {
                showModal.value = value.ShowModalSubForm;
                localFormData.value = JSON.parse(
                    JSON.stringify(
                        value.itemSelected ? value.itemSelected : newModule,
                    ),
                );
                localFormData.value.id_menu = id_menu;
                if (value.itemSelected) {
                    localFormData.value.action = 'edit';
                    localFormData.value.estado =
                        value.itemSelected?.estado === '1';
                    localFormData.value.csrf_token = csrf_token;
                }
            }

            onWatcherCleanup(() => {
                showModal.value = false;
                localFormData.value = JSON.parse(JSON.stringify(newModule));
            });
        },
        { deep: true },
    );

    const saveModule = async () => {
        const params = {
            url: '/admin/submodules/saveModule',
            method: 'POST',
            data: localFormData.value,
        } as VersaParamsFetch;

        const response = await versaFetch(params);
        if (response.success === 0) {
            const errores = html`
                <ul
                    class="max-w-md space-y-1 text-gray-500 list-disc list-inside dark:text-gray-400">
                    ${Object.keys(response.errors)
                        .map(
                            key => html`
                                <li>${response.errors[key]}</li>
                            `,
                        )
                        .join('')}
                </ul>
            `;
            versaAlert({
                title: 'Error',
                type: 'error',
                html: errores,
            });
        } else {
            versaAlert({
                title: 'Éxito',
                message: response.message,
                type: 'success',
                callback: () => {
                    location.reload();
                },
            });
        }
    };

    const accion = (accion: AccionData) => {
        const actions: actionsType = {
            closeModal: () => {
                ShowModalSubForm.ShowModalSubForm = false;
                ShowModalSubForm.itemSelected = null;
            },
            default: () => console.log('Accion no encontrada'),
        };
        const selectedAction = actions[accion.accion] || actions['default'];
        if (typeof selectedAction === 'function') {
            selectedAction();
        }
    };
</script>
<template>
    <modal
        :showModal="showModal"
        @accion="accion"
        idModal="modalFormModule"
        size="max-w-xl">
        <template v-slot:modalTitle>
            <div class="flex justify-between">
                <h3 class="text-lg font-medium text-gray-900 dark:text-white">
                    Sub Modulo
                </h3>

                <div class="float-left">
                    <button
                        type="button"
                        class="text-gray-400 bg-transparent hover:bg-gray-200 hover:text-gray-900 rounded-lg text-sm w-8 h-8 ms-auto inline-flex justify-center items-center dark:hover:bg-gray-600 dark:hover:text-white"
                        @click="accion({ accion: 'closeModal' })">
                        <svg
                            class="w-3 h-3"
                            fill="none"
                            viewBox="0 0 14 14"
                            xmlns="http://www.w3.org/2000/svg">
                            <path
                                d="m1 1 6 6m0 0 6 6M7 7l6-6M7 7l-6 6"
                                stroke="currentColor"
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2" />
                        </svg>
                    </button>
                </div>
            </div>
        </template>
        <template v-slot:modalBody>
            <form class="space-y-6 flex flex-col">
                <div class="grid grid-cols-2 gap-4">
                    <div class="space-y-2 col-span-2 sm:col-span-1">
                        <label
                            class="block mb-2 text-sm font-medium text-gray-900 dark:text-white"
                            for="nombre">
                            Nombre
                        </label>
                        <input
                            id="nombre"
                            type="text"
                            class="bg-gray-50 border border-gray-300 text-gray-900 sm:text-sm rounded-lg focus:ring-primary-500 focus:border-primary-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-primary-500 dark:focus:border-primary-500"
                            placeholder="Nombre que aparecerá en el menú"
                            required
                            v-model="localFormData.nombre" />
                    </div>
                </div>

                <div class="space-y-2">
                    <label
                        class="block mb-2 text-sm font-medium text-gray-900 dark:text-white"
                        for="descripcion">
                        Descripción
                    </label>
                    <textarea
                        id="descripcion"
                        class="bg-gray-50 border border-gray-300 text-gray-900 sm:text-sm rounded-lg focus:ring-primary-500 focus:border-primary-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-primary-500 dark:focus:border-primary-500"
                        placeholder="Descripción del módulo"
                        required
                        rows="3"
                        v-model="localFormData.descripcion"></textarea>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div class="space-y-2 col-span-2 sm:col-span-1">
                        <label
                            class="block mb-2 text-sm font-medium text-gray-900 dark:text-white"
                            for="url">
                            URL
                        </label>
                        <input
                            id="url"
                            type="url"
                            class="bg-gray-50 border border-gray-300 text-gray-900 sm:text-sm rounded-lg focus:ring-primary-500 focus:border-primary-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-primary-500 dark:focus:border-primary-500"
                            placeholder="/admin/modulo"
                            required
                            v-model="localFormData.url" />
                    </div>

                    <div
                        class="space-y-2 col-span-2 sm:col-span-1 flex items-center justify-center">
                        <div class="flex gap-2 items-center justify-center">
                            <input
                                id="estado"
                                type="checkbox"
                                class="form-checkbox h-5 w-5 text-primary-600 dark:text-primary-400 focus:ring-primary-500 dark:focus:ring-primary-500"
                                v-model="localFormData.estado" />
                            <label
                                class="block text-sm font-medium text-gray-900 dark:text-white"
                                for="estado">
                                Estado
                            </label>
                        </div>
                    </div>
                </div>
            </form>
        </template>
        <template v-slot:modalFooter>
            <button
                type="button"
                class="text-white bg-blue-700 hover:bg-blue-800 focus:ring-4 focus:outline-none focus:ring-blue-300 font-medium rounded-lg text-sm px-5 py-2.5 text-center inline-flex items-center dark:bg-blue-600 dark:hover:bg-blue-700 dark:focus:ring-blue-800"
                @click="accion({ accion: 'closeModal' })">
                Cancelar
            </button>
            <button
                type="button"
                class="text-white bg-green-700 hover:bg-green-800 focus:ring-4 focus:outline-none focus:ring-blue-300 font-medium rounded-lg text-sm px-5 py-2.5 text-center inline-flex items-center dark:bg-green-600 dark:hover:bg-green-700 dark:focus:ring-green-800"
                @click="saveModule">
                Guardar
            </button>
        </template>
    </modal>
</template>
