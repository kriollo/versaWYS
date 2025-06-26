import { defineComponent as _defineComponent } from '/node_modules/vue/dist/vue.esm-browser.js';
import Swal from '/node_modules/sweetalert2/dist/sweetalert2.esm.all.js';
import { computed, inject, provide, reactive, ref } from '/node_modules/vue/dist/vue.esm-browser.js';
import customTable from '@/dashboard/js/components/customTable.vue';
import modal from '@/dashboard/js/components/modal.vue';
import { convertDataTypes, versaFetch, VersaToast, } from '@/dashboard/js/functions';
import { ShowModalSubFormInjection, } from '@/dashboard/js/modules/InjectKeys';
import subModulesForm from '@/dashboard/js/modules/subModulesForm.vue';
import { API_RESPONSE_CODES, GLOBAL_CONSTANTS, } from '@/dashboard/js/constants';
const subModulesList_component = /*@__PURE__*/ _defineComponent({
    __file: 'C:/Users/jjara/Desktop/proyectos/versaWYS-PHP/src/dashboard/js/modules/subModulesList.vue',
    __name: 'subModulesList',
    name: 'subModulesList',
    components: {
        customTable,
        modal,
        subModulesForm
    },
    props: {
        showModal: {
            type: Boolean,
            default: false,
        },
        idModule: {
            type: Number,
            default: 0,
        },
    },
    emits: ['accion'],
    setup(__props, { expose: __expose, emit: __emit }) {
        __expose();
        const versaComponentKey = ref(0);
        const emit = __emit;
        const props = __props;
        const showModal = computed(() => props.showModal);
        const idModule = computed(() => props.idModule);
        const showModalSubForm = reactive({
            showModalSubForm: false,
            itemSelected: null,
            action: '',
        });
        ShowModalSubFormInjection.provide(showModalSubForm);
        provide('id_menu', idModule);
        const csrf_token = inject('csrf_token');
        const refreshTable = ref(false);
        const changeStatus = async (
        /** @type {Object} */ item) => {
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
                        estado: '1' === item.estado ? '0' : '1',
                        csrf_token,
                    }),
                    headers: { 'Content-Type': 'application/json' },
                };
                const response = await versaFetch(params);
                if (API_RESPONSE_CODES.SUCCESS === response.success) {
                    refreshTable.value = !refreshTable.value;
                    await VersaToast.fire({
                        icon: 'success',
                        title: response.message,
                    });
                    emit('accion', { accion: 'refreshData' });
                }
                else {
                    await VersaToast.fire({
                        icon: 'warning',
                        title: response.message,
                    });
                }
            }
        };
        const changePosition = async (
        /** @type {Object} */ item) => {
            const { value } = await Swal.fire({
                title: 'Cambiar posición',
                icon: 'question',
                input: 'number',
                inputValue: item.posicion.toString(),
                inputOptions: {
                    min: '1',
                },
                inputAttributes: {
                    min: '1',
                },
                showCancelButton: true,
                confirmButtonText: 'Cambiar',
                cancelButtonText: 'Cancelar',
                inputValidator: value => {
                    if (!value) {
                        return 'Debes ingresar una posición';
                    }
                },
            });
            if (value) {
                const params = {
                    url: '/admin/submodules/changePositionSubModule',
                    method: 'PATCH',
                    data: JSON.stringify({
                        id: item.id,
                        id_menu: item.id_menu,
                        value,
                        csrf_token,
                    }),
                    headers: { 'Content-Type': 'application/json' },
                };
                const response = await versaFetch(params);
                if (API_RESPONSE_CODES.SUCCESS === response.success) {
                    refreshTable.value = !refreshTable.value;
                    await VersaToast.fire({
                        icon: 'success',
                        title: response.message,
                    });
                    emit('accion', { accion: 'refreshData' });
                }
                else {
                    await VersaToast.fire({
                        icon: 'warning',
                        title: response.message,
                    });
                }
            }
        };
        const accion = (payload) => {
            const actions = {
                create: () => {
                    showModalSubForm.itemSelected = null;
                    showModalSubForm.action = 'new';
                    showModalSubForm.showModalSubForm = true;
                },
                showEditSubModule: () => {
                    showModalSubForm.itemSelected =
                        convertDataTypes([payload.item], [
                            {
                                key: 'estado',
                                type: 'boolean',
                            },
                        ])[GLOBAL_CONSTANTS.ZERO] ?? null;
                    showModalSubForm.action = 'edit';
                    showModalSubForm.showModalSubForm = true;
                },
                changeStatusSubMenu: () => changeStatus(payload.item),
                closeModal: () => emit('accion', { accion: 'closeModal' }),
                changePosition: () => changePosition(payload.item),
                default: () => console.log('Accion no encontrada'),
            };
            const selectedAction = actions[payload.accion] || actions['default'];
            if ('function' === typeof selectedAction) {
                selectedAction();
            }
        };
        const __returned__ = { versaComponentKey, emit, props, showModal, idModule, showModalSubForm, csrf_token, refreshTable, changeStatus, changePosition, accion, customTable, modal, subModulesForm };
        Object.defineProperty(__returned__, '__isScriptSetup', { enumerable: false, value: true });
        return __returned__;
    }
});
import { createElementVNode as _createElementVNode, createTextVNode as _createTextVNode, openBlock as _openBlock, createElementBlock as _createElementBlock, resolveComponent as _resolveComponent, createVNode as _createVNode, withCtx as _withCtx, createBlock as _createBlock, createCommentVNode as _createCommentVNode } from "/node_modules/vue/dist/vue.esm-browser.js";
function render_subModulesList_component(_ctx, _cache, $props, $setup, $data, $options) {
    const _component_subModulesForm = _resolveComponent("subModulesForm");
    const _component_customTable = _resolveComponent("customTable");
    const _component_modal = _resolveComponent("modal");
    return (_openBlock(), _createBlock(_component_modal, {
        showModal: $setup.showModal,
        idModal: "modalSubModule",
        size: "max-w-7xl",
        onAccion: $setup.accion,
        key: $setup.versaComponentKey
    }, {
        modalTitle: _withCtx(() => [
            _createElementVNode("div", { class: "flex justify-between" }, [
                _createElementVNode("h3", { class: "text-lg font-medium text-gray-900 dark:text-white" }, [
                    _createElementVNode("i", { class: "bi bi-menu-app-fill text-2xl text-blue-500 dark:text-blue-300" }),
                    _createTextVNode(" Listado de Sub Módulos ")
                ]),
                _createElementVNode("div", { class: "float-left" }, [
                    _createElementVNode("button", {
                        type: "button",
                        class: "text-gray-400 bg-transparent hover:bg-gray-200 hover:text-gray-900 rounded-lg text-sm w-8 h-8 ms-auto inline-flex justify-center items-center dark:hover:bg-gray-600 dark:hover:text-white",
                        onClick: $event => ($setup.accion({ accion: 'closeModal' }))
                    }, [
                        (_openBlock(), _createElementBlock("svg", {
                            class: "w-3 h-3",
                            fill: "none",
                            viewBox: "0 0 14 14",
                            xmlns: "http://www.w3.org/2000/svg"
                        }, [
                            _createElementVNode("path", {
                                d: "m1 1 6 6m0 0 6 6M7 7l6-6M7 7l-6 6",
                                stroke: "currentColor",
                                "stroke-linecap": "round",
                                "stroke-linejoin": "round",
                                "stroke-width": "2"
                            })
                        ]))
                    ], 8 /* PROPS */, ["onClick"])
                ])
            ])
        ]),
        modalBody: _withCtx(() => [
            _createVNode(_component_subModulesForm, { onAccion: $setup.accion }),
            ($setup.showModal)
                ? (_openBlock(), _createBlock(_component_customTable, {
                    onAccion: $setup.accion,
                    id: "subModulesTable",
                    key: "subModulesTable",
                    tablaTitle: "Listado de Sub Módulos",
                    urlData: "/admin/modules/getSubModules",
                    externalFilters: 'id_menu=' + $setup.idModule,
                    fieldOrder: "posicion",
                    refreshData: $setup.refreshTable
                }, {
                    buttons: _withCtx(() => [
                        _createElementVNode("div", { class: "flex justify-end gap-2" }, [
                            _createElementVNode("button", {
                                type: "button",
                                class: "text-white bg-blue-700 hover:bg-blue-800 focus:ring-4 focus:outline-none focus:ring-blue-300 rounded-lg text-sm px-5 py-2.5 text-center inline-flex items-center dark:bg-blue-600 dark:hover:bg-blue-700 dark:focus:ring-blue-800",
                                onClick: $event => ($setup.accion({ accion: 'create' }))
                            }, " Crear ", 8 /* PROPS */, ["onClick"])
                        ])
                    ]),
                    _: 1 /* STABLE */
                }, 8 /* PROPS */, ["externalFilters", "refreshData"]))
                : _createCommentVNode("v-if", true)
        ]),
        modalFooter: _withCtx(() => [
            _createElementVNode("button", {
                type: "button",
                class: "text-white bg-gray-700 hover:bg-gray-800 focus:ring-4 focus:outline-none focus:ring-gray-300 rounded-lg text-sm px-5 py-2.5 text-center inline-flex items-center dark:bg-gray-600 dark:hover:bg-gray-700 dark:focus:ring-gray-800",
                onClick: $event => ($setup.accion({ accion: 'closeModal' }))
            }, " Cerrar ", 8 /* PROPS */, ["onClick"])
        ]),
        _: 1 /* STABLE */
    }, 8 /* PROPS */, ["showModal"]));
}
subModulesList_component.render = render_subModulesList_component;
export default subModulesList_component;
//# sourceMappingURL=subModulesList.vue.js.map