import { defineComponent as _defineComponent } from '/node_modules/vue/dist/vue.esm-browser.js';

import Swal from '/node_modules/sweetalert2/dist/sweetalert2.esm.all.js';
import { onMounted, provide, ref } from '/node_modules/vue/dist/vue.esm-browser.js';
import breadcrumb from '@/dashboard/js/components/breadcrumb.vue';
import lineHr from '@/dashboard/js/components/lineHr.vue';
import { $dom } from '@/dashboard/js/composables/dom';
import { versaFetch, VersaToast } from '@/dashboard/js/functions';
import listPerfilesSide from '@/dashboard/js/perfil/listPerfilesSide.vue';
import perfilSide from '@/dashboard/js/perfil/perfilSide.vue';
const dashPerfiles_component = /*@__PURE__*/ _defineComponent({
    __file: 'C:/Users/jjara/Desktop/proyectos/versaWYS-PHP/src/dashboard/js/perfil/dashPerfiles.vue',
    __name: 'dashPerfiles',
    name: 'dashPerfiles',
    components: {
        breadcrumb,
        lineHr,
        listPerfilesSide,
        perfilSide
    },
    setup(__props, { expose: __expose }) {
        __expose();
        const versaComponentKey = ref(0);
        const refreshData = ref(false);
        const perfil = ref({
            id: 0,
            nombre: '',
            pagina_inicio: '',
        });
        provide('perfil', perfil);
        const newPerfil = async () => {
            const result = await Swal.fire({
                title: 'Nuevo Perfil',
                html: `
                <input
                    id="nombre"
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-1 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-800 dark:border-gray-600 dark:focus:ring-blue-600 dark:focus:border-blue-600"
                    placeholder="Nombre del Perfil"
                    type="text" />
            `,
                showCancelButton: true,
                confirmButtonText: 'Guardar',
                cancelButtonText: 'Cancelar',
                focusConfirm: false,
                preConfirm: () => {
                    const $input = $dom('#nombre');
                    if ($input instanceof HTMLInputElement) {
                        if (!$input.value) {
                            Swal.showValidationMessage('El nombre del perfil es requerido');
                            return false;
                        }
                        return {
                            nombre: $input.value,
                        };
                    }
                },
            });
            if (result.isConfirmed) {
                const response = await versaFetch({
                    url: '/admin/perfiles/save',
                    method: 'POST',
                    data: { nombre: result.value.nombre, estado: 1 },
                });
                if (response.success) {
                    refreshData.value = !refreshData.value;
                    VersaToast.fire({
                        icon: 'success',
                        title: 'Perfil guardado correctamente',
                    });
                }
                else {
                    VersaToast.fire({
                        icon: 'error',
                        title: 'Error al guardar el perfil',
                    });
                }
            }
        };
        const accion = (accion) => {
            const actions = {
                refreshData: () => {
                    refreshData.value = !refreshData.value;
                },
                default: () => console.log('Accion no encontrada'),
            };
            const selectedAction = actions[accion.accion] || actions['default'];
            if ('function' === typeof selectedAction) {
                selectedAction();
            }
        };
        onMounted(() => {
            refreshData.value = !refreshData.value;
        });
        const breadCrumb = [
            {
                type: 'link',
                title: 'Home',
                icon: '<svg class="w-3 h-3 me-2.5" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg"><path d="m19.707 9.293-2-2-7-7a1 1 0 0 0-1.414 0l-7 7-2 2a1 1 0 0 0 1.414 1.414L2 10.414V18a2 2 0 0 0 2 2h3a1 1 0 0 0 1-1v-4a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1v4a1 1 0 0 0 1 1h3a2 2 0 0 0 2-2v-7.586l.293.293a1 1 0 0 0 1.414-1.414Z"/></svg>',
                link: '/admin/dashboard',
            },
            {
                type: 'link',
                title: 'Perfiles',
                icon: '<svg class="w-3 h-3 text-gray-400 mx-1" fill="none" viewBox="0 0 6 10" xmlns="http://www.w3.org/2000/svg"><path d="m1 9 4-4-4-4" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"/></svg>',
                link: '/admin/perfiles',
            },
            {
                type: 'text',
                title: 'Mantenedor de Perfiles',
                icon: '<svg class="w-3 h-3 text-gray-400 mx-1" fill="none" viewBox="0 0 6 10" xmlns="http://www.w3.org/2000/svg"><path d="m1 9 4-4-4-4" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"/></svg>',
                link: '',
            },
        ];
        const __returned__ = { versaComponentKey, refreshData, perfil, newPerfil, accion, breadCrumb, breadcrumb, lineHr, listPerfilesSide, perfilSide };
        Object.defineProperty(__returned__, '__isScriptSetup', { enumerable: false, value: true });
        return __returned__;
    }
});
import { resolveComponent as _resolveComponent, createVNode as _createVNode, createElementVNode as _createElementVNode, openBlock as _openBlock, createElementBlock as _createElementBlock } from "/node_modules/vue/dist/vue.esm-browser.js";
function render_dashPerfiles_component(_ctx, _cache, $props, $setup, $data, $options) {
    const _component_breadcrumb = _resolveComponent("breadcrumb");
    const _component_lineHr = _resolveComponent("lineHr");
    const _component_listPerfilesSide = _resolveComponent("listPerfilesSide");
    const _component_perfilSide = _resolveComponent("perfilSide");
    return (_openBlock(), _createElementBlock("div", {
        class: "w-full h-full flex flex-col",
        key: $setup.versaComponentKey
    }, [
        _createVNode(_component_breadcrumb, {
            title: "Perfiles",
            iconSVG: "<svg\r\n                    class=\"w-[32px] h-[32px] text-gray-800 dark:text-white\"\r\n                    aria-hidden=\"true\"\r\n                    xmlns=\"http://www.w3.org/2000/svg\"\r\n                    width=\"24\"\r\n                    height=\"24\"\r\n                    fill=\"currentColor\"\r\n                    viewbox=\"0 0 24 24\">\r\n                    <path\r\n                        d=\"M10.83 5a3.001 3.001 0 0 0-5.66 0H4a1 1 0 1 0 0 2h1.17a3.001 3.001 0 0 0 5.66 0H20a1 1 0 1 0 0-2h-9.17ZM4 11h9.17a3.001 3.001 0 0 1 5.66 0H20a1 1 0 1 1 0 2h-1.17a3.001 3.001 0 0 1-5.66 0H4a1 1 0 1 1 0-2Zm1.17 6H4a1 1 0 1 0 0 2h1.17a3.001 3.001 0 0 0 5.66 0H20a1 1 0 1 0 0-2h-9.17a3.001 3.001 0 0 0-5.66 0Z\" />\r\n                </svg>",
            items: $setup.breadCrumb
        }),
        _createElementVNode("div", { class: "flex-1 relative shadow-md sm:rounded-lg mx-4 overflow-y-auto" }, [
            _createVNode(_component_lineHr),
            _createElementVNode("div", { class: "flex justify-between items-center pb-2" }, [
                _createElementVNode("a", {
                    onClick: $setup.newPerfil,
                    class: "text-white bg-blue-500 hover:bg-blue-700 focus:ring-4 focus:outline-none focus:ring-blue-300 font-medium rounded-lg text-sm px-5 py-2.5 text-center inline-flex items-center dark:bg-blue-600 dark:hover:bg-blue-700 dark:focus:ring-blue-800 cursor-pointer"
                }, [
                    (_openBlock(), _createElementBlock("svg", {
                        class: "w-[20px] h-[20px] text-gray-800 dark:text-white",
                        "aria-hidden": "true",
                        xmlns: "http://www.w3.org/2000/svg",
                        width: "24",
                        height: "24",
                        fill: "currentColor"
                    }, [
                        _createElementVNode("path", { d: "M10.83 5a3.001 3.001 0 0 0-5.66 0H4a1 1 0 1 0 0 2h1.17a3.001 3.001 0 0 0 5.66 0H20a1 1 0 1 0 0-2h-9.17ZM4 11h9.17a3.001 3.001 0 0 1 5.66 0H20a1 1 0 1 1 0 2h-1.17a3.001 3.001 0 0 1-5.66 0H4a1 1 0 1 1 0-2Zm1.17 6H4a1 1 0 1 0 0 2h1.17a3.001 3.001 0 0 0 5.66 0H20a1 1 0 1 0 0-2h-9.17a3.001 3.001 0 0 0-5.66 0Z" })
                    ])),
                    _createElementVNode("span", { class: "max-lg:hidden ms-2" }, "Nuevo Perfil")
                ])
            ]),
            _createElementVNode("div", { class: "grid lg:grid-cols-[1fr,2fr] gap-4 grid-cols-1" }, [
                _createVNode(_component_listPerfilesSide, { refreshData: $setup.refreshData }, null, 8 /* PROPS */, ["refreshData"]),
                _createVNode(_component_perfilSide, { onAccion: $setup.accion })
            ])
        ])
    ]));
}
dashPerfiles_component.render = render_dashPerfiles_component;
export default dashPerfiles_component;
//# sourceMappingURL=dashPerfiles.vue.js.map