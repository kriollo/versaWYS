import { $dom } from '@/dashboard/js/composables/dom';
import {
    log,
    versaAlert,
    versaFetch,
    VersaToast,
} from '@/dashboard/js/functions';
import { app } from '@/dashboard/js/vue-instancia';
import { html } from '@/vendor/code-tag/code-tag-esm';
import Swal from 'sweetalert2';
import { computed, ref } from 'vue';

import { customTable } from '@/dashboard/js/components/customTable';
import { modal } from '@/dashboard/js/components/modal';

/* eslint-disable */
const ct = customTable;
const m = modal;
/* eslint-enable */

app.component('Usersppal', {
    template: html`
        <div
            class="mx-4 my-4 lg:flex lg:justify-between max-sm:flex-col max-sm:flex-wrap">
            <div class="flex gap-2 items-center">
                <svg
                    class="w-[32px] h-[32px] text-gray-800 dark:text-white"
                    aria-hidden="true"
                    fill="currentColor"
                    height="24"
                    viewBox="0 0 24 24"
                    width="24"
                    xmlns="http://www.w3.org/2000/svg">
                    <path
                        clip-rule="evenodd"
                        d="M8 4a4 4 0 1 0 0 8 4 4 0 0 0 0-8Zm-2 9a4 4 0 0 0-4 4v1a2 2 0 0 0 2 2h8a2 2 0 0 0 2-2v-1a4 4 0 0 0-4-4H6Zm7.25-2.095c.478-.86.75-1.85.75-2.905a5.973 5.973 0 0 0-.75-2.906 4 4 0 1 1 0 5.811ZM15.466 20c.34-.588.535-1.271.535-2v-1a5.978 5.978 0 0 0-1.528-4H18a4 4 0 0 1 4 4v1a2 2 0 0 1-2 2h-4.535Z"
                        fill-rule="evenodd" />
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
                            class="inline-flex items-center text-sm font-medium text-gray-700 hover:text-blue-600 dark:text-gray-400 dark:hover:text-white"
                            href="/admin/dashboard">
                            <svg
                                class="w-3 h-3 me-2.5"
                                aria-hidden="true"
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
                                aria-hidden="true"
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
                                Usuarios
                            </span>
                        </div>
                    </li>
                    <li aria-current="page">
                        <div class="flex items-center">
                            <svg
                                class="rtl:rotate-180 w-3 h-3 text-gray-400 mx-1"
                                aria-hidden="true"
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
                                    href="/admin/usuarios/addUser">
                                    <svg
                                        class="w-[20px] h-[20px] text-gray-800 dark:text-white"
                                        aria-hidden="true"
                                        fill="currentColor"
                                        height="24"
                                        viewBox="0 0 24 24"
                                        width="24"
                                        xmlns="http://www.w3.org/2000/svg">
                                        <path
                                            clip-rule="evenodd"
                                            d="M9 4a4 4 0 1 0 0 8 4 4 0 0 0 0-8Zm-2 9a4 4 0 0 0-4 4v1a2 2 0 0 0 2 2h8a2 2 0 0 0 2-2v-1a4 4 0 0 0-4-4H7Zm8-1a1 1 0 0 1 1-1h1v-1a1 1 0 1 1 2 0v1h1a1 1 0 1 1 0 2h-1v1a1 1 0 1 1-2 0v-1h-1a1 1 0 0 1-1-1Z"
                                            fill-rule="evenodd" />
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
        const externalFilters = ref('');
        const buttonSelected = ref('Todos');

        const editUser = (/** @type {String} */ tokenid) => {
            window.location.href = `/admin/usuarios/editUser/${tokenid}`;
        };

        const changePassword = (/** @type {String} */ tokenid) => {
            showModal.value = true;
            tokenIdSelected.value = tokenid;
        };

        const changeStatus = async (/** @type {Object} */ item) => {
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
                    await VersaToast.fire({
                        icon: 'success',
                        title: response.message,
                    });
                    refreshTable.value = !refreshTable.value;
                } else {
                    await VersaToast.fire({
                        icon: 'error',
                        title: response.message,
                    });
                }
            }
        };

        const closeModal = () => {
            showModal.value = false;
        };

        const setFilterExterno = (/** @type {String} */ filter) => {
            externalFilters.value = filter;
            refreshTable.value = !refreshTable.value;
        };

        const accion = (/** @type {Object} */ accion) => {
            const actions = {
                editUser: () => editUser(accion.item.tokenid),
                changePassword: () => changePassword(accion.item.tokenid),
                changeStatus: () => changeStatus(accion.item),
                closeModal: () => closeModal(),
            };
            const action =
                actions[accion.accion] || (() => log('Accion no encontrada'));
            if (typeof action === 'function') {
                action();
            }
        };

        return {
            showModal,
            tokenIdSelected,
            refreshTable,
            externalFilters,
            buttonSelected,
            accion,
            setFilterExterno,
        };
    },
    template: html`
        <customTable
            :externalFilters="externalFilters"
            :refreshData="refreshTable"
            @accion="accion"
            tablaTitle="Listado de Usuarios"
            urlData="/admin/users/getUsersPaginated">
            <template v-slot:buttons>
                <button
                    type="button"
                    class="text-white bg-blue-700 hover:bg-blue-800 focus:ring-4 focus:outline-none focus:ring-blue-300  rounded-lg text-sm px-5 py-2.5 text-center inline-flex items-center dark:bg-blue-600 dark:hover:bg-blue-700 dark:focus:ring-blue-800"
                    :class="buttonSelected === 'Todos' ? 'ring-4 ring-blue-800 font-bold text-current underline underline-offset-8':'font-medium ' "
                    @click="buttonSelected = 'Todos'; setFilterExterno('')">
                    Todos
                </button>
                <button
                    type="button"
                    class="text-white bg-green-700 hover:bg-green-800 focus:ring-4 focus:outline-none focus:ring-blue-300  rounded-lg text-sm px-5 py-2.5 text-center inline-flex items-center dark:bg-green-600 dark:hover:bg-green-700 dark:focus:ring-green-800"
                    :class="buttonSelected === 'Activos' ? 'ring-4 ring-green-800 font-bold text-current  underline underline-offset-8':'font-medium ' "
                    @click="buttonSelected = 'Activos'; setFilterExterno('status = 1')">
                    Activos
                </button>
                <button
                    type="button"
                    class="text-white bg-red-700 hover:bg-red-800 focus:ring-4 focus:outline-none focus:ring-blue-300 rounded-lg text-sm px-5 py-2.5 text-center inline-flex items-center dark:bg-red-600 dark:hover:bg-red-700 dark:focus:ring-red-800"
                    :class="buttonSelected === 'Inactivos' ? 'ring-4 ring-red-800 font-bold text-current  underline underline-offset-8':'font-medium ' "
                    @click="buttonSelected = 'Inactivos'; setFilterExterno('status = 0')">
                    Inactivos
                </button>
            </template>
        </customTable>
        <modalUpdatePass
            :showModal="showModal"
            :tokenId="tokenIdSelected"
            @accion="showModal = false"
            origen="usersPpal" />
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
    setup(props, { emit }) {
        const showModalLocal = computed(() => props.showModal);
        const tokenId = computed(() => props.tokenId);
        const inputToken = document.getElementById('csrf_token');
        if (!(inputToken instanceof HTMLInputElement)) return;
        const csrf_token = inputToken ? inputToken.value : '';

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
            const confirmNewPass = document.getElementById(
                'comfirm_new_password',
            );

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
                    errors = html`
                        <ul
                            class="w-full text-left space-y-1 text-gray-500 list-disc list-inside dark:text-gray-400">
                            ${Object.keys(response.errors)
                                .map(
                                    key => html`
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
        return {
            showModalLocal,
            tokenId,
            csrf_token,
            accion,
            tooglePassword,
            sendResetPass,
        };
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
                            type="button"
                            class="text-gray-400 bg-transparent hover:bg-gray-200 hover:text-gray-900 rounded-lg text-sm w-8 h-8 ms-auto inline-flex justify-center items-center dark:hover:bg-gray-600 dark:hover:text-white"
                            @click="accion({accion: 'closeModal'})">
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
                    <input
                        type="hidden"
                        :value="csrf_token"
                        name="csrf_token" />
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
                            @click="tooglePassword('new_password','imgShowPassNew','imgHiddenPassNew')">
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
                                    <path
                                        d="M10 10a3 3 0 1 0 0-6 3 3 0 0 0 0 6Z" />
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
                            @click="tooglePassword('comfirm_new_password','imgShowPassConfirmNew','imgHiddenPassConfirmNew')">
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
                                    <path
                                        d="M10 10a3 3 0 1 0 0-6 3 3 0 0 0 0 6Z" />
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
                    @click="accion({accion: 'closeModal'})">
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
    `,
});

app.mount('.content-wrapper');
