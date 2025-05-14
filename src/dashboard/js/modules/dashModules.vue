<script setup lang="ts">
    import modulesForm from '@/dashboard/js/modules/modulesForm.vue';
    import modulesList from '@/dashboard/js/modules/modulesList.vue';
    import { reactive } from 'vue';

    import {
        type ShowModalForm,
        ShowModalFormInjection,
    } from '@/dashboard/js/modules/InjectKeys';
    import type { AccionData, actionsType } from 'versaTypes';

    const showModalForm = reactive<ShowModalForm>({
        showModalForm: false,
        itemSelected: null,
        action: '',
    });
    ShowModalFormInjection.provide(showModalForm);

    const accion = (accion: AccionData) => {
        const actions: actionsType = {
            openModal: () => {
                showModalForm.itemSelected = null;
                showModalForm.action = 'new';
                showModalForm.showModalForm = true;
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
    <div class="flex flex-col overflow-y-auto">
        <div
            class="mx-4 my-4 lg:flex lg:justify-between max-sm:flex-col max-sm:flex-wrap">
            <div class="flex items-center gap-2">
                <svg
                    class="w-[32px] h-[32px] text-gray-800 dark:text-white"
                    fill="currentColor"
                    height="24"
                    viewBox="0 0 24 24"
                    width="24"
                    xmlns="http://www.w3.org/2000/svg">
                    <path
                        clip-rule="evenodd"
                        d="M4.857 3A1.857 1.857 0 0 0 3 4.857v4.286C3 10.169 3.831 11 4.857 11h4.286A1.857 1.857 0 0 0 11 9.143V4.857A1.857 1.857 0 0 0 9.143 3H4.857Zm10 0A1.857 1.857 0 0 0 13 4.857v4.286c0 1.026.831 1.857 1.857 1.857h4.286A1.857 1.857 0 0 0 21 9.143V4.857A1.857 1.857 0 0 0 19.143 3h-4.286Zm-10 10A1.857 1.857 0 0 0 3 14.857v4.286C3 20.169 3.831 21 4.857 21h4.286A1.857 1.857 0 0 0 11 19.143v-4.286A1.857 1.857 0 0 0 9.143 13H4.857Zm10 0A1.857 1.857 0 0 0 13 14.857v4.286c0 1.026.831 1.857 1.857 1.857h4.286A1.857 1.857 0 0 0 21 19.143v-4.286A1.857 1.857 0 0 0 19.143 13h-4.286Z"
                        fill-rule="evenodd" />
                </svg>
                <h1
                    class="text-2xl font-semibold text-gray-900 dark:text-white">
                    Módulos
                </h1>
            </div>

            <nav class="flex" aria-label="Breadcrumb">
                <ol
                    class="inline-flex items-center space-x-1 md:space-x-2 rtl:space-x-reverse">
                    <li class="inline-flex items-center">
                        <a
                            class="inline-flex items-center text-sm font-medium text-gray-700 hover:text-blue-600 dark:text-gray-400 dark:hover:text-white"
                            href="/admin/dashboard">
                            <svg
                                class="w-3 h-3 me-2.5"
                                fill="currentColor"
                                viewBox="0 0 20 20"
                                xmlns="http://www.w3.org/2000/svg">
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
                                fill="none"
                                viewBox="0 0 6 10"
                                xmlns="http://www.w3.org/2000/svg">
                                <path
                                    d="m1 9 4-4-4-4"
                                    stroke="currentColor"
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2" />
                            </svg>
                            <span
                                class="ms-1 text-sm font-medium text-gray-500 md:ms-2 dark:text-gray-400">
                                Modules
                            </span>
                        </div>
                    </li>
                    <li aria-current="page">
                        <div class="flex items-center">
                            <svg
                                class="rtl:rotate-180 w-3 h-3 text-gray-400 mx-1"
                                fill="none"
                                viewBox="0 0 6 10"
                                xmlns="http://www.w3.org/2000/svg">
                                <path
                                    d="m1 9 4-4-4-4"
                                    stroke="currentColor"
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2" />
                            </svg>
                            <span
                                class="ms-1 text-sm font-medium text-gray-500 md:ms-2 dark:text-gray-400">
                                <a
                                    class="text-white bg-blue-500 hover:bg-blue-700 focus:ring-4 focus:outline-none focus:ring-blue-300 font-medium rounded-lg text-sm px-5 py-2.5 text-center inline-flex items-center dark:bg-blue-600 dark:hover:bg-blue-700 dark:focus:ring-blue-800 cursor-pointer"
                                    @click="accion({ accion: 'openModal' })">
                                    <svg
                                        class="w-[20px] h-[20px] text-gray-800 dark:text-white"
                                        fill="currentColor"
                                        height="24"
                                        viewBox="0 0 24 24"
                                        width="24"
                                        xmlns="http://www.w3.org/2000/svg">
                                        <path
                                            clip-rule="evenodd"
                                            d="M4.857 3A1.857 1.857 0 0 0 3 4.857v4.286C3 10.169 3.831 11 4.857 11h4.286A1.857 1.857 0 0 0 11 9.143V4.857A1.857 1.857 0 0 0 9.143 3H4.857Zm10 0A1.857 1.857 0 0 0 13 4.857v4.286c0 1.026.831 1.857 1.857 1.857h4.286A1.857 1.857 0 0 0 21 9.143V4.857A1.857 1.857 0 0 0 19.143 3h-4.286Zm-10 10A1.857 1.857 0 0 0 3 14.857v4.286C3 20.169 3.831 21 4.857 21h4.286A1.857 1.857 0 0 0 11 19.143v-4.286A1.857 1.857 0 0 0 9.143 13H4.857ZM18 14a1 1 0 1 0-2 0v2h-2a1 1 0 1 0 0 2h2v2a1 1 0 1 0 2 0v-2h2a1 1 0 1 0 0-2h-2v-2Z"
                                            fill-rule="evenodd" />
                                    </svg>

                                    <span class="max-lg:hidden ms-2">
                                        Nuevo Modulo
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
            <modulesForm />
            <modulesList />
        </div>
    </div>
</template>
