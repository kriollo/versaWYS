'use strict';

// @ts-ignore
import { computed, ref } from 'vue';
import { modal } from '../components/modal.js';
import { versaAlert, versaFetch } from '../functions.js';
import { app } from '../vue-instancia.js';

app.component('Usersppal', {
    setup() {},
    template: /*html*/ `
        <div class="mx-4 my-4 lg:flex lg:justify-between max-sm:flex-col max-sm:flex-wrap">
            <div class="flex gap-2">
                <svg
                    class="w-6 h-6 text-gray-800 dark:text-white"
                    aria-hidden="true"
                    xmlns="http://www.w3.org/2000/svg"
                    fill="currentColor"
                    viewBox="0 0 20 18"
                >
                    <path
                        d="M14 2a3.963 3.963 0 0 0-1.4.267 6.439 6.439 0 0 1-1.331 6.638A4 4 0 1 0 14 2Zm1 9h-1.264A6.957 6.957 0 0 1 15 15v2a2.97 2.97 0 0 1-.184 1H19a1 1 0 0 0 1-1v-1a5.006 5.006 0 0 0-5-5ZM6.5 9a4.5 4.5 0 1 0 0-9 4.5 4.5 0 0 0 0 9ZM8 10H5a5.006 5.006 0 0 0-5 5v2a1 1 0 0 0 1 1h11a1 1 0 0 0 1-1v-2a5.006 5.006 0 0 0-5-5Z"
                    />
                </svg>
                <h1 class="text-2xl font-semibold text-gray-900 dark:text-white">Usuarios</h1>
            </div>

            <nav class="flex" aria-label="Breadcrumb">
                <ol class="inline-flex items-center space-x-1 md:space-x-2 rtl:space-x-reverse">
                    <li class="inline-flex items-center">
                        <a
                            href="/admin/dashboard"
                            class="inline-flex items-center text-sm font-medium text-gray-700 hover:text-blue-600 dark:text-gray-400 dark:hover:text-white"
                        >
                            <svg
                                class="w-3 h-3 me-2.5"
                                aria-hidden="true"
                                xmlns="http://www.w3.org/2000/svg"
                                fill="currentColor"
                                viewBox="0 0 20 20"
                            >
                                <path
                                    d="m19.707 9.293-2-2-7-7a1 1 0 0 0-1.414 0l-7 7-2 2a1 1 0 0 0 1.414 1.414L2 10.414V18a2 2 0 0 0 2 2h3a1 1 0 0 0 1-1v-4a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1v4a1 1 0 0 0 1 1h3a2 2 0 0 0 2-2v-7.586l.293.293a1 1 0 0 0 1.414-1.414Z"
                                />
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
                                viewBox="0 0 6 10"
                            >
                                <path
                                    stroke="currentColor"
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="m1 9 4-4-4-4"
                                />
                            </svg>
                            <span class="ms-1 text-sm font-medium text-gray-500 md:ms-2 dark:text-gray-400">Usuarios</span>
                        </div>
                    </li>
                    <li aria-current="page">
                        <div class="flex items-center">
                            <svg
                                class="rtl:rotate-180 w-3 h-3 text-gray-400 mx-1"
                                aria-hidden="true"
                                xmlns="http://www.w3.org/2000/svg"
                                fill="none"
                                viewBox="0 0 6 10"
                            >
                                <path
                                    stroke="currentColor"
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="m1 9 4-4-4-4"
                                />
                            </svg>
                            <span class="ms-1 text-sm font-medium text-gray-500 md:ms-2 dark:text-gray-400">
                                <a href="/admin/usuarios/addUser" class="text-white bg-blue-500 hover:bg-blue-700 focus:ring-4 focus:outline-none focus:ring-blue-300 font-medium rounded-lg text-sm px-5 py-2.5 text-center inline-flex items-center dark:bg-blue-600 dark:hover:bg-blue-700 dark:focus:ring-blue-800">
                                    <svg class="w-6 h-6 text-gray-800 dark:text-white me-2" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 20 18">
                                        <path d="M6.5 9a4.5 4.5 0 1 0 0-9 4.5 4.5 0 0 0 0 9ZM8 10H5a5.006 5.006 0 0 0-5 5v2a1 1 0 0 0 1 1h11a1 1 0 0 0 1-1v-2a5.006 5.006 0 0 0-5-5Zm11-3h-2V5a1 1 0 0 0-2 0v2h-2a1 1 0 1 0 0 2h2v2a1 1 0 0 0 2 0V9h2a1 1 0 1 0 0-2Z"/>
                                    </svg>
                                    <span class="max-lg:hidden ms-2">Agregar Nuevo usuario</span>
                                </a>
                            </span>
                        </div>
                    </li>
                </ol>
            </nav>


        </div>
        <div class="relative overflow-x-auto shadow-md sm:rounded-lg mx-4">
            <tableUsers></tableUsers>
        </div>
    `,
});
app.component('tableUsers', {
    props: {},
    setup() {
        const users = ref([]);
        const showModal = ref(false);
        const tokenIdSelected = ref('');

        const params = {
            url: '/admin/users/getAllUsers',
            method: 'GET',
            headers: {
                'content-type': 'application/json',
            },
        };
        const loadUsers = () => {
            users.value = [];
            versaFetch(params).then(response => {
                if (response.success === 1) {
                    users.value = response.data;
                }
            });
        };
        loadUsers();

        return {
            users,
            loadUsers,
            showModal,
            tokenIdSelected,
        };
    },
    methods: {
        editUser(tokenid) {
            window.location.href = `/admin/usuarios/editUser/${tokenid}`;
        },
        deleteUser(user) {
            const swalParams =
                user.status === '1'
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
                          text: 'El usuario sera activado',
                          icon: 'warning',
                          showCancelButton: true,
                          confirmButtonText: 'Si, activar',
                          cancelButtonText: 'Cancelar',
                      };

            // @ts-ignore
            Swal.fire(swalParams).then(result => {
                if (result.isConfirmed) {
                    const params = {
                        url: '/admin/users/deleteUser',
                        method: 'DELETE',
                        headers: {
                            'content-type': 'application/json',
                        },
                        data: JSON.stringify({
                            tokenid: user.tokenid,
                        }),
                    };
                    versaFetch(params).then(response => {
                        if (response.success === 1) {
                            versaAlert({
                                message: response.message,
                                type: 'success',
                                callback: () => this.loadUsers(),
                            });
                        } else {
                            versaAlert({ message: response.message, title: 'Error', type: 'error' });
                        }
                    });
                }
            });
        },
        showModalUpdatePass(tokenid) {
            this.showModal = true;
            this.tokenIdSelected = tokenid;
        },
    },
    template: /*html*/ `
        <table class="w-full text-sm text-left rtl:text-right text-gray-500 dark:text-gray-400">
            <caption
                class="p-5 text-lg font-semibold text-left rtl:text-right text-gray-900 bg-white dark:text-white dark:bg-gray-800"
            >
                Listado de Usuarios

                <modalUpdatePass
                    origen="usersPpal"
                    :showModal="showModal"
                    @accion="showModal = false"

                    :tokenId="tokenIdSelected"
                ></modalUpdatePass>

            </caption>
            <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                <tr>
                    <th scope="col" class="px-6 py-3">Nombre</th>
                    <th scope="col" class="px-6 py-3">Correo</th>
                    <th scope="col" class="px-6 py-3">Rol</th>
                    <th scope="col" class="px-6 py-3">Creado el</th>
                    <th scope="col" class="px-6 py-3">Estado</th>
                    <th scope="col" class="px-6 py-3"></th>
                </tr>
            </thead>
            <tbody>
                <tr v-for="user in users" class="bg-white border-b dark:bg-gray-800 dark:border-gray-700">
                    <th scope="row" class="px-6 py-4 font-medium text-gray-900 whitespace-nowrap dark:text-white">
                        {{ user.name }}
                    </th>
                    <td class="px-6 py-4">{{ user.email }}</td>
                    <td class="px-6 py-4">{{ user.role }}</td>
                    <td class="px-6 py-4">{{ user.created_at }}</td>
                    <td class="px-6 py-4">
                        <span v-if="user.status === '1'"
                            class="px-2 py-1 font-semibold leading-tight text-green-700 bg-green-100 rounded-full dark:bg-green-700 dark:text-green-100"
                        >
                            Activo
                        </span>
                        <span v-else
                            class="px-2 py-1 font-semibold leading-tight text-red-700 bg-red-100 rounded-full dark:bg-red-700 dark:text-red-100"
                        >
                            Inactivo
                        </span>
                    </td>
                    <td class="px-6 py-4 text-right">
                        <div class="flex justify-end gap-4">
                            <button @click="editUser(user.tokenid)" class="text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-white" title="editar Usuario">
                                <svg class="w-6 h-6 text-green-600 dark:text-green-600" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 20 18">
                                    <path d="M6.5 9a4.5 4.5 0 1 0 0-9 4.5 4.5 0 0 0 0 9Zm-1.391 7.361.707-3.535a3 3 0 0 1 .82-1.533L7.929 10H5a5.006 5.006 0 0 0-5 5v2a1 1 0 0 0 1 1h4.259a2.975 2.975 0 0 1-.15-1.639ZM8.05 17.95a1 1 0 0 1-.981-1.2l.708-3.536a1 1 0 0 1 .274-.511l6.363-6.364a3.007 3.007 0 0 1 4.243 0 3.007 3.007 0 0 1 0 4.243l-6.365 6.363a1 1 0 0 1-.511.274l-3.536.708a1.07 1.07 0 0 1-.195.023Z"/>
                                </svg>
                            </button>
                            <button @click="showModalUpdatePass(user.tokenid)" class="text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-white" title="Resetear Contraseña">
                                <svg class="w-6 h-6 text-blue-600 dark:text-blue-600" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 20 19">
                                    <path d="M7.324 9.917A2.479 2.479 0 0 1 7.99 7.7l.71-.71a2.484 2.484 0 0 1 2.222-.688 4.538 4.538 0 1 0-3.6 3.615h.002ZM7.99 18.3a2.5 2.5 0 0 1-.6-2.564A2.5 2.5 0 0 1 6 13.5v-1c.005-.544.19-1.072.526-1.5H5a5.006 5.006 0 0 0-5 5v2a1 1 0 0 0 1 1h7.687l-.697-.7ZM19.5 12h-1.12a4.441 4.441 0 0 0-.579-1.387l.8-.795a.5.5 0 0 0 0-.707l-.707-.707a.5.5 0 0 0-.707 0l-.795.8A4.443 4.443 0 0 0 15 8.62V7.5a.5.5 0 0 0-.5-.5h-1a.5.5 0 0 0-.5.5v1.12c-.492.113-.96.309-1.387.579l-.795-.795a.5.5 0 0 0-.707 0l-.707.707a.5.5 0 0 0 0 .707l.8.8c-.272.424-.47.891-.584 1.382H8.5a.5.5 0 0 0-.5.5v1a.5.5 0 0 0 .5.5h1.12c.113.492.309.96.579 1.387l-.795.795a.5.5 0 0 0 0 .707l.707.707a.5.5 0 0 0 .707 0l.8-.8c.424.272.892.47 1.382.584v1.12a.5.5 0 0 0 .5.5h1a.5.5 0 0 0 .5-.5v-1.12c.492-.113.96-.309 1.387-.579l.795.8a.5.5 0 0 0 .707 0l.707-.707a.5.5 0 0 0 0-.707l-.8-.795c.273-.427.47-.898.584-1.392h1.12a.5.5 0 0 0 .5-.5v-1a.5.5 0 0 0-.5-.5ZM14 15.5a2.5 2.5 0 1 1 0-5 2.5 2.5 0 0 1 0 5Z"/>
                                </svg>
                            </button>
                            <button @click="deleteUser(user)" class="text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-white" title="Desactivar Usuario">
                                <svg class="w-6 h-6" :class="user.status === '0' ? 'text-yellow-300 dark:text-yellow-300':'text-red-500 dark:text-red-500' " aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 20 18">
                                    <path v-if="user.status === '1'" d="M6.5 9a4.5 4.5 0 1 0 0-9 4.5 4.5 0 0 0 0 9ZM8 10H5a5.006 5.006 0 0 0-5 5v2a1 1 0 0 0 1 1h11a1 1 0 0 0 1-1v-2a5.006 5.006 0 0 0-5-5Zm11-3h-6a1 1 0 1 0 0 2h6a1 1 0 1 0 0-2Z"/>
                                    <path v-else d="M6.5 9a4.5 4.5 0 1 0 0-9 4.5 4.5 0 0 0 0 9ZM8 10H5a5.006 5.006 0 0 0-5 5v2a1 1 0 0 0 1 1h11a1 1 0 0 0 1-1v-2a5.006 5.006 0 0 0-5-5Zm11-3h-2V5a1 1 0 0 0-2 0v2h-2a1 1 0 1 0 0 2h2v2a1 1 0 0 0 2 0V9h2a1 1 0 1 0 0-2Z"/>
                                </svg>
                            </button>
                        </div>
                    </td>
                </tr>
            </tbody>
        </table>`,
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
        sendResetPass() {
            const formChangePass = document.getElementById('formChangePass');
            if (!(formChangePass instanceof HTMLFormElement)) return false;
            const formData = new FormData(formChangePass);
            const newPass = document.getElementById('new_password');
            const confirmNewPass = document.getElementById('comfirm_new_password');

            if (!(newPass instanceof HTMLInputElement)) return;
            if (!(confirmNewPass instanceof HTMLInputElement)) return;

            if (newPass.value !== confirmNewPass.value) {
                versaAlert({ message: 'Las contraseñas no coinciden', title: 'Error', type: 'error' });
                return;
            }

            // @ts-ignore
            const objectData = Object.fromEntries(formData.entries());
            const params = {
                url: '/admin/users/changePassword',
                method: 'PATCH',
                headers: {
                    'content-type': 'application/json',
                },
                data: JSON.stringify(objectData),
            };
            versaFetch(params).then(response => {
                if (response.success === 1) {
                    versaAlert({
                        message: response.message,
                        type: 'success',
                        callback: () => this.accion({ accion: 'closeModal' }),
                    });
                } else {
                    let errors = '';
                    for (const key in response.errors) {
                        errors += `<li>${response.errors[key]}</li>`;
                    }

                    versaAlert({ html: `${response.message}<ul>${errors}</ul>`, title: 'Error', type: 'error' });
                }
            });
        },
    },
    template: /*html*/ `
        <modal :idModal="origen+'_resetPass'" :showModal="showModalLocal" @accion="accion" >
            <template v-slot:modalTitle>
                <div class="flex justify-between">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white">Actualizar Contraseña</h3>

                    <div class="float-left">
                        <button @click="accion({accion: 'closeModal'})" type="button" class="text-gray-400 bg-transparent hover:bg-gray-200 hover:text-gray-900 rounded-lg text-sm w-8 h-8 ms-auto inline-flex justify-center items-center dark:hover:bg-gray-600 dark:hover:text-white">
                            <svg class="w-3 h-3" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 14 14">
                                <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m1 1 6 6m0 0 6 6M7 7l6-6M7 7l-6 6"/>
                            </svg>
                        </button>
                    </div>
                </div>
            </template>
            <template v-slot:modalBody>
                <form class="space-y-4" id="formChangePass">
                    <input type="hidden" name="csrf_token" :value="csrf_token">
                    <input type="hidden" name="tokenid" :value="tokenId">
                    <div class="relative">
                        <label for="new_password" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Contraseña</label>
                        <span @click="tooglePassword('new_password','imgShowPassNew','imgHiddenPassNew')" id="togglePasswordNew" class="absolute end-0 flex items-center cursor-pointer pr-2 top-[60%]">
                            <svg class="hidden w-6 h-6 text-gray-800 dark:text-slate-400" id="imgShowPassNew" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewbox="0 0 20 18">
                                <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M1.933 10.909A4.357 4.357 0 0 1 1 9c0-1 4-6 9-6m7.6 3.8A5.068 5.068 0 0 1 19 9c0 1-3 6-9 6-.314 0-.62-.014-.918-.04M2 17 18 1m-5 8a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"/>
                            </svg>
                            <svg class="show w-6 h-6 text-gray-800 dark:text-slate-400" id="imgHiddenPassNew" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewbox="0 0 20 14">
                                <g stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2">
                                    <path d="M10 10a3 3 0 1 0 0-6 3 3 0 0 0 0 6Z"/>
                                    <path d="M10 13c4.97 0 9-2.686 9-6s-4.03-6-9-6-9 2.686-9 6 4.03 6 9 6Z"/>
                                </g>
                            </svg>
                        </span>
                        <input type="password" name="new_password" id="new_password" placeholder="••••••••" class="bg-gray-50 border border-gray-300 text-gray-900 sm:text-sm rounded-lg focus:ring-primary-500 focus:border-primary-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-primary-500 dark:focus:border-primary-500" required>
                    </div>

                    <div class="relative">
                        <label for="comfirm_new_password" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Contraseña</label>
                        <span @click="tooglePassword('comfirm_new_password','imgShowPassConfirmNew','imgHiddenPassConfirmNew')" id="togglePasswordConfirmNew" class="absolute end-0 flex items-center cursor-pointer pr-2 top-[60%]">
                            <svg class="hidden w-6 h-6 text-gray-800 dark:text-slate-400" id="imgShowPassConfirmNew" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewbox="0 0 20 14">
                                <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M1.933 10.909A4.357 4.357 0 0 1 1 9c0-1 4-6 9-6m7.6 3.8A5.068 5.068 0 0 1 19 9c0 1-3 6-9 6-.314 0-.62-.014-.918-.04M2 17 18 1m-5 8a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"/>
                            </svg>
                            <svg class="show w-6 h-6 text-gray-800 dark:text-slate-400" id="imgHiddenPassConfirmNew" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewbox="0 0 20 14">
                                <g stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2">
                                    <path d="M10 10a3 3 0 1 0 0-6 3 3 0 0 0 0 6Z"/>
                                    <path d="M10 13c4.97 0 9-2.686 9-6s-4.03-6-9-6-9 2.686-9 6 4.03 6 9 6Z"/>
                                </g>
                            </svg>
                        </span>
                        <input type="password" name="comfirm_new_password" id="comfirm_new_password" placeholder="••••••••" class="bg-gray-50 border border-gray-300 text-gray-900 sm:text-sm rounded-lg focus:ring-primary-500 focus:border-primary-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-primary-500 dark:focus:border-primary-500" required>
                    </div>
                </form>
            </template>
            <template v-slot:modalFooter>
                <button @click="accion({accion: 'closeModal'})" type="button" class="text-white bg-blue-700 hover:bg-blue-800 focus:ring-4 focus:outline-none focus:ring-blue-300 font-medium rounded-lg text-sm px-5 py-2.5 text-center inline-flex items-center dark:bg-blue-600 dark:hover:bg-blue-700 dark:focus:ring-blue-800">
                    Cancelar
                </button>
                <button @click="sendResetPass()" type="button" class="text-white bg-green-700 hover:bg-green-800 focus:ring-4 focus:outline-none focus:ring-blue-300 font-medium rounded-lg text-sm px-5 py-2.5 text-center inline-flex items-center dark:bg-green-600 dark:hover:bg-green-700 dark:focus:ring-green-800">
                    Actualizar Contraseña
                </button>
            </template>
        </modal>`,
});

app.component('modal', modal);

app.mount('.content-wrapper');
