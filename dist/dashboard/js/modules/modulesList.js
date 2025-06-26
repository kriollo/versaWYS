import { defineComponent as _defineComponent } from '/node_modules/vue/dist/vue.esm-browser.js';
import Swal from '/node_modules/sweetalert2/dist/sweetalert2.esm.all.js';
import { inject, ref } from '/node_modules/vue/dist/vue.esm-browser.js';
import customTable from '@/dashboard/js/components/customTable.vue';
import { convertDataTypes, versaFetch, VersaToast, } from '@/dashboard/js/functions';
import { ShowModalFormInjection, } from '@/dashboard/js/modules/InjectKeys';
import subModulesList from '@/dashboard/js/modules/subModulesList.vue';
import { API_RESPONSE_CODES, GLOBAL_CONSTANTS, } from '@/dashboard/js/constants';
const modulesList_component = /*@__PURE__*/ _defineComponent({
    __file: 'C:/Users/jjara/Desktop/proyectos/versaWYS-PHP/src/dashboard/js/modules/modulesList.vue',
    __name: 'modulesList',
    name: 'modulesList',
    components: {
        customTable,
        subModulesList
    },
    setup(__props, { expose: __expose }) {
        __expose();
        const versaComponentKey = ref(0);
        const externalFilters = ref('');
        const buttonSelected = ref('Todos');
        const refreshTable = ref(false);
        const showModalSubMenu = ref(false);
        const showModalForm = ShowModalFormInjection.inject();
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
                        estado: '1' === item.estado ? '0' : '1',
                        csrf_token,
                    }),
                    headers: { 'Content-Type': 'application/json' },
                };
                const response = await versaFetch(params);
                if (API_RESPONSE_CODES.SUCCESS === response.success) {
                    await VersaToast.fire({
                        icon: 'success',
                        title: response.message,
                    });
                    location.reload();
                }
                else {
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
            if (API_RESPONSE_CODES.SUCCESS === response.success) {
                refreshTable.value = !refreshTable.value;
            }
            else {
                await VersaToast.fire({
                    icon: 'warning',
                    title: response.message,
                });
            }
        };
        const idModuleSelected = ref(GLOBAL_CONSTANTS.ZERO);
        const accion = (accion) => {
            const actions = {
                openModalForm: () => {
                    showModalForm.itemSelected = null;
                    showModalForm.action = 'new';
                    showModalForm.showModalForm = true;
                },
                showEditModule: () => {
                    showModalForm.showModalForm = true;
                    showModalForm.itemSelected =
                        convertDataTypes([accion.item], [
                            { key: 'estado', type: 'boolean' },
                            { key: 'fill', type: 'boolean' },
                        ])[GLOBAL_CONSTANTS.ZERO] ?? null;
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
            if ('function' === typeof action) {
                action();
            }
        };
        const __returned__ = { versaComponentKey, externalFilters, buttonSelected, refreshTable, showModalSubMenu, showModalForm, csrf_token, setFilterExterno, changeStatus, changePosition, idModuleSelected, accion, customTable, subModulesList };
        Object.defineProperty(__returned__, '__isScriptSetup', { enumerable: false, value: true });
        return __returned__;
    }
});
import { createElementVNode as _createElementVNode, openBlock as _openBlock, createElementBlock as _createElementBlock, normalizeClass as _normalizeClass, resolveComponent as _resolveComponent, withCtx as _withCtx, createVNode as _createVNode } from "/node_modules/vue/dist/vue.esm-browser.js";
function render_modulesList_component(_ctx, _cache, $props, $setup, $data, $options) {
    const _component_customTable = _resolveComponent("customTable");
    const _component_subModulesList = _resolveComponent("subModulesList");
    return (_openBlock(), _createElementBlock("div", { key: $setup.versaComponentKey }, [
        _createVNode(_component_customTable, {
            id: "modulesTable",
            key: "modulesTable",
            externalFilters: $setup.externalFilters,
            refreshData: $setup.refreshTable,
            onAccion: $setup.accion,
            fieldOrder: "seccion",
            tablaTitle: "Listado de Modulos de la Aplicación",
            urlData: "/admin/modules/getModulesPaginated"
        }, {
            buttons: _withCtx(() => [
                _createElementVNode("div", { class: "flex justify-between gap-2" }, [
                    _createElementVNode("a", {
                        class: "text-white bg-blue-500 hover:bg-blue-700 focus:ring-4 focus:outline-none focus:ring-blue-300 font-medium rounded-lg text-sm px-5 py-2.5 text-center inline-flex items-center dark:bg-blue-600 dark:hover:bg-blue-700 dark:focus:ring-blue-800 cursor-pointer",
                        onClick: $event => ($setup.accion({ accion: 'openModalForm' }))
                    }, [
                        (_openBlock(), _createElementBlock("svg", {
                            class: "w-[20px] h-[20px] text-gray-800 dark:text-white",
                            fill: "currentColor",
                            height: "24",
                            viewBox: "0 0 24 24",
                            width: "24",
                            xmlns: "http://www.w3.org/2000/svg"
                        }, [
                            _createElementVNode("path", {
                                "clip-rule": "evenodd",
                                d: "M4.857 3A1.857 1.857 0 0 0 3 4.857v4.286C3 10.169 3.831 11 4.857 11h4.286A1.857 1.857 0 0 0 11 9.143V4.857A1.857 1.857 0 0 0 9.143 3H4.857Zm10 0A1.857 1.857 0 0 0 13 4.857v4.286c0 1.026.831 1.857 1.857 1.857h4.286A1.857 1.857 0 0 0 21 9.143V4.857A1.857 1.857 0 0 0 19.143 3h-4.286Zm-10 10A1.857 1.857 0 0 0 3 14.857v4.286C3 20.169 3.831 21 4.857 21h4.286A1.857 1.857 0 0 0 11 19.143v-4.286A1.857 1.857 0 0 0 9.143 13H4.857ZM18 14a1 1 0 1 0-2 0v2h-2a1 1 0 1 0 0 2h2v2a1 1 0 1 0 2 0v-2h2a1 1 0 1 0 0-2h-2v-2Z",
                                "fill-rule": "evenodd"
                            })
                        ])),
                        _createElementVNode("span", { class: "max-lg:hidden ms-2" }, "Nuevo Modulo")
                    ], 8 /* PROPS */, ["onClick"]),
                    _createElementVNode("div", { class: "flex justify-end gap-2" }, [
                        _createElementVNode("button", {
                            type: "button",
                            class: _normalizeClass(["text-white bg-blue-700 hover:bg-blue-800 focus:ring-4 focus:outline-none focus:ring-blue-300 rounded-lg text-sm px-5 py-2.5 text-center inline-flex items-center dark:bg-blue-600 dark:hover:bg-blue-700 dark:focus:ring-blue-800",
                                $setup.buttonSelected === 'Todos'
                                    ? 'ring-4 ring-blue-800 font-bold text-current underline underline-offset-8'
                                    : 'font-medium '
                            ]),
                            onClick: $event => {
                                $setup.buttonSelected = 'Todos';
                                $setup.setFilterExterno('');
                            }
                        }, " Todos ", 10 /* CLASS, PROPS */, ["onClick"]),
                        _createElementVNode("button", {
                            type: "button",
                            class: _normalizeClass(["text-white bg-green-700 hover:bg-green-800 focus:ring-4 focus:outline-none focus:ring-blue-300 rounded-lg text-sm px-5 py-2.5 text-center inline-flex items-center dark:bg-green-600 dark:hover:bg-green-700 dark:focus:ring-green-800",
                                $setup.buttonSelected === 'Activos'
                                    ? 'ring-4 ring-green-800 font-bold text-current  underline underline-offset-8'
                                    : 'font-medium '
                            ]),
                            onClick: $event => {
                                $setup.buttonSelected = 'Activos';
                                $setup.setFilterExterno('estado = 1');
                            }
                        }, " Activos ", 10 /* CLASS, PROPS */, ["onClick"]),
                        _createElementVNode("button", {
                            type: "button",
                            class: _normalizeClass(["text-white bg-red-700 hover:bg-red-800 focus:ring-4 focus:outline-none focus:ring-blue-300 rounded-lg text-sm px-5 py-2.5 text-center inline-flex items-center dark:bg-red-600 dark:hover:bg-red-700 dark:focus:ring-red-800",
                                $setup.buttonSelected === 'Inactivos'
                                    ? 'ring-4 ring-red-800 font-bold text-current  underline underline-offset-8'
                                    : 'font-medium '
                            ]),
                            onClick: $event => {
                                $setup.buttonSelected = 'Inactivos';
                                $setup.setFilterExterno('estado = 0');
                            }
                        }, " Inactivos ", 10 /* CLASS, PROPS */, ["onClick"])
                    ])
                ])
            ]),
            _: 1 /* STABLE */
        }, 8 /* PROPS */, ["externalFilters", "refreshData"]),
        _createVNode(_component_subModulesList, {
            showModal: $setup.showModalSubMenu,
            onAccion: $setup.accion,
            idModule: Number($setup.idModuleSelected)
        }, null, 8 /* PROPS */, ["showModal", "idModule"])
    ]));
}
modulesList_component.render = render_modulesList_component;
export default modulesList_component;
//# sourceMappingURL=modulesList.vue.js.map