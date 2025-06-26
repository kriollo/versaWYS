import { defineComponent as _defineComponent } from '/node_modules/vue/dist/vue.esm-browser.js';
import Swal from '/node_modules/sweetalert2/dist/sweetalert2.esm.all.js';
import { computed, inject, ref, watch } from '/node_modules/vue/dist/vue.esm-browser.js';
import { versaFetch, VersaToast } from '@/dashboard/js/functions';
import { API_RESPONSE_CODES } from '../constants.js';
const listPerfilesSide_component = /*@__PURE__*/ _defineComponent({
    __file: 'C:/Users/jjara/Desktop/proyectos/versaWYS-PHP/src/dashboard/js/perfil/listPerfilesSide.vue',
    __name: 'listPerfilesSide',
    name: 'listPerfilesSide',
    components: {},
    props: {
        refreshData: {
            type: Boolean,
            dafault: false,
        },
    },
    setup(__props, { expose: __expose }) {
        __expose();
        const versaComponentKey = ref(0);
        const props = __props;
        const perfil = inject('perfil');
        const refreshData = computed(() => props.refreshData);
        const data = ref([]);
        const listPerfilesSide = async () => {
            const response = await versaFetch({
                url: '/admin/perfiles/all',
                method: 'GET',
            });
            if (response.success) {
                data.value = response.data;
            }
        };
        const editPerfil = (perfilSelected) => {
            perfil.value = perfilSelected;
        };
        const deletePerfil = async (perfil) => {
            const result = await Swal.fire({
                title: 'Actualizar estado del Perfil',
                text: `¿Estás seguro de cambiar el estado del perfil ${perfil.nombre}?`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Actualizar',
                cancelButtonText: 'Cancelar',
            });
            if (result.isConfirmed) {
                const response = await versaFetch({
                    url: '/admin/perfiles/changeState',
                    method: 'PATCH',
                    data: { id: perfil.id },
                });
                if (API_RESPONSE_CODES.SUCCESS === response.success) {
                    listPerfilesSide();
                    VersaToast.fire({
                        icon: 'success',
                        title: 'Perfil actualizado correctamente',
                    });
                }
                else {
                    VersaToast.fire({
                        icon: 'error',
                        title: 'Error al actualizar el estado del perfil',
                    });
                }
            }
        };
        watch(refreshData, () => {
            listPerfilesSide();
        });
        const __returned__ = { versaComponentKey, props, perfil, refreshData, data, listPerfilesSide, editPerfil, deletePerfil };
        Object.defineProperty(__returned__, '__isScriptSetup', { enumerable: false, value: true });
        return __returned__;
    }
});
import { renderList as _renderList, Fragment as _Fragment, openBlock as _openBlock, createElementBlock as _createElementBlock, toDisplayString as _toDisplayString, normalizeClass as _normalizeClass, createElementVNode as _createElementVNode } from "/node_modules/vue/dist/vue.esm-browser.js";
function render_listPerfilesSide_component(_ctx, _cache, $props, $setup, $data, $options) {
    return (_openBlock(), _createElementBlock("div", { key: $setup.versaComponentKey }, [
        _createElementVNode("ul", { class: "w-full text-sm font-medium text-gray-900 bg-white border border-gray-200 rounded-lg dark:bg-gray-700 dark:border-gray-600 dark:text-white" }, [
            (_openBlock(true), _createElementBlock(_Fragment, null, _renderList($setup.data, (item, key) => {
                return (_openBlock(), _createElementBlock("li", {
                    key: item.id,
                    class: _normalizeClass(["flex justify-between items-center",
                        key === $setup.data.length - 1
                            ? 'rounded-b-lg'
                            : 'w-full px-4 py-2 border-b border-gray-200 rounded-t-lg dark:border-gray-600'
                    ])
                }, [
                    _createElementVNode("div", null, [
                        _createElementVNode("span", {
                            class: _normalizeClass(item.estado === '0' ? 'line-through' : '')
                        }, _toDisplayString(item.nombre), 3 /* TEXT, CLASS */)
                    ]),
                    _createElementVNode("div", { class: "flex gap-2" }, [
                        _createElementVNode("button", {
                            type: "button",
                            class: "text-xs text-blue-500 dark:text-blue-400",
                            onClick: $event => ($setup.editPerfil(item))
                        }, " Editar ", 8 /* PROPS */, ["onClick"]),
                        _createElementVNode("button", {
                            type: "button",
                            class: _normalizeClass(["text-xs",
                                item.estado === '0'
                                    ? 'text-green-500 dark:text-green-400'
                                    : 'text-red-500 dark:text-red-400'
                            ]),
                            onClick: $event => ($setup.deletePerfil(item))
                        }, _toDisplayString(item.estado === '0' ? 'Activar' : 'Desactivar'), 11 /* TEXT, CLASS, PROPS */, ["onClick"])
                    ])
                ], 2 /* CLASS */));
            }), 128 /* KEYED_FRAGMENT */))
        ])
    ]));
}
listPerfilesSide_component.render = render_listPerfilesSide_component;
export default listPerfilesSide_component;
//# sourceMappingURL=listPerfilesSide.vue.js.map