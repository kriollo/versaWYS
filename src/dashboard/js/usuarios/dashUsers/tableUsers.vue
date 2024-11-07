<script setup>
import { versaFetch, VersaToast } from '@/dashboard/js/functions';
import Swal from 'sweetalert2';
import { ref } from 'vue';

import { customTable } from '@/dashboard/js/components/customTable';
import { modalUpdatePass } from '@/dashboard/js/usuarios/dashUsers/modalUpdatePass';

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
        actions[accion.accion] ||
        (() => console.log('Accion no encontrada'));
    if (typeof action === 'function') {
        action();
    }
};
</script>
<template>
    <div>
        <customTable :externalFilters="externalFilters" :refreshData="refreshTable" @accion="accion"
            tablaTitle="Listado de Usuarios" urlData="/admin/users/getUsersPaginated">
            <template v-slot:buttons>
                <div class="flex justify-end gap-2">
                    <button type="button"
                        class="text-white bg-blue-700 hover:bg-blue-800 focus:ring-4 focus:outline-none focus:ring-blue-300 rounded-lg text-sm px-5 py-2.5 text-center inline-flex items-center dark:bg-blue-600 dark:hover:bg-blue-700 dark:focus:ring-blue-800"
                        :class="buttonSelected === 'Todos'
                            ? 'ring-4 ring-blue-800 font-bold text-current underline underline-offset-8'
                            : 'font-medium '
                            " @click="
                                buttonSelected = 'Todos';
                            setFilterExterno('');
                            ">
                        Todos
                    </button>
                    <button type="button"
                        class="text-white bg-green-700 hover:bg-green-800 focus:ring-4 focus:outline-none focus:ring-blue-300 rounded-lg text-sm px-5 py-2.5 text-center inline-flex items-center dark:bg-green-600 dark:hover:bg-green-700 dark:focus:ring-green-800"
                        :class="buttonSelected === 'Activos'
                            ? 'ring-4 ring-green-800 font-bold text-current  underline underline-offset-8'
                            : 'font-medium '
                            " @click="
                                buttonSelected = 'Activos';
                            setFilterExterno('status = 1');
                            ">
                        Activos
                    </button>
                    <button type="button"
                        class="text-white bg-red-700 hover:bg-red-800 focus:ring-4 focus:outline-none focus:ring-blue-300 rounded-lg text-sm px-5 py-2.5 text-center inline-flex items-center dark:bg-red-600 dark:hover:bg-red-700 dark:focus:ring-red-800"
                        :class="buttonSelected === 'Inactivos'
                            ? 'ring-4 ring-red-800 font-bold text-current  underline underline-offset-8'
                            : 'font-medium '
                            " @click="
                                buttonSelected = 'Inactivos';
                            setFilterExterno('status = 0');
                            ">
                        Inactivos
                    </button>
                </div>
            </template>
        </customTable>
        <modalUpdatePass :showModal="showModal" :tokenId="tokenIdSelected" @accion="showModal = false"
            origen="usersPpal" />
    </div>
</template>
