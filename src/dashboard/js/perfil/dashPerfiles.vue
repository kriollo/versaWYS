<script setup lang="ts">
    import { $dom } from '@/dashboard/js/composables/dom';
    import { versaFetch, VersaToast } from '@/dashboard/js/functions';
    import listPerfilesSide from '@/dashboard/js/perfil/listPerfilesSide.vue';
    import perfilSide from '@/dashboard/js/perfil/perfilSide.vue';
    import { html } from 'P@/vendor/code-tag/code-tag-esm';
    import Swal from 'sweetalert2';
    import { onMounted, provide, ref } from 'vue';

    import type { Perfil } from 'perfilTypes';
    import type { AccionData, actionsType } from 'versaTypes';

    const refreshData = ref(false);

    const perfil = ref<Perfil>({
        id: 0,
        nombre: '',
        pagina_inicio: '',
    });
    provide('perfil', perfil);

    const newPerfil = async () => {
        const result = await Swal.fire({
            title: 'Nuevo Perfil',
            html: html`
                <input
                    id="nombre"
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-1 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-800 dark:border-gray-600 dark:focus:ring-blue-600 dark:focus:border-blue-600"
                    placeholder="Nombre del Perfil"
                    type="text" />
            `,
            showCancelButton: true,
            confirmButtonText: 'Guardar',
            cancelButtonText: 'Cancelar',
            focusConfirm: false,
            preConfirm: () => {
                const $input = $dom('#nombre');
                if ($input instanceof HTMLInputElement) {
                    if (!$input.value) {
                        Swal.showValidationMessage(
                            'El nombre del perfil es requerido',
                        );
                        return false;
                    }
                    return {
                        nombre: $input.value,
                    };
                }
            },
        });

        if (result.isConfirmed) {
            const response = await versaFetch({
                url: '/admin/perfiles/save',
                method: 'POST',
                data: { nombre: result.value.nombre, estado: 1 },
            });

            if (response.success) {
                refreshData.value = !refreshData.value;
                VersaToast.fire({
                    icon: 'success',
                    title: 'Perfil guardado correctamente',
                });
            } else {
                VersaToast.fire({
                    icon: 'error',
                    title: 'Error al guardar el perfil',
                });
            }
        }
    };

    const accion = (accion: AccionData) => {
        const actions: actionsType = {
            refreshData: () => {
                refreshData.value = !refreshData.value;
            },
            default: () => console.log('Accion no encontrada'),
        };

        const selectedAction = actions[accion.accion] || actions['default'];
        if (typeof selectedAction === 'function') {
            selectedAction();
        }
    };

    onMounted(() => {
        refreshData.value = !refreshData.value;
    });
