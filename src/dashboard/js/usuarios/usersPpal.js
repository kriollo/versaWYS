import { log, versaAlert, versaFetch } from '@/dashboard/js/functions.js';
import { app } from '@/dashboard/js/vue-instancia.js';
import { html } from '@/vendor/code-tag/code-tag-esm.js';
import Swal from 'sweetalert2';
import { computed, ref } from 'vue';

import customTable from '@/dashboard/js/components/customTable.js';
import modal from '@/dashboard/js/components/modal.js';

/* eslint-disable */
const ct = customTable;
const m = modal;
/* eslint-enable */

app.component('Usersppal', {
    template: html`
        <div
            class="mx-4 my-4 lg:flex lg:justify-between max-sm:flex-col max-sm:flex-wrap">
            <div class="flex gap-2">
                <svg
                    class="w-6 h-6 text-gray-800 dark:text-white"
                    aria-hidden="true"
                    xmlns="http://www.w3.org/2000/svg"
                    fill="currentColor"
                    viewBox="0 0 20 18">
                    <path
                        d="M14 2a3.963 3.963 0 0 0-1.4.267 6.439 6.439 0 0 1-1.331 6.638A4 4 0 1 0 14 2Zm1 9h-1.264A6.957 6.957 0 0 1 15 15v2a2.97 2.97 0 0 1-.184 1H19a1 1 0 0 0 1-1v-1a5.006 5.006 0 0 0-5-5ZM6.5 9a4.5 4.5 0 1 0 0-9 4.5 4.5 0 0 0 0 9ZM8 10H5a5.006 5.006 0 0 0-5 5v2a1 1 0 0 0 1 1h11a1 1 0 0 0 1-1v-2a5.006 5.006 0 0 0-5-5Z" />
                </svg>
                <h1
                    class="text-2xl font-semibold text-gray-900 dark:text-white">
                    Usuarios
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
                                viewBox="0 0 20 20">
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
                                viewBox="0 0 6 10">
                                <path
                                    stroke="currentColor"
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="m1 9 4-4-4-4" />
                            </svg>
                            <span
                                class="ms-1 text-sm font-medium text-gray-500 md:ms-2 dark:text-gray-400">
                                Usuarios
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
                                viewBox="0 0 6 10">
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
                                    href="/admin/usuarios/addUser"
                                    class="text-white bg-blue-500 hover:bg-blue-700 focus:ring-4 focus:outline-none focus:ring-blue-300 font-medium rounded-lg text-sm px-5 py-2.5 text-center inline-flex items-center dark:bg-blue-600 dark:hover:bg-blue-700 dark:focus:ring-blue-800">
                                    <svg
                                        class="w-6 h-6 text-gray-800 dark:text-white me-2"
                                        aria-hidden="true"
                                        xmlns="http://www.w3.org/2000/svg"
                                        fill="currentColor"
                                        viewBox="0 0 20 18">
                                        <path
                                            d="M6.5 9a4.5 4.5 0 1 0 0-9 4.5 4.5 0 0 0 0 9ZM8 10H5a5.006 5.006 0 0 0-5 5v2a1 1 0 0 0 1 1h11a1 1 0 0 0 1-1v-2a5.006 5.006 0 0 0-5-5Zm11-3h-2V5a1 1 0 0 0-2 0v2h-2a1 1 0 1 0 0 2h2v2a1 1 0 0 0 2 0V9h2a1 1 0 1 0 0-2Z" />
                                    </svg>
                                    <span class="max-lg:hidden ms-2">
                                        Agregar Nuevo usuario
                                    </span>
                                </a>
                            </span>
                        </div>
                    </li>
                </ol>
            </nav>
        </div>
        <div
            class="relative overflow-x-auto shadow-md sm:rounded-lg mx-4 pb-20">
            <hr class="h-px mt-8 mb-4 bg-gray-200 border-0 dark:bg-gray-700" />
            <tableUsers></tableUsers>
        </div>
    `,
});
app.component('tableUsers', {
    setup() {
        const showModal = ref(false);
        const tokenIdSelected = ref('');
        const refreshTable = ref(false);

        return {
            showModal,
            tokenIdSelected,
            refreshTable,
        };
    },
    methods: {
        accion(/** @type {Object} */ accion) {
            const actions = {
                editUser: () => this.editUser(accion.item.tokenid),
                changePassword: () => this.changePassword(accion.item.tokenid),
                changeStatus: () => this.changeStatus(accion.item),
                closeModal: () => (this.showModal = false),
            };
            const action =
                actions[accion.accion] || (() => log('Accion no encontrada'));
            if (typeof action === 'function') {
                action();
            }
        },
        editUser(/** @type {String} */ tokenid) {
            window.location.href = `/admin/usuarios/editUser/${tokenid}`;
        },
        changePassword(/** @type {String} */ tokenid) {
            this.showModal = true;
            this.tokenIdSelected = tokenid;
        },
        async changeStatus(/** @type {Object} */ item) {
            const swalParams =
                item.status === '1'
                    ? {
                          title: '¿Estas seguro?',
                          text: 'El usuario sera desactivado y no podra acceder al sistema',
                          icon: 'warning',
                          showCancelButton: true,
                          confirmButtonText: 'Si, desactivar',
                          cancelButtonText: 'Cancelar',
                      }
                    : {
                          title: '¿Estas seguro?',
                          text: 'El usuario será activado',
                          icon: 'warning',
                          showCancelButton: true,
                          confirmButtonText: 'Sí, activar',
                          cancelButtonText: 'Cancelar',
                      };

            const result = await Swal.fire({
                ...swalParams,
            });
            if (result.isConfirmed) {
                const params = {
                    url: '/admin/users/deleteUser',
                    method: 'DELETE',
                    headers: {
                        'content-type': 'application/json',
                    },
                    data: JSON.stringify({
                        tokenid: item.tokenid,
                    }),
                };
                const response = await versaFetch(params);
                if (response.success === 1) {
                    versaAlert({
                        message: response.message,
                        type: 'success',
                        callback: () => {
                            this.refreshTable = !this.refreshTable;
                        },
                    });
                } else {
                    versaAlert({
                        message: response.message,
                        title: 'Error',
                        type: 'error',
                    });
                }
            }
        },
    },
    template: html`
        <customTable
            urlData="/admin/users/getUsersPaginated"
            tablaTitle="Listado de Usuarios"
            @accion="accion"
            :refreshData="refreshTable" />
        <modalUpdatePass
            origen="usersPpal"
            :showModal="showModal"
            @accion="showModal = false"
            :tokenId="tokenIdSelected" />
    `,
});
app.component('modalUpdatePass', {
    emits: ['accion'],
    props: {
        showModal: {
            type: Boolean,
            default: false,
        },
        origen: {
            type: String,
            default: 'Pendientes',
        },
        tokenId: {
            type: String,
            default: '',
        },
    },
    setup(props) {
        const showModalLocal = computed(() => props.showModal);
        const tokenId = computed(() => props.tokenId);
        const inputToken = document.getElementById('csrf_token');
        if (!(inputToken instanceof HTMLInputElement)) return;
        const csrf_token = inputToken ? inputToken.value : '';

        return {
            showModalLocal,
            tokenId,
            csrf_token,
        };
    },
    methods: {
        accion(/** @type {Object} */ accion) {
            this.$emit('accion', accion);
        },
        tooglePassword(
            /** @type {String} */ idInput,
            /** @type {String} */ idImgShow,
            /** @type {String} */ idImgHidden
        ) {
            const togglePassword = document.getElementById(idInput);
            const imgShowPass = document.getElementById(idImgShow);
            const imgHiddenPass = document.getElementById(idImgHidden);

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
        },
        async sendResetPass() {
            const formChangePass = document.getElementById('formChangePass');
            if (!(formChangePass instanceof HTMLFormElement)) return false;
            const formData = new FormData(formChangePass);
            const newPass = document.getElementById('new_password');
            const confirmNewPass = document.getElementById(
                'comfirm_new_password'
            );

            if (!(newPass instanceof HTMLInputElement)) return;
            if (!(confirmNewPass instanceof HTMLInputElement)) return;

            if (newPass.value !== confirmNewPass.value) {
                versaAlert({
                    message: 'Las contraseñas no coinciden',
                    title: 'Error',
                    type: 'error',
                });
                return;
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
                versaAlert({
                    message: response.message,
                    type: 'success',
                    callback: () => this.accion({ accion: 'closeModal' }),
                });
            } else {
                let errors = '';
                if (response?.errors) {
                    errors = html`
                        <ul
                            class="w-full text-left space-y-1 text-gray-500 list-disc list-inside dark:text-gray-400">
                            ${Object.keys(response.errors)
                                .map(
                                    key => html`
                                        <li>${response.errors[key]}</li>
                                    `
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
        },
    },
    template: html`
        <modal
            :idModal="origen+'_resetPass'"
            :showModal="showModalLocal"
            @accion="accion">
            <template v-slot:modalTitle>
                <div class="flex justify-between">
                    <h3
                        class="text-lg font-medium text-gray-900 dark:text-white">
                        Actualizar Contraseña
                    </h3>

                    <div class="float-left">
                        <button
                            @click="accion({accion: 'closeModal'})"
                            type="button"
                            class="text-gray-400 bg-transparent hover:bg-gray-200 hover:text-gray-900 rounded-lg text-sm w-8 h-8 ms-auto inline-flex justify-center items-center dark:hover:bg-gray-600 dark:hover:text-white">
                            <svg
                                class="w-3 h-3"
                                aria-hidden="true"
                                xmlns="http://www.w3.org/2000/svg"
                                fill="none"
                                viewBox="0 0 14 14">
                                <path
                                    stroke="currentColor"
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="m1 1 6 6m0 0 6 6M7 7l6-6M7 7l-6 6" />
                            </svg>
                        </button>
                    </div>
                </div>
            </template>
            <template v-slot:modalBody>
                <form class="space-y-4" id="formChangePass">
                    <input
                        type="hidden"
                        name="csrf_token"
                        :value="csrf_token" />
                    <input type="hidden" name="tokenid" :value="tokenId" />
                    <div class="relative">
                        <label
                            for="new_password"
                            class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">
                            Contraseña
                        </label>
                        <span
                            @click="tooglePassword('new_password','imgShowPassNew','imgHiddenPassNew')"
                            id="togglePasswordNew"
                            class="absolute end-0 flex items-center cursor-pointer pr-2 top-[60%]">
                            <svg
                                class="hidden w-6 h-6 text-gray-800 dark:text-slate-400"
                                id="imgShowPassNew"
                                aria-hidden="true"
                                xmlns="http://www.w3.org/2000/svg"
                                fill="none"
                                viewbox="0 0 20 18">
                                <path
                                    stroke="currentColor"
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M1.933 10.909A4.357 4.357 0 0 1 1 9c0-1 4-6 9-6m7.6 3.8A5.068 5.068 0 0 1 19 9c0 1-3 6-9 6-.314 0-.62-.014-.918-.04M2 17 18 1m-5 8a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                            </svg>
                            <svg
                                class="show w-6 h-6 text-gray-800 dark:text-slate-400"
                                id="imgHiddenPassNew"
                                aria-hidden="true"
                                xmlns="http://www.w3.org/2000/svg"
                                fill="none"
                                viewbox="0 0 20 14">
                                <g
                                    stroke="currentColor"
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2">
                                    <path
                                        d="M10 10a3 3 0 1 0 0-6 3 3 0 0 0 0 6Z" />
                                    <path
                                        d="M10 13c4.97 0 9-2.686 9-6s-4.03-6-9-6-9 2.686-9 6 4.03 6 9 6Z" />
                                </g>
                            </svg>
                        </span>
                        <input
                            type="password"
                            name="new_password"
                            id="new_password"
                            placeholder="••••••••"
                            class="bg-gray-50 border border-gray-300 text-gray-900 sm:text-sm rounded-lg focus:ring-primary-500 focus:border-primary-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-primary-500 dark:focus:border-primary-500"
                            required />
                    </div>

                    <div class="relative">
                        <label
                            for="comfirm_new_password"
                            class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">
                            Contraseña
                        </label>
                        <span
                            @click="tooglePassword('comfirm_new_password','imgShowPassConfirmNew','imgHiddenPassConfirmNew')"
                            id="togglePasswordConfirmNew"
                            class="absolute end-0 flex items-center cursor-pointer pr-2 top-[60%]">
                            <svg
                                class="hidden w-6 h-6 text-gray-800 dark:text-slate-400"
                                id="imgShowPassConfirmNew"
                                aria-hidden="true"
                                xmlns="http://www.w3.org/2000/svg"
                                fill="none"
                                viewbox="0 0 20 14">
                                <path
                                    stroke="currentColor"
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M1.933 10.909A4.357 4.357 0 0 1 1 9c0-1 4-6 9-6m7.6 3.8A5.068 5.068 0 0 1 19 9c0 1-3 6-9 6-.314 0-.62-.014-.918-.04M2 17 18 1m-5 8a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                            </svg>
                            <svg
                                class="show w-6 h-6 text-gray-800 dark:text-slate-400"
                                id="imgHiddenPassConfirmNew"
                                aria-hidden="true"
                                xmlns="http://www.w3.org/2000/svg"
                                fill="none"
                                viewbox="0 0 20 14">
                                <g
                                    stroke="currentColor"
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2">
                                    <path
                                        d="M10 10a3 3 0 1 0 0-6 3 3 0 0 0 0 6Z" />
                                    <path
                                        d="M10 13c4.97 0 9-2.686 9-6s-4.03-6-9-6-9 2.686-9 6 4.03 6 9 6Z" />
                                </g>
                            </svg>
                        </span>
                        <input
                            type="password"
                            name="comfirm_new_password"
                            id="comfirm_new_password"
                            placeholder="••••••••"
                            class="bg-gray-50 border border-gray-300 text-gray-900 sm:text-sm rounded-lg focus:ring-primary-500 focus:border-primary-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-primary-500 dark:focus:border-primary-500"
                            required />
                    </div>
                </form>
            </template>
            <template v-slot:modalFooter>
                <button
                    @click="accion({accion: 'closeModal'})"
                    type="button"
                    class="text-white bg-blue-700 hover:bg-blue-800 focus:ring-4 focus:outline-none focus:ring-blue-300 font-medium rounded-lg text-sm px-5 py-2.5 text-center inline-flex items-center dark:bg-blue-600 dark:hover:bg-blue-700 dark:focus:ring-blue-800">
                    Cancelar
                </button>
                <button
                    @click="sendResetPass()"
                    type="button"
                    class="text-white bg-green-700 hover:bg-green-800 focus:ring-4 focus:outline-none focus:ring-blue-300 font-medium rounded-lg text-sm px-5 py-2.5 text-center inline-flex items-center dark:bg-green-600 dark:hover:bg-green-700 dark:focus:ring-green-800">
                    Actualizar Contraseña
                </button>
            </template>
        </modal>
    `,
});

app.mount('.content-wrapper');
