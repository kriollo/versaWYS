<script setup>
    import { customTable } from '@/dashboard/js/components/customTable';
    import { modal } from '@/dashboard/js/components/modal';
    import { versaFetch, VersaToast } from '@/dashboard/js/functions';
    import { subModulesForm } from '@/dashboard/js/modules/subModulesForm';
    import Swal from 'sweetalert2';
    import { computed, inject, provide, reactive, ref } from 'vue';

    const emit = defineEmits(['accion']);

    const props = defineProps({
        showModal: {
            type: Boolean,
            default: false,
        },
        idModule: {
            type: Number,
            default: 0,
        },
    });
    const showModal = computed(() => props.showModal);
    const idModule = computed(() => props.idModule);

    const ShowModalSubForm = reactive({
        ShowModalSubForm: false,
        itemSelected: null,
        action: '',
    });
    provide('ShowModalSubForm', ShowModalSubForm);
    provide('id_menu', idModule);

    const csrf_token = inject('csrf_token');
    const refreshTable = ref(false);

    const changeStatus = async (/** @type {Object} */ item) => {
        const result = await Swal.fire({
            title: '¿Estás seguro?',
            text: `Estás a punto de cambiar el estado del sub módulo ${item.nombre}`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Sí, cambiar estado',
            cancelButtonText: 'Cancelar',
        });
        if (result.isConfirmed) {
            const params = {
                url: '/admin/submodules/changeStatus',
                method: 'PATCH',
                data: JSON.stringify({
                    id: item.id,
                    id_menu: item.id_menu,
                    estado: item.estado === '1' ? '0' : '1',
                    csrf_token,
                }),
                headers: { 'Content-Type': 'application/json' },
            };
            const response = await versaFetch(params);
            if (response.success === 1) {
                refreshTable.value = !refreshTable.value;
                await VersaToast.fire({
                    icon: 'success',
                    title: response.message,
                });
                emit('accion', { accion: 'refreshData' });
            } else {
                await VersaToast.fire({
                    icon: 'warning',
                    title: response.message,
                });
            }
        }
    };

    const changePosition = async (/** @type {Object} */ item) => {
        const { value: position } = await Swal.fire({
            title: 'Cambiar posición',
            input: 'number',
            inputValue:
                Number(item.posicion) + (item.direction === 'up' ? -1 : 1),
            inputAttributes: {
                min: 1,
            },
            showCancelButton: true,
            confirmButtonText: 'Cambiar',
            cancelButtonText: 'Cancelar',
        });
        if (position) {
            const params = {
                url: '/admin/submodules/changePositionSubModule',
                method: 'PATCH',
                data: JSON.stringify({
                    id: item.id,
                    id_menu: item.id_menu,
                    position,
                    csrf_token,
                }),
                headers: { 'Content-Type': 'application/json' },
            };
            const response = await versaFetch(params);
            if (response.success === 1) {
                refreshTable.value = !refreshTable.value;
                await VersaToast.fire({
                    icon: 'success',
                    title: response.message,
                });
                emit('accion', { accion: 'refreshData' });
            } else {
                await VersaToast.fire({
                    icon: 'warning',
                    title: response.message,
                });
            }
        }
    };

    const accion = (/** @type {Object} */ payload) => {
        const actions = {
            create: () => {
                ShowModalSubForm.itemSelected = null;
                ShowModalSubForm.action = 'new';
                ShowModalSubForm.ShowModalSubForm = true;
            },
            showEditSubModule: () => {
                ShowModalSubForm.itemSelected = payload.item;
                ShowModalSubForm.action = 'edit';
                ShowModalSubForm.ShowModalSubForm = true;
            },
            changeStatusSubMenu: () => changeStatus(payload.item),
            closeModal: () => emit('accion', { accion: 'closeModal' }),
            changePosition: () => changePosition(payload.item),
            default: () => console.log('Accion no encontrada'),
        };

        const selectedAction = actions[payload.accion] || actions['default'];
        if (typeof selectedAction === 'function') {
            selectedAction();
        }
    };
</script>
<template>
    <modal
        :showModal="showModal"
        idModal="modalSubModule"
        size="max-w-7xl"
        @accion="accion">
        <template #modalTitle>
            <div class="flex justify-between">
                <h3 class="text-lg font-medium text-gray-900 dark:text-white">
                    <i
                        class="bi bi-menu-app-fill text-2xl text-blue-500 dark:text-blue-300"></i>
                    Listado de Sub Modulos
                </h3>
                <div class="float-left">
                    <button
                        type="button"
                        class="text-gray-400 bg-transparent hover:bg-gray-200 hover:text-gray-900 rounded-lg text-sm w-8 h-8 ms-auto inline-flex justify-center items-center dark:hover:bg-gray-600 dark:hover:text-white"
                        @click="accion({ accion: 'closeModal' })">
                        <svg
                            class="w-3 h-3"
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
        <template #modalBody>
            <customTable
                @accion="accion"
                v-if="showModal"
                id="subModulesTable"
                tablaTitle="Listado de Sub Modulos"
                urlData="/admin/modules/getSubModules"
                :externalFilters="'id_menu=' + idModule"
                fieldOrder="posicion"
                :refreshData="refreshTable">
                <template v-slot:buttons>
                    <div class="flex justify-end gap-2">
                        <button
                            type="button"
                            class="text-white bg-blue-700 hover:bg-blue-800 focus:ring-4 focus:outline-none focus:ring-blue-300 rounded-lg text-sm px-5 py-2.5 text-center inline-flex items-center dark:bg-blue-600 dark:hover:bg-blue-700 dark:focus:ring-blue-800"
                            @click="accion({ accion: 'create' })">
                            Crear
                        </button>
                    </div>
                </template>
            </customTable>
            <subModulesForm @accion="accion" />
        </template>
        <template #modalFooter>
            <button
                type="button"
                class="text-white bg-blue-700 hover:bg-blue-800 focus:ring-4 focus:outline-none focus:ring-blue-300 rounded-lg text-sm px-5 py-2.5 text-center inline-flex items-center dark:bg-blue-600 dark:hover:bg-blue-700 dark:focus:ring-blue-800"
                @click="accion({ accion: 'closeModal' })">
                Cerrar
            </button>
        </template>
    </modal>
</template>
