<script setup lang="ts">
    import Swal, { type SweetAlertOptions } from 'sweetalert2';
    // Importar tipos
    import { ref, watch } from 'vue';

    import customTable from '@/dashboard/js/components/customTable.vue';
    import { API_RESPONSE_CODES } from '@/dashboard/js/constants';
    import { versaFetch, VersaToast } from '@/dashboard/js/functions';
    import modalUpdatePass from '@/dashboard/js/usuarios/dashUsers/modalUpdatePass.vue';
    import type {
        AccionData,
        actionsType,
        VersaParamsFetch,
    } from '@/dashboard/types/versaTypes';

    // Definir una interfaz para el parámetro 'item'
    interface UserItem {
        status: '0' | '1' | string; // Ser específico si es posible, ej. '0' | '1'
        tokenid: string;
        // otras propiedades que item pueda tener
    }

    const showModal = ref(false);
    const tokenIdSelected = ref('');
    const refreshTable = ref(false);
    const externalFilters = ref('');
    const buttonSelected = ref('Todos');
    let estadoFilter = '';

    const editUser = (tokenid: string) => {
        // Tipar parámetro
        globalThis.location.href = `/admin/usuarios/editUser/${tokenid}`;
    };

    const changePassword = (tokenid: string) => {
        // Tipar parámetro
        showModal.value = true;
        tokenIdSelected.value = tokenid;
    };

    const changeStatus = async (item: UserItem) => {
        // Aplicar tipo a 'item'
        // Define el objeto de opciones y tipifícalo explícitamente como SweetAlertOptions
        const options: SweetAlertOptions =
            '1' === item.status
                ? {
                      title: '¿Estas seguro?',
                      text: 'El usuario sera desactivado y no podra acceder al sistema',
                      icon: 'warning', // 'warning' es un SweetAlertIcon válido
                      showCancelButton: true,
                      confirmButtonText: 'Si, desactivar',
                      cancelButtonText: 'Cancelar',
                  }
                : {
                      title: '¿Estas seguro?',
                      text: 'El usuario será activado',
                      icon: 'warning', // 'warning' es un SweetAlertIcon válido
                      showCancelButton: true,
                      confirmButtonText: 'Sí, activar',
                      cancelButtonText: 'Cancelar',
                  };

        // Pasa el objeto de opciones directamente a Swal.fire()
        const result = await Swal.fire(options);

        if (result.isConfirmed) {
            const params: VersaParamsFetch = {
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
            if (API_RESPONSE_CODES.SUCCESS === response.success) {
                VersaToast.fire({
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

    watch(buttonSelected, newValue => {
        if (newValue === 'Todos') {
            estadoFilter = '';
        } else if (newValue === 'Activos') {
            estadoFilter = 'status = 1';
        } else if (newValue === 'Inactivos') {
            estadoFilter = 'status = 0';
        }
        setFilterExterno();
    });

    const setFilterExterno = () => {
        externalFilters.value = `${estadoFilter}`;
        refreshTable.value = !refreshTable.value;
    };

    const accion = (accion: AccionData) => {
        const actions: actionsType = {
            editUser: () => editUser(accion.item.tokenid),
            changePassword: () => changePassword(accion.item.tokenid),
            changeStatus: () => changeStatus(accion.item),
            closeModal: () => closeModal(),
        };
        const action =
            actions[accion.accion] ||
            (() => console.log('Accion no encontrada'));
        if ('function' === typeof action) {
            action();
        }
    };
</script>
<template>
    <div>
        <customTable
            :externalFilters="externalFilters"
            :refreshData="refreshTable"
            @accion="accion"
            tablaTitle="Listado de Usuarios"
            urlData="/admin/users/getUsersPaginated">
            <template v-slot:buttons>
                <div class="flex justify-between gap-2">
                    <a
                        class="text-white bg-blue-500 hover:bg-blue-700 focus:ring-4 focus:outline-none focus:ring-blue-300 font-medium rounded-lg text-sm px-5 py-2.5 text-center inline-flex items-center dark:bg-blue-600 dark:hover:bg-blue-700 dark:focus:ring-blue-800 cursor-pointer"
                        href="/admin/usuarios/addUser">
                        <svg
                            class="w-[20px] h-[20px] text-gray-800 dark:text-white"
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
                    <div class="flex justify-end gap-2">
                        <button
                            type="button"
                            class="text-white bg-blue-700 hover:bg-blue-800 focus:ring-4 focus:outline-none focus:ring-blue-300 rounded-lg text-sm px-5 py-2.5 text-center inline-flex items-center dark:bg-blue-600 dark:hover:bg-blue-700 dark:focus:ring-blue-800"
                            :class="
                                buttonSelected === 'Todos'
                                    ? 'ring-4 ring-blue-800 font-bold text-current underline underline-offset-8'
                                    : 'font-medium '
                            "
                            @click="buttonSelected = 'Todos'">
                            Todos
                        </button>
                        <button
                            type="button"
                            class="text-white bg-green-700 hover:bg-green-800 focus:ring-4 focus:outline-none focus:ring-blue-300 rounded-lg text-sm px-5 py-2.5 text-center inline-flex items-center dark:bg-green-600 dark:hover:bg-green-700 dark:focus:ring-green-800"
                            :class="
                                buttonSelected === 'Activos'
                                    ? 'ring-4 ring-green-800 font-bold text-current  underline underline-offset-8'
                                    : 'font-medium '
                            "
                            @click="buttonSelected = 'Activos'">
                            Activos
                        </button>
                        <button
                            type="button"
                            class="text-white bg-red-700 hover:bg-red-800 focus:ring-4 focus:outline-none focus:ring-blue-300 rounded-lg text-sm px-5 py-2.5 text-center inline-flex items-center dark:bg-red-600 dark:hover:bg-red-700 dark:focus:ring-red-800"
                            :class="
                                buttonSelected === 'Inactivos'
                                    ? 'ring-4 ring-red-800 font-bold text-current  underline underline-offset-8'
                                    : 'font-medium '
                            "
                            @click="buttonSelected = 'Inactivos'">
                            Inactivos
                        </button>
                    </div>
                </div>
            </template>
        </customTable>
        <modalUpdatePass
            :showModal="showModal"
            :tokenId="tokenIdSelected"
            @accion="showModal = false"
            origen="usersPpal" />
    </div>
</template>