</script>
<template>
    <div class="flex flex-col overflow-y-auto">
        <div
            class="mx-4 my-4 lg:flex lg:justify-between max-sm:flex-col max-sm:flex-wrap">
            <div class="flex gap-2 items-center">
                <svg
                    class="w-[32px] h-[32px] text-gray-800 dark:text-white"
                    aria-hidden="true"
                    xmlns="http://www.w3.org/2000/svg"
                    width="24"
                    height="24"
                    fill="currentColor"
                    viewbox="0 0 24 24">
                    <path
                        d="M10.83 5a3.001 3.001 0 0 0-5.66 0H4a1 1 0 1 0 0 2h1.17a3.001 3.001 0 0 0 5.66 0H20a1 1 0 1 0 0-2h-9.17ZM4 11h9.17a3.001 3.001 0 0 1 5.66 0H20a1 1 0 1 1 0 2h-1.17a3.001 3.001 0 0 1-5.66 0H4a1 1 0 1 1 0-2Zm1.17 6H4a1 1 0 1 0 0 2h1.17a3.001 3.001 0 0 0 5.66 0H20a1 1 0 1 0 0-2h-9.17a3.001 3.001 0 0 0-5.66 0Z" />
                </svg>
                <h1
                    class="text-2xl font-semibold text-gray-900 dark:text-white">
                    Perfil
                </h1>
            </div>

            <nav class="flex" aria-label="Breadcrumb">
                <ol
                    class="inline-flex items-center space-x-1 md:space-x-2 rtl:space-x-reverse">
                    <li class="inline-flex items-center">
                        <a
                            href="/admin/dashboard"
                            class="inline-flex items-center text-sm font-medium text-gray-700 hover:text-blue-600 dark:text-gray-400 dark:hover:text-white">
                            <svg
                                class="w-3 h-3 me-2.5"
                                aria-hidden="true"
                                xmlns="http://www.w3.org/2000/svg"
                                fill="currentColor"
                                viewbox="0 0 20 20">
                                <path
                                    d="m19.707 9.293-2-2-7-7a1 1 0 0 0-1.414 0l-7 7-2 2a1 1 0 0 0 1.414 1.414L2 10.414V18a2 2 0 0 0 2 2h3a1 1 0 0 0 1-1v-4a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1v4a1 1 0 0 0 1 1h3a2 2 0 0 0 2-2v-7.586l.293.293a1 1 0 0 0 1.414-1.414Z" />
                            </svg>
                            Home
                        </a>
                    </li>
                    <li>
                        <div class="flex items-center">
                            <svg
                                class="rtl:rotate-180 w-3 h-3 text-gray-400 mx-1"
                                aria-hidden="true"
                                xmlns="http://www.w3.org/2000/svg"
                                fill="none"
                                viewbox="0 0 6 10">
                                <path
                                    stroke="currentColor"
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="m1 9 4-4-4-4" />
                            </svg>
                            <span
                                class="ms-1 text-sm font-medium text-gray-500 md:ms-2 dark:text-gray-400">
                                Perfil
                            </span>
                        </div>
                    </li>
                    <li aria-current="page">
                        <div class="flex items-center">
                            <svg
                                class="rtl:rotate-180 w-3 h-3 text-gray-400 mx-1"
                                aria-hidden="true"
                                xmlns="http://www.w3.org/2000/svg"
                                fill="none"
                                viewbox="0 0 6 10">
                                <path
                                    stroke="currentColor"
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="m1 9 4-4-4-4" />
                            </svg>
                            <span
                                class="ms-1 text-sm font-medium text-gray-500 md:ms-2 dark:text-gray-400">
                                <a
                                    @click="newPerfil"
                                    class="text-white bg-blue-500 hover:bg-blue-700 focus:ring-4 focus:outline-none focus:ring-blue-300 font-medium rounded-lg text-sm px-5 py-2.5 text-center inline-flex items-center dark:bg-blue-600 dark:hover:bg-blue-700 dark:focus:ring-blue-800 cursor-pointer">
                                    <svg
                                        class="w-[20px] h-[20px] text-gray-800 dark:text-white"
                                        aria-hidden="true"
                                        xmlns="http://www.w3.org/2000/svg"
                                        width="24"
                                        height="24"
                                        fill="currentColor"
                                        viewbox="0 0 24 24">
                                        <path
                                            d="M10.83 5a3.001 3.001 0 0 0-5.66 0H4a1 1 0 1 0 0 2h1.17a3.001 3.001 0 0 0 5.66 0H20a1 1 0 1 0 0-2h-9.17ZM4 11h9.17a3.001 3.001 0 0 1 5.66 0H20a1 1 0 1 1 0 2h-1.17a3.001 3.001 0 0 1-5.66 0H4a1 1 0 1 1 0-2Zm1.17 6H4a1 1 0 1 0 0 2h1.17a3.001 3.001 0 0 0 5.66 0H20a1 1 0 1 0 0-2h-9.17a3.001 3.001 0 0 0-5.66 0Z" />
                                    </svg>
                                    <span class="max-lg:hidden ms-2">
                                        Nuevo Perfil
                                    </span>
                                </a>
                            </span>
                        </div>
                    </li>
                </ol>
            </nav>
        </div>
        <div class="relative shadow-md sm:rounded-lg mx-4">
            <hr class="h-px mt-8 mb-4 bg-gray-200 border-0 dark:bg-gray-700" />
            <div class="grid lg:grid-cols-[1fr,2fr] gap-4 grid-cols-1">
                <listPerfilesSide :refreshData="refreshData" />
                <perfilSide @accion="accion" />
            </div>
        </div>
    </div>
</template>
