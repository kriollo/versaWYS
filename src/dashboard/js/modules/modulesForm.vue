<script setup>
import { modal } from '@/dashboard/js/components/modal';
import {
    removeScape,
    versaAlert,
    versaFetch,
} from '@/dashboard/js/functions';
import Swal from 'sweetalert2';
import { inject, onWatcherCleanup, ref, watch } from 'vue';

const showModalForm = inject('showModalForm');
const csrf_token = inject('csrf_token');
const showModal = ref(false);
const newModule = {
    action: 'create',
    seccion: '',
    nombre: '',
    descripcion: '',
    icono: '',
    fill: false,
    url: '',
    estado: true,
    csrf_token,
};

const localFormData = ref(JSON.parse(JSON.stringify(newModule)));

watch(
    () => showModalForm,
    value => {
        if (value.showModalForm) {
            showModal.value = value.showModalForm;
            localFormData.value = JSON.parse(
                JSON.stringify(
                    value.itemSelected ? value.itemSelected : newModule,
                ),
            );
            if (value.itemSelected) {
                localFormData.value.action = 'edit';
                localFormData.value.icono = removeScape(
                    value.itemSelected.icono,
                );
                localFormData.value.fill = value.itemSelected.fill === '1';
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
        url: '/admin/modules/saveModule',
        method: 'POST',
        data: localFormData.value,
    };

    const response = await versaFetch(params);
    if (response.success === 0) {
        const errores = /*html*/ `
                <ul
                    class="max-w-md space-y-1 text-gray-500 list-disc list-inside dark:text-gray-400">
                    ${Object.keys(response.errors)
                .map(key => `<li>${response.errors[key]}</li>`)
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
            callback: async () => {
                const result = await Swal.fire({
                    title: 'Recargar la página',
                    text: '¿Desea recargar la página para ver los cambios?',
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonText: 'Recargar',
                    cancelButtonText: 'Cancelar',
                });
                if (result.isConfirmed) window.location.reload();
                else accion({ accion: 'closeModal' });
            },
        });
    }
};

const accion = (/** @type {Object} */ accion) => {
    const actions = {
        closeModal: () => {
            showModalForm.showModalForm = false;
            showModalForm.itemSelected = null;
        },
        default: () => {
            console.log('Emitir evento');
        },
    };
    const selectedAction = actions[accion.accion] || actions['default'];
    if (typeof selectedAction === 'function') {
        selectedAction();
    }
};
</script>

<template>
    <modal :showModal="showModal" @accion="accion" idModal="modalFormModule" size="max-w-xl">
        <template v-slot:modalTitle>
            <div class="flex justify-between">
                <h3 class="text-lg font-medium text-gray-900 dark:text-white">
                    Modulo
                </h3>

                <div class="float-left">
                    <button type="button"
                        class="text-gray-400 bg-transparent hover:bg-gray-200 hover:text-gray-900 rounded-lg text-sm w-8 h-8 ms-auto inline-flex justify-center items-center dark:hover:bg-gray-600 dark:hover:text-white"
                        @click="accion({ accion: 'closeModal' })">
                        <svg class="w-3 h-3" fill="none" viewBox="0 0 14 14" xmlns="http://www.w3.org/2000/svg">
                            <path d="m1 1 6 6m0 0 6 6M7 7l6-6M7 7l-6 6" stroke="currentColor" stroke-linecap="round"
                                stroke-linejoin="round" stroke-width="2" />
                        </svg>
                    </button>
                </div>
            </div>
        </template>
        <template v-slot:modalBody>
            <form class="space-y-6 flex flex-col">
                <div class="grid grid-cols-2 gap-4">
                    <div class="space-y-2 col-span-2 sm:col-span-1">
                        <label class="block mb-2 text-sm font-medium text-gray-900 dark:text-white" for="seccion">
                            Sección
                        </label>
                        <input id="seccion" type="text"
                            class="bg-gray-50 border border-gray-300 text-gray-900 sm:text-sm rounded-lg focus:ring-primary-500 focus:border-primary-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-primary-500 dark:focus:border-primary-500"
                            placeholder="Ingrese la sección" required v-model="localFormData.seccion" />
                    </div>

                    <div class="space-y-2 col-span-2 sm:col-span-1">
                        <label class="block mb-2 text-sm font-medium text-gray-900 dark:text-white" for="nombre">
                            Nombre
                        </label>
                        <input id="nombre" type="text"
                            class="bg-gray-50 border border-gray-300 text-gray-900 sm:text-sm rounded-lg focus:ring-primary-500 focus:border-primary-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-primary-500 dark:focus:border-primary-500"
                            placeholder="Nombre que aparecerá en el menú" required v-model="localFormData.nombre" />
                    </div>
                </div>

                <div class="space-y-2">
                    <label class="block mb-2 text-sm font-medium text-gray-900 dark:text-white" for="descripcion">
                        Descripción
                    </label>
                    <textarea id="descripcion"
                        class="bg-gray-50 border border-gray-300 text-gray-900 sm:text-sm rounded-lg focus:ring-primary-500 focus:border-primary-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-primary-500 dark:focus:border-primary-500"
                        placeholder="Descripción del módulo" required rows="3"
                        v-model="localFormData.descripcion"></textarea>
                </div>

                <div class="space-y-2">
                    <label class="block mb-2 text-sm font-medium text-gray-900 dark:text-white" for="iconoSvg">
                        Icono SVG
                    </label>
                    <div class="flex space-x-4">
                        <textarea id="iconoSvg"
                            class="bg-gray-50 border border-gray-300 text-gray-900 sm:text-sm rounded-lg focus:ring-primary-500 focus:border-primary-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-primary-500 dark:focus:border-primary-500"
                            placeholder="Pegue la sección path '<path d=.... />' del SVG aquí" required rows="5"
                            v-model="localFormData.icono"></textarea>
                        <div>
                            <div class="flex gap-2 mb-2">
                                <label class="block text-sm font-medium text-gray-900 dark:text-white" for="fill">
                                    Rellenar
                                </label>
                                <input type="checkbox"
                                    class="form-checkbox h-5 w-5 text-primary-600 dark:text-primary-400 focus:ring-primary-500 dark:focus:ring-primary-500"
                                    v-model="localFormData.fill" />
                            </div>
                            <div
                                class="flex-shrink-0 w-20 h-20 border border-gray-300 rounded-md flex items-center justify-center overflow-hidden">
                                <div class="w-full h-full flex items-center justify-center">
                                    <svg class="w-full h-full text-gray-800 dark:text-white text-center" :fill="localFormData.fill === '1' ||
                                        localFormData.fill === true
                                        ? 'currentColor'
                                        : 'none'
                                        " height="24" v-html="localFormData.icono" viewBox="0 0 24 24" width="24"
                                        xmlns="http://www.w3.org/2000/svg"></svg>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div class="space-y-2 col-span-2 sm:col-span-1">
                        <label class="block mb-2 text-sm font-medium text-gray-900 dark:text-white" for="url">
                            URL
                        </label>
                        <input id="url" type="url"
                            class="bg-gray-50 border border-gray-300 text-gray-900 sm:text-sm rounded-lg focus:ring-primary-500 focus:border-primary-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-primary-500 dark:focus:border-primary-500"
                            placeholder="/admin/modulo" required v-model="localFormData.url" />
                    </div>

                    <div class="space-y-2 col-span-2 sm:col-span-1 flex items-center justify-center">
                        <div class="flex gap-2 items-center justify-center">
                            <input id="estado" type="checkbox"
                                class="form-checkbox h-5 w-5 text-primary-600 dark:text-primary-400 focus:ring-primary-500 dark:focus:ring-primary-500"
                                v-model="localFormData.estado" />
                            <label class="block text-sm font-medium text-gray-900 dark:text-white" for="estado">
                                Estado
                            </label>
                        </div>
                    </div>
                </div>
            </form>
        </template>
        <template v-slot:modalFooter>
            <button type="button"
                class="text-white bg-blue-700 hover:bg-blue-800 focus:ring-4 focus:outline-none focus:ring-blue-300 font-medium rounded-lg text-sm px-5 py-2.5 text-center inline-flex items-center dark:bg-blue-600 dark:hover:bg-blue-700 dark:focus:ring-blue-800"
                @click="accion({ accion: 'closeModal' })">
                Cancelar
            </button>
            <button type="button"
                class="text-white bg-green-700 hover:bg-green-800 focus:ring-4 focus:outline-none focus:ring-blue-300 font-medium rounded-lg text-sm px-5 py-2.5 text-center inline-flex items-center dark:bg-green-600 dark:hover:bg-green-700 dark:focus:ring-green-800"
                @click="saveModule">
                Guardar
            </button>
        </template>
    </modal>
</template>
