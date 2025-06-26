import { defineComponent as _defineComponent } from '/node_modules/vue/dist/vue.esm-browser.js';
import Swal from '/node_modules/sweetalert2/dist/sweetalert2.esm.all.js';
// Importar tipos
import { ref, watch } from '/node_modules/vue/dist/vue.esm-browser.js';
import customTable from '@/dashboard/js/components/customTable.vue';
import { API_RESPONSE_CODES } from '@/dashboard/js/constants';
import { versaFetch, VersaToast } from '@/dashboard/js/functions';
import modalUpdatePass from '@/dashboard/js/usuarios/dashUsers/modalUpdatePass.vue';
const tableUsers_component = /*@__PURE__*/ _defineComponent({
    __file: 'C:/Users/jjara/Desktop/proyectos/versaWYS-PHP/src/dashboard/js/usuarios/dashUsers/tableUsers.vue',
    __name: 'tableUsers',
    name: 'tableUsers',
    components: {
        customTable,
        modalUpdatePass
    },
    setup(__props, { expose: __expose }) {
        __expose();
        const versaComponentKey = ref(0);
        const showModal = ref(false);
        const tokenIdSelected = ref('');
        const refreshTable = ref(false);
        const externalFilters = ref('');
        const buttonSelected = ref('Todos');
        let estadoFilter = '';
        const editUser = (tokenid) => {
            // Tipar parámetro
            globalThis.location.href = `/admin/usuarios/editUser/${tokenid}`;
        };
        const changePassword = (tokenid) => {
            // Tipar parámetro
            showModal.value = true;
            tokenIdSelected.value = tokenid;
        };
        const changeStatus = async (item) => {
            // Aplicar tipo a 'item'
            // Define el objeto de opciones y tipifícalo explícitamente como SweetAlertOptions
            const options = '1' === item.status
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
                if (API_RESPONSE_CODES.SUCCESS === response.success) {
                    VersaToast.fire({
                        icon: 'success',
                        title: response.message,
                    });
                    refreshTable.value = !refreshTable.value;
                }
                else {
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
            }
            else if (newValue === 'Activos') {
                estadoFilter = 'status = 1';
            }
            else if (newValue === 'Inactivos') {
                estadoFilter = 'status = 0';
            }
            setFilterExterno();
        });
        const setFilterExterno = () => {
            externalFilters.value = `${estadoFilter}`;
            refreshTable.value = !refreshTable.value;
        };
        const accion = (accion) => {
            const actions = {
                editUser: () => editUser(accion.item.tokenid),
                changePassword: () => changePassword(accion.item.tokenid),
                changeStatus: () => changeStatus(accion.item),
                closeModal: () => closeModal(),
            };
            const action = actions[accion.accion] ||
                (() => console.log('Accion no encontrada'));
            if ('function' === typeof action) {
                action();
            }
        };
        const __returned__ = { versaComponentKey, showModal, tokenIdSelected, refreshTable, externalFilters, buttonSelected, get estadoFilter() { return estadoFilter; }, set estadoFilter(v) { estadoFilter = v; }, editUser, changePassword, changeStatus, closeModal, setFilterExterno, accion, customTable, modalUpdatePass };
        Object.defineProperty(__returned__, '__isScriptSetup', { enumerable: false, value: true });
        return __returned__;
    }
});
import { createElementVNode as _createElementVNode, openBlock as _openBlock, createElementBlock as _createElementBlock, normalizeClass as _normalizeClass, resolveComponent as _resolveComponent, withCtx as _withCtx, createVNode as _createVNode } from "/node_modules/vue/dist/vue.esm-browser.js";
function render_tableUsers_component(_ctx, _cache, $props, $setup, $data, $options) {
    const _component_customTable = _resolveComponent("customTable");
    const _component_modalUpdatePass = _resolveComponent("modalUpdatePass");
    return (_openBlock(), _createElementBlock("div", { key: $setup.versaComponentKey }, [
        _createVNode(_component_customTable, {
            externalFilters: $setup.externalFilters,
            refreshData: $setup.refreshTable,
            onAccion: $setup.accion,
            tablaTitle: "Listado de Usuarios",
            urlData: "/admin/users/getUsersPaginated"
        }, {
            buttons: _withCtx(() => [
                _createElementVNode("div", { class: "flex justify-between gap-2" }, [
                    _createElementVNode("a", {
                        class: "text-white bg-blue-500 hover:bg-blue-700 focus:ring-4 focus:outline-none focus:ring-blue-300 font-medium rounded-lg text-sm px-5 py-2.5 text-center inline-flex items-center dark:bg-blue-600 dark:hover:bg-blue-700 dark:focus:ring-blue-800 cursor-pointer",
                        href: "/admin/usuarios/addUser"
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
                                d: "M9 4a4 4 0 1 0 0 8 4 4 0 0 0 0-8Zm-2 9a4 4 0 0 0-4 4v1a2 2 0 0 0 2 2h8a2 2 0 0 0 2-2v-1a4 4 0 0 0-4-4H7Zm8-1a1 1 0 0 1 1-1h1v-1a1 1 0 1 1 2 0v1h1a1 1 0 1 1 0 2h-1v1a1 1 0 1 1-2 0v-1h-1a1 1 0 0 1-1-1Z",
                                "fill-rule": "evenodd"
                            })
                        ])),
                        _createElementVNode("span", { class: "max-lg:hidden ms-2" }, " Agregar Nuevo usuario ")
                    ]),
                    _createElementVNode("div", { class: "flex justify-end gap-2" }, [
                        _createElementVNode("button", {
                            type: "button",
                            class: _normalizeClass(["text-white bg-blue-700 hover:bg-blue-800 focus:ring-4 focus:outline-none focus:ring-blue-300 rounded-lg text-sm px-5 py-2.5 text-center inline-flex items-center dark:bg-blue-600 dark:hover:bg-blue-700 dark:focus:ring-blue-800",
                                $setup.buttonSelected === 'Todos'
                                    ? 'ring-4 ring-blue-800 font-bold text-current underline underline-offset-8'
                                    : 'font-medium '
                            ]),
                            onClick: $event => ($setup.buttonSelected = 'Todos')
                        }, " Todos ", 10 /* CLASS, PROPS */, ["onClick"]),
                        _createElementVNode("button", {
                            type: "button",
                            class: _normalizeClass(["text-white bg-green-700 hover:bg-green-800 focus:ring-4 focus:outline-none focus:ring-blue-300 rounded-lg text-sm px-5 py-2.5 text-center inline-flex items-center dark:bg-green-600 dark:hover:bg-green-700 dark:focus:ring-green-800",
                                $setup.buttonSelected === 'Activos'
                                    ? 'ring-4 ring-green-800 font-bold text-current  underline underline-offset-8'
                                    : 'font-medium '
                            ]),
                            onClick: $event => ($setup.buttonSelected = 'Activos')
                        }, " Activos ", 10 /* CLASS, PROPS */, ["onClick"]),
                        _createElementVNode("button", {
                            type: "button",
                            class: _normalizeClass(["text-white bg-red-700 hover:bg-red-800 focus:ring-4 focus:outline-none focus:ring-blue-300 rounded-lg text-sm px-5 py-2.5 text-center inline-flex items-center dark:bg-red-600 dark:hover:bg-red-700 dark:focus:ring-red-800",
                                $setup.buttonSelected === 'Inactivos'
                                    ? 'ring-4 ring-red-800 font-bold text-current  underline underline-offset-8'
                                    : 'font-medium '
                            ]),
                            onClick: $event => ($setup.buttonSelected = 'Inactivos')
                        }, " Inactivos ", 10 /* CLASS, PROPS */, ["onClick"])
                    ])
                ])
            ]),
            _: 1 /* STABLE */
        }, 8 /* PROPS */, ["externalFilters", "refreshData"]),
        _createVNode(_component_modalUpdatePass, {
            showModal: $setup.showModal,
            tokenId: $setup.tokenIdSelected,
            onAccion: $event => ($setup.showModal = false),
            origen: "usersPpal"
        }, null, 8 /* PROPS */, ["showModal", "tokenId", "onAccion"])
    ]));
}
tableUsers_component.render = render_tableUsers_component;
export default tableUsers_component;
//# sourceMappingURL=tableUsers.vue.js.map