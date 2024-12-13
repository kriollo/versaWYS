<script setup>
    import { versaFetch, VersaToast } from '@/dashboard/js/functions';
    import Swal from 'sweetalert2';
    import { inject, ref } from 'vue';

    import { customTable } from '@/dashboard/js/components/customTable';
    import { listSubModules } from '@/dashboard/js/modules/listSubModules';

    const externalFilters = ref('');
    const buttonSelected = ref('Todos');
    const refreshTable = ref(false);
    const showModalSubMenu = ref(false);

    /** @type {{showModalForm: boolean, itemSelected: null|Object, action: string}} */
    const showModalForm = inject('showModalForm');

    const csrf_token = inject('csrf_token');

    const setFilterExterno = (/** @type {string} */ filter) => {
        externalFilters.value = filter;
        refreshTable.value = !refreshTable.value;
    };

    const changeStatus = async (/** @type {Object} */ item) => {
        const result = await Swal.fire({
            title: '¿Estás seguro?',
            text: `Estás a punto de cambiar el estado del módulo ${item.nombre}`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Sí, cambiar estado',
            cancelButtonText: 'Cancelar',
        });
        if (result.isConfirmed) {
            const params = {
                url: '/admin/modules/changeStatus',
                method: 'PATCH',
                data: JSON.stringify({
                    id: item.id,
                    estado: item.estado === '1' ? '0' : '1',
                    csrf_token,
                }),
                headers: { 'Content-Type': 'application/json' },
            };
            const response = await versaFetch(params);
            if (response.success === 1) {
                await VersaToast.fire({
                    icon: 'success',
                    title: response.message,
                });
                location.reload();
            } else {
                await VersaToast.fire({
                    icon: 'warning',
                    title: response.message,
                });
            }
        }
    };

    const changePosition = async (/** @type {Object} */ item) => {
        const params = {
            url: '/admin/modules/movePosition',
            method: 'PATCH',
            data: JSON.stringify({
                id: item.id,
                currentPosition: item.posicion,
                direction: item.direction,
                csrf_token,
            }),
            headers: { 'Content-Type': 'application/json' },
        };
        const response = await versaFetch(params);
        if (response.success === 1) {
            refreshTable.value = !refreshTable.value;
        } else {
            await VersaToast.fire({
                icon: 'warning',
                title: response.message,
            });
        }
    };

    const idModuleSelected = ref(0);

    const accion = (
        /** @type {{ item: any; accion: string | number; direction: any; }} */ accion,
    ) => {
        const actions = {
            showEditModule: () => {
                showModalForm.showModalForm = true;
                showModalForm.itemSelected = accion.item;
                showModalForm.action = 'edit';
            },
            changePosition: () => changePosition(accion.item),
            changeStatus: () => changeStatus(accion.item),
            viewSubmenus: () => {
                idModuleSelected.value = accion.item.id;
                showModalSubMenu.value = true;
            },
            refreshData: () => {
                refreshTable.value = !refreshTable.value;
            },
            closeModal: () => {
                showModalSubMenu.value = false;
            },
            default: () => console.log('Accion no encontrada'),
        };
        const action = actions[accion.accion] || actions.default;
        if (typeof action === 'function') {
            action();
        }
    };
</script>

<template>
    <div>
        <customTable
            id="modulesTable"
            :externalFilters="externalFilters"
            :refreshData="refreshTable"
            @accion="accion"
            fieldOrder="seccion"
            tablaTitle="Listado de Modulos de la Aplicación"
            urlData="/admin/modules/getModulesPaginated">
            <template v-slot:buttons>
                <div class="flex justify-end gap-2">
                    <button
                        type="button"
                        class="text-white bg-blue-700 hover:bg-blue-800 focus:ring-4 focus:outline-none focus:ring-blue-300 rounded-lg text-sm px-5 py-2.5 text-center inline-flex items-center dark:bg-blue-600 dark:hover:bg-blue-700 dark:focus:ring-blue-800"
                        :class="
                            buttonSelected === 'Todos'
                                ? 'ring-4 ring-blue-800 font-bold text-current underline underline-offset-8'
                                : 'font-medium '
                        "
                        @click="
                            buttonSelected = 'Todos';
                            setFilterExterno('');
                        ">
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
                        @click="
                            buttonSelected = 'Activos';
                            setFilterExterno('estado = 1');
                        ">
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
                        @click="
                            buttonSelected = 'Inactivos';
                            setFilterExterno('estado = 0');
                        ">
                        Inactivos
                    </button>
                </div>
            </template>
        </customTable>
        <listSubModules
            :showModal="showModalSubMenu"
            @accion="accion"
            :idModule="Number(idModuleSelected)" />
    </div>
</template>
