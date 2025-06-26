import { defineComponent as _defineComponent } from '/node_modules/vue/dist/vue.esm-browser.js';
import Swal from '/node_modules/sweetalert2/dist/sweetalert2.esm.all.js';
import { inject, ref, watch } from '/node_modules/vue/dist/vue.esm-browser.js';
import check from '@/dashboard/js/components/check.vue';
import inputEditable from '@/dashboard/js/components/inputEditable.vue';
import { removeScape, versaFetch } from '@/dashboard/js/functions';
const perfilSide_component = /*@__PURE__*/ _defineComponent({
    __file: 'C:/Users/jjara/Desktop/proyectos/versaWYS-PHP/src/dashboard/js/perfil/perfilSide.vue',
    __name: 'perfilSide',
    name: 'perfilSide',
    components: {
        check,
        inputEditable
    },
    emits: ['accion'],
    setup(__props, { expose: __expose, emit: __emit }) {
        __expose();
        const versaComponentKey = ref(0);
        const emit = __emit;
        const perfil = inject('perfil');
        const csrf_token = inject('csrf_token');
        const perfilData = ref({});
        const urls = ref([]);
        const removeScapeLocal = (str) => removeScape(str);
        const getPerfil = async () => {
            const response = await versaFetch({
                url: `/admin/perfiles/getPerfil/${perfil.value.id}`,
                method: 'GET',
            });
            if (response.success) {
                perfilData.value = response.data;
                urls.value = response.urls;
            }
        };
        watch(() => perfil.value, () => {
            getPerfil();
        });
        const savePerfilPersmisos = async () => {
            if (perfil.value?.id === undefined) {
                return;
            }
            const data = {
                id: perfil.value.id,
                nombre: perfil.value.nombre,
                pagina_inicio: perfil.value.pagina_inicio,
                csrf_token: csrf_token,
                data: JSON.stringify(perfilData.value),
            };
            const response = await versaFetch({
                url: '/admin/perfiles/savePerfilPermisos',
                method: 'POST',
                data,
            });
            if (response.success) {
                Swal.fire({
                    icon: 'success',
                    title: 'Perfil actualizado',
                    showConfirmButton: false,
                    timer: 1500,
                });
            }
            else {
                Swal.fire({
                    icon: 'error',
                    title: 'Error al actualizar el perfil',
                    showConfirmButton: false,
                    timer: 1500,
                });
            }
        };
        const accion = (accion) => {
            const actions = {
                updateData: () => savePerfilPersmisos(),
            };
            const selectedAction = actions[accion.accion] || actions['default'];
            if ('function' === typeof selectedAction) {
                selectedAction();
            }
        };
        const __returned__ = { versaComponentKey, emit, perfil, csrf_token, perfilData, urls, removeScapeLocal, getPerfil, savePerfilPersmisos, accion, check, inputEditable };
        Object.defineProperty(__returned__, '__isScriptSetup', { enumerable: false, value: true });
        return __returned__;
    }
});
import { resolveComponent as _resolveComponent, createVNode as _createVNode, createElementVNode as _createElementVNode, renderList as _renderList, Fragment as _Fragment, openBlock as _openBlock, createElementBlock as _createElementBlock, toDisplayString as _toDisplayString, vModelSelect as _vModelSelect, withDirectives as _withDirectives, createBlock as _createBlock } from "/node_modules/vue/dist/vue.esm-browser.js";
function render_perfilSide_component(_ctx, _cache, $props, $setup, $data, $options) {
    const _component_inputEditable = _resolveComponent("inputEditable");
    const _component_check = _resolveComponent("check");
    return (_openBlock(), _createElementBlock("div", {
        class: "w-full mx-auto rounded-lg shadow-md overflow-hidden",
        key: $setup.versaComponentKey
    }, [
        _createElementVNode("div", { class: "px-4 py-2" }, [
            _createVNode(_component_inputEditable, {
                id: "nombre",
                modelValue: $setup.perfil.nombre,
                "onUpdate:modelValue": $event => (($setup.perfil.nombre) = $event),
                field: "nombre",
                type: "text",
                placeholder: "Nombre del perfil",
                onAccion: $setup.accion
            }, null, 8 /* PROPS */, ["modelValue", "onUpdate:modelValue"]),
            _createElementVNode("hr", { class: "my-4" })
        ]),
        _createElementVNode("div", { class: "px-6 text-gray-700 dark:text-white" }, [
            _createElementVNode("div", { class: "flex justify-between" }, [
                _createElementVNode("h2", { class: "text-lg font-semibold" }, "Pagina de Inicio:"),
                _withDirectives(_createElementVNode("select", {
                    "onUpdate:modelValue": $event => (($setup.perfil.pagina_inicio) = $event),
                    class: "w-[80%] p-2 border border-gray-300 rounded-lg bg-white dark:bg-gray-800"
                }, [
                    (_openBlock(true), _createElementBlock(_Fragment, null, _renderList($setup.urls, (url, key) => {
                        return (_openBlock(), _createElementBlock("option", {
                            key: key,
                            value: url.url
                        }, [
                            _createElementVNode("ruby", null, [
                                _createElementVNode("rt", null, _toDisplayString(url.nombre) + " ", 1 /* TEXT */),
                                _createElementVNode("rp", null, "("),
                                _createElementVNode("rt", null, _toDisplayString(url.url), 1 /* TEXT */),
                                _createElementVNode("rp", null, ")")
                            ])
                        ], 8 /* PROPS */, ["value"]));
                    }), 128 /* KEYED_FRAGMENT */))
                ], 8 /* PROPS */, ["onUpdate:modelValue"]), [
                    [_vModelSelect, $setup.perfil.pagina_inicio]
                ])
            ]),
            _createElementVNode("h2", { class: "text-lg font-semibold" }, "Permisos"),
            _createElementVNode("hr", { class: "my-4" }),
            _createElementVNode("div", { class: "grid grid-cols-1 w-[80%]" }, [
                (_openBlock(true), _createElementBlock(_Fragment, null, _renderList($setup.perfilData, (seccion, key) => {
                    return (_openBlock(), _createElementBlock("div", { key: key }, [
                        _createElementVNode("span", { class: "" }, _toDisplayString(key), 1 /* TEXT */),
                        _createElementVNode("ul", { class: "ml-4" }, [
                            (_openBlock(true), _createElementBlock(_Fragment, null, _renderList(seccion, (menu, index) => {
                                return (_openBlock(), _createElementBlock("li", {
                                    key: index,
                                    class: "py-2 flex gap-2"
                                }, [
                                    (_openBlock(), _createElementBlock("svg", {
                                        class: "flex-shrink-0 w-6 h-6 text-gray-500 transition duration-75 group-hover:text-gray-900 dark:text-gray-400 dark:group-hover:text-white",
                                        "aria-hidden": "true",
                                        xmlns: "http://www.w3.org/2000/svg",
                                        width: "24",
                                        height: "24",
                                        fill: menu.fill_menu === '1' ? 'currentColor' : 'none',
                                        innerHTML: $setup.removeScapeLocal(menu.icon)
                                    }, null, 8 /* PROPS */, ["fill", "innerHTML"])),
                                    (menu.submenu.length > 0)
                                        ? (_openBlock(), _createElementBlock("div", {
                                            key: 0,
                                            class: "w-full"
                                        }, [
                                            _createElementVNode("span", null, _toDisplayString(index), 1 /* TEXT */),
                                            _createElementVNode("ul", { class: "ml-2" }, [
                                                (_openBlock(true), _createElementBlock(_Fragment, null, _renderList(menu.submenu, (submenu, i) => {
                                                    return (_openBlock(), _createElementBlock("li", {
                                                        key: i,
                                                        class: "flex gap-2"
                                                    }, [
                                                        _createElementVNode("i", { class: "bi bi-circle" }),
                                                        _createElementVNode("div", { class: "flex justify-between gap-2 w-full" }, [
                                                            _createElementVNode("label", {
                                                                class: "block text-sm font-medium text-gray-900 dark:text-white",
                                                                for: submenu.id_menu + '_' + submenu.id_submenu
                                                            }, _toDisplayString(submenu.submenu), 9 /* TEXT, PROPS */, ["for"]),
                                                            (_openBlock(), _createBlock(_component_check, {
                                                                id: submenu.id_menu + '_' + submenu.id_submenu,
                                                                key: submenu.id_menu + '_' + submenu.id_submenu,
                                                                modelValue: submenu.checked,
                                                                "onUpdate:modelValue": $event => ((submenu.checked) = $event)
                                                            }, null, 8 /* PROPS */, ["id", "modelValue", "onUpdate:modelValue"]))
                                                        ])
                                                    ]));
                                                }), 128 /* KEYED_FRAGMENT */))
                                            ])
                                        ]))
                                        : (_openBlock(), _createElementBlock("div", {
                                            key: 1,
                                            class: "w-full"
                                        }, [
                                            _createElementVNode("div", { class: "flex justify-between gap-2" }, [
                                                _createElementVNode("label", {
                                                    class: "block text-sm font-medium text-gray-900 dark:text-white",
                                                    for: menu.id_menu
                                                }, _toDisplayString(index), 9 /* TEXT, PROPS */, ["for"]),
                                                (_openBlock(), _createBlock(_component_check, {
                                                    id: menu.id_menu,
                                                    key: menu.id_menu,
                                                    modelValue: menu.checked,
                                                    "onUpdate:modelValue": $event => ((menu.checked) = $event)
                                                }, null, 8 /* PROPS */, ["id", "modelValue", "onUpdate:modelValue"]))
                                            ])
                                        ]))
                                ]));
                            }), 128 /* KEYED_FRAGMENT */))
                        ])
                    ]));
                }), 128 /* KEYED_FRAGMENT */))
            ])
        ])
    ]));
}
perfilSide_component.render = render_perfilSide_component;
export default perfilSide_component;
//# sourceMappingURL=perfilSide.vue.js.map