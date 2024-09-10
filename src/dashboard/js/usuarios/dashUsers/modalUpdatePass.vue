<script setup>
    import { modal } from '@/dashboard/js/components/modal';
    import { $dom } from '@/dashboard/js/composables/dom';
    import {
        versaAlert,
        versaFetch,
        VersaToast,
    } from '@/dashboard/js/functions';
    import { computed, defineEmits, defineProps, inject } from 'vue';

    const props = defineProps({
        showModal: Boolean,
        tokenId: String,
        origen: String,
    });

    const emit = defineEmits(['accion']);

    const showModalLocal = computed(() => props.showModal);
    const tokenId = computed(() => props.tokenId);
    const csrf_token = inject('csrf_token');

    const tooglePassword = (
        /** @type {String} */ idInput,
        /** @type {String} */ idImgShow,
        /** @type {String} */ idImgHidden,
    ) => {
        const togglePassword = $dom(`#${idInput}`);
        const imgShowPass = $dom(`#${idImgShow}`);
        const imgHiddenPass = $dom(`#${idImgHidden}`);

        if (!(togglePassword instanceof HTMLInputElement)) return;
        if (togglePassword.type == 'password') {
            togglePassword.type = 'text';
            imgShowPass.classList.remove('hidden');
            imgHiddenPass.classList.add('hidden');
        } else {
            togglePassword.type = 'password';
            imgShowPass.classList.add('hidden');
            imgHiddenPass.classList.remove('hidden');
        }
    };

    const accion = (/** @type {Object} */ accion) => {
        emit('accion', accion);
    };

    const sendResetPass = async () => {
        const formChangePass = $dom('#formChangePass');
        if (!(formChangePass instanceof HTMLFormElement)) return false;
        const formData = new FormData(formChangePass);
        const newPass = document.getElementById('new_password');
        const confirmNewPass = document.getElementById('comfirm_new_password');

        if (!(newPass instanceof HTMLInputElement)) return;
        if (!(confirmNewPass instanceof HTMLInputElement)) return;

        if (newPass.value !== confirmNewPass.value) {
            versaAlert({
                message: 'Las contraseñas no coinciden',
                title: 'Error',
                type: 'error',
            });
            return false;
        }

        const objectData = Object.fromEntries(formData.entries());
        const params = {
            url: '/admin/users/changePassword',
            method: 'PATCH',
            headers: {
                'content-type': 'application/json',
            },
            data: JSON.stringify(objectData),
        };
        const response = await versaFetch(params);
        if (response.success === 1) {
            await VersaToast.fire({
                icon: 'success',
                title: response.message,
            });
            accion({ accion: 'closeModal' });
        } else {
            let errors = '';
            if (response?.errors) {
                errors = /* html */ `
                    <ul
                        class="w-full text-left space-y-1 text-gray-500 list-disc list-inside dark:text-gray-400">
                        ${Object.keys(response.errors)
                            .map(
                                key => /* html */ `
                                    <li>${response.errors[key]}</li>
                                `,
                            )
                            .join('')}
                    </ul>
                `;
            }

            versaAlert({
                message: response.message,
                html: `${response.message}<br>${errors}`,
                title: 'Error',
                type: 'error',
                customClass: {
                    popup: 'swal-wide',
                    // htmlContainer: 'swal-target',
                },
            });
        }
    };
</script>
<template #default>
    <modal
        :idModal="origen + 'resetPass'"
        :showModal="showModalLocal"
        @accion="accion">
        <template v-slot:modalTitle>
            <div class="flex justify-between">
                <h3 class="text-lg font-medium text-gray-900 dark:text-white">
                    Actualizar Contraseña
                </h3>

                <div class="float-left">
                    <button
                        type="button"
                        class="text-gray-400 bg-transparent hover:bg-gray-200 hover:text-gray-900 rounded-lg text-sm w-8 h-8 ms-auto inline-flex justify-center items-center dark:hover:bg-gray-600 dark:hover:text-white"
                        @click="accion({ accion: 'closeModal' })">
                        <svg
                            class="w-3 h-3"
                            aria-hidden="true"
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
            <form id="formChangePass" class="space-y-4">
                <input type="hidden" :value="csrf_token" name="csrf_token" />
                <input type="hidden" :value="tokenId" name="tokenid" />
                <div class="relative">
                    <label
                        class="block mb-2 text-sm font-medium text-gray-900 dark:text-white"
                        for="new_password">
                        Contraseña
                    </label>
                    <span
                        id="togglePasswordNew"
                        class="absolute end-0 flex items-center cursor-pointer pr-2 top-[60%]"
                        @click="
                            tooglePassword(
                                'new_password',
                                'imgShowPassNew',
                                'imgHiddenPassNew',
                            )
                        ">
                        <svg
                            id="imgShowPassNew"
                            class="hidden w-6 h-6 text-gray-800 dark:text-slate-400"
                            aria-hidden="true"
                            fill="none"
                            viewbox="0 0 20 18"
                            xmlns="http://www.w3.org/2000/svg">
                            <path
                                d="M1.933 10.909A4.357 4.357 0 0 1 1 9c0-1 4-6 9-6m7.6 3.8A5.068 5.068 0 0 1 19 9c0 1-3 6-9 6-.314 0-.62-.014-.918-.04M2 17 18 1m-5 8a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"
                                stroke="currentColor"
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2" />
                        </svg>
                        <svg
                            id="imgHiddenPassNew"
                            class="show w-6 h-6 text-gray-800 dark:text-slate-400"
                            aria-hidden="true"
                            fill="none"
                            viewbox="0 0 20 14"
                            xmlns="http://www.w3.org/2000/svg">
                            <g
                                stroke="currentColor"
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2">
                                <path d="M10 10a3 3 0 1 0 0-6 3 3 0 0 0 0 6Z" />
                                <path
                                    d="M10 13c4.97 0 9-2.686 9-6s-4.03-6-9-6-9 2.686-9 6 4.03 6 9 6Z" />
                            </g>
                        </svg>
                    </span>
                    <input
                        id="new_password"
                        type="password"
                        class="bg-gray-50 border border-gray-300 text-gray-900 sm:text-sm rounded-lg focus:ring-primary-500 focus:border-primary-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-primary-500 dark:focus:border-primary-500"
                        name="new_password"
                        placeholder="••••••••"
                        required />
                </div>

                <div class="relative">
                    <label
                        class="block mb-2 text-sm font-medium text-gray-900 dark:text-white"
                        for="comfirm_new_password">
                        Contraseña
                    </label>
                    <span
                        id="togglePasswordConfirmNew"
                        class="absolute end-0 flex items-center cursor-pointer pr-2 top-[60%]"
                        @click="
                            tooglePassword(
                                'comfirm_new_password',
                                'imgShowPassConfirmNew',
                                'imgHiddenPassConfirmNew',
                            )
                        ">
                        <svg
                            id="imgShowPassConfirmNew"
                            class="hidden w-6 h-6 text-gray-800 dark:text-slate-400"
                            aria-hidden="true"
                            fill="none"
                            viewbox="0 0 20 14"
                            xmlns="http://www.w3.org/2000/svg">
                            <path
                                d="M1.933 10.909A4.357 4.357 0 0 1 1 9c0-1 4-6 9-6m7.6 3.8A5.068 5.068 0 0 1 19 9c0 1-3 6-9 6-.314 0-.62-.014-.918-.04M2 17 18 1m-5 8a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"
                                stroke="currentColor"
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2" />
                        </svg>
                        <svg
                            id="imgHiddenPassConfirmNew"
                            class="show w-6 h-6 text-gray-800 dark:text-slate-400"
                            aria-hidden="true"
                            fill="none"
                            viewbox="0 0 20 14"
                            xmlns="http://www.w3.org/2000/svg">
                            <g
                                stroke="currentColor"
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2">
                                <path d="M10 10a3 3 0 1 0 0-6 3 3 0 0 0 0 6Z" />
                                <path
                                    d="M10 13c4.97 0 9-2.686 9-6s-4.03-6-9-6-9 2.686-9 6 4.03 6 9 6Z" />
                            </g>
                        </svg>
                    </span>
                    <input
                        id="comfirm_new_password"
                        type="password"
                        class="bg-gray-50 border border-gray-300 text-gray-900 sm:text-sm rounded-lg focus:ring-primary-500 focus:border-primary-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-primary-500 dark:focus:border-primary-500"
                        name="comfirm_new_password"
                        placeholder="••••••••"
                        required />
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
                @click="sendResetPass()">
                Actualizar Contraseña
            </button>
        </template>
    </modal>
</template>
