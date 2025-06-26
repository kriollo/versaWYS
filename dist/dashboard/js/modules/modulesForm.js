import { defineComponent as _defineComponent } from '/node_modules/vue/dist/vue.esm-browser.js';

import Swal from '/node_modules/sweetalert2/dist/sweetalert2.esm.all.js';
import { inject, onWatcherCleanup, ref, watchEffect } from '/node_modules/vue/dist/vue.esm-browser.js';
import check from '@/dashboard/js/components/check.vue';
import modal from '@/dashboard/js/components/modal.vue';
import { removeScape, versaAlert, versaFetch, } from '@/dashboard/js/functions';
import { ShowModalFormInjection, } from '@/dashboard/js/modules/InjectKeys';
import { API_RESPONSE_CODES } from '@/dashboard/js/constants';
const modulesForm_component = /*@__PURE__*/ _defineComponent({
    __file: 'C:/Users/jjara/Desktop/proyectos/versaWYS-PHP/src/dashboard/js/modules/modulesForm.vue',
    __name: 'modulesForm',
    name: 'modulesForm',
    components: {
        check,
        modal
    },
    setup(__props, { expose: __expose }) {
        __expose();
        const versaComponentKey = ref(0);
        const showModalForm = ShowModalFormInjection.inject();
        const csrf_token = inject('csrf_token');
        const showModal = ref(false);
        const newModule = {
            id: 0,
            action: 'create',
            seccion: '',
            nombre: '',
            descripcion: '',
            icono: '',
            fill: false,
            url: '',
            estado: true,
            csrf_token,
        };
        const localFormData = ref({ ...newModule });
        watchEffect(() => {
            if (showModalForm.showModalForm) {
                showModal.value = showModalForm.showModalForm;
                localFormData.value = {
                    ...(showModalForm.itemSelected
                        ? showModalForm.itemSelected
                        : newModule),
                };
                if (showModalForm.itemSelected) {
                    localFormData.value.action = 'edit';
                    localFormData.value.icono = removeScape(showModalForm.itemSelected.icono);
                    localFormData.value.fill = showModalForm.itemSelected.fill;
                    localFormData.value.estado = showModalForm.itemSelected.estado;
                    localFormData.value.csrf_token = csrf_token;
                }
            }
            onWatcherCleanup(() => {
                showModal.value = false;
                localFormData.value = { ...newModule };
            });
        });
        const saveModule = async () => {
            const params = {
                url: '/admin/modules/saveModule',
                method: 'POST',
                data: localFormData.value,
            };
            const response = await versaFetch(params);
            if (API_RESPONSE_CODES.ERROR === response.success) {
                const errores = `
                <ul
                    class="max-w-md space-y-1 text-gray-500 list-disc list-inside dark:text-gray-400">
                    ${Object.keys(response.errors)
                    .map(key => `
                                <li>${response.errors[key]}</li>
                            `)
                    .join('')}
                </ul>
            `;
                versaAlert({
                    title: 'Error',
                    type: 'error',
                    html: errores,
                });
            }
            else {
                versaAlert({
                    title: 'Éxito',
                    message: response.message,
                    type: 'success',
                    callback: async () => {
                        const result = await Swal.fire({
                            title: 'Recargar la página',
                            text: '¿Desea recargar la página para ver los cambios?',
                            icon: 'question',
                            showCancelButton: true,
                            confirmButtonText: 'Recargar',
                            cancelButtonText: 'Cancelar',
                        });
                        if (result.isConfirmed) {
                            window.location.reload();
                        }
                        else {
                            accion({ accion: 'closeModal' });
                        }
                    },
                });
            }
        };
        const accion = (accion) => {
            const actions = {
                closeModal: () => {
                    showModalForm.showModalForm = false;
                    showModalForm.itemSelected = null;
                },
                default: () => console.log('Accion no encontrada'),
            };
            const selectedAction = actions[accion.accion] || actions['default'];
            if ('function' === typeof selectedAction) {
                selectedAction();
            }
        };
        const __returned__ = { versaComponentKey, showModalForm, csrf_token, showModal, newModule, localFormData, saveModule, accion, check, modal };
        Object.defineProperty(__returned__, '__isScriptSetup', { enumerable: false, value: true });
        return __returned__;
    }
});
import { createElementVNode as _createElementVNode, openBlock as _openBlock, createElementBlock as _createElementBlock, vModelText as _vModelText, withDirectives as _withDirectives, resolveComponent as _resolveComponent, createVNode as _createVNode, vModelCheckbox as _vModelCheckbox, withCtx as _withCtx, createBlock as _createBlock } from "/node_modules/vue/dist/vue.esm-browser.js";
function render_modulesForm_component(_ctx, _cache, $props, $setup, $data, $options) {
    const _component_check = _resolveComponent("check");
    const _component_modal = _resolveComponent("modal");
    return (_openBlock(), _createBlock(_component_modal, {
        showModal: $setup.showModal,
        onAccion: $setup.accion,
        idModal: "modalFormModule",
        size: "max-w-xl",
        key: $setup.versaComponentKey
    }, {
        modalTitle: _withCtx(() => [
            _createElementVNode("div", { class: "flex justify-between" }, [
                _createElementVNode("h3", { class: "text-lg font-medium text-gray-900 dark:text-white" }, " Configurar Módulo "),
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
            _createElementVNode("form", { class: "space-y-6 flex flex-col" }, [
                _createElementVNode("div", { class: "grid grid-cols-2 gap-4" }, [
                    _createElementVNode("div", { class: "space-y-2 col-span-2 sm:col-span-1" }, [
                        _createElementVNode("label", {
                            class: "block mb-2 text-sm font-medium text-gray-900 dark:text-white",
                            for: "seccion"
                        }, " Sección "),
                        _withDirectives(_createElementVNode("input", {
                            id: "seccion",
                            type: "text",
                            class: "bg-gray-50 border border-gray-300 text-gray-900 sm:text-sm rounded-lg focus:ring-primary-500 focus:border-primary-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-primary-500 dark:focus:border-primary-500",
                            placeholder: "Ingrese la sección",
                            required: "",
                            "onUpdate:modelValue": $event => (($setup.localFormData.seccion) = $event)
                        }, null, 8 /* PROPS */, ["onUpdate:modelValue"]), [
                            [_vModelText, $setup.localFormData.seccion]
                        ])
                    ]),
                    _createElementVNode("div", { class: "space-y-2 col-span-2 sm:col-span-1" }, [
                        _createElementVNode("label", {
                            class: "block mb-2 text-sm font-medium text-gray-900 dark:text-white",
                            for: "nombre"
                        }, " Nombre "),
                        _withDirectives(_createElementVNode("input", {
                            id: "nombre",
                            type: "text",
                            class: "bg-gray-50 border border-gray-300 text-gray-900 sm:text-sm rounded-lg focus:ring-primary-500 focus:border-primary-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-primary-500 dark:focus:border-primary-500",
                            placeholder: "Nombre que aparecerá en el menú",
                            required: "",
                            "onUpdate:modelValue": $event => (($setup.localFormData.nombre) = $event)
                        }, null, 8 /* PROPS */, ["onUpdate:modelValue"]), [
                            [_vModelText, $setup.localFormData.nombre]
                        ])
                    ])
                ]),
                _createElementVNode("div", { class: "space-y-2" }, [
                    _createElementVNode("label", {
                        class: "block mb-2 text-sm font-medium text-gray-900 dark:text-white",
                        for: "descripcion"
                    }, " Descripción "),
                    _withDirectives(_createElementVNode("textarea", {
                        id: "descripcion",
                        class: "bg-gray-50 border border-gray-300 text-gray-900 sm:text-sm rounded-lg focus:ring-primary-500 focus:border-primary-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-primary-500 dark:focus:border-primary-500",
                        placeholder: "Descripción del módulo",
                        required: "",
                        rows: "3",
                        "onUpdate:modelValue": $event => (($setup.localFormData.descripcion) = $event)
                    }, null, 8 /* PROPS */, ["onUpdate:modelValue"]), [
                        [_vModelText, $setup.localFormData.descripcion]
                    ])
                ]),
                _createElementVNode("div", { class: "space-y-2" }, [
                    _createElementVNode("label", {
                        class: "block mb-2 text-sm font-medium text-gray-900 dark:text-white",
                        for: "iconoSvg"
                    }, " Icono SVG "),
                    _createElementVNode("div", { class: "flex space-x-4" }, [
                        _withDirectives(_createElementVNode("textarea", {
                            id: "iconoSvg",
                            class: "bg-gray-50 border border-gray-300 text-gray-900 sm:text-sm rounded-lg focus:ring-primary-500 focus:border-primary-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-primary-500 dark:focus:border-primary-500",
                            placeholder: "Pegue la sección path '<path d=.... />' del SVG aquí",
                            required: "",
                            rows: "5",
                            "onUpdate:modelValue": $event => (($setup.localFormData.icono) = $event)
                        }, null, 8 /* PROPS */, ["onUpdate:modelValue"]), [
                            [_vModelText, $setup.localFormData.icono]
                        ]),
                        _createElementVNode("div", null, [
                            _createElementVNode("div", { class: "flex gap-2 mb-2" }, [
                                _createVNode(_component_check, {
                                    id: "fill",
                                    label: "Rellenar",
                                    modelValue: $setup.localFormData.fill,
                                    "onUpdate:modelValue": $event => (($setup.localFormData.fill) = $event)
                                }, null, 8 /* PROPS */, ["modelValue", "onUpdate:modelValue"])
                            ]),
                            _createElementVNode("div", { class: "flex-shrink-0 w-20 h-20 border border-gray-300 rounded-md flex items-center justify-center overflow-hidden" }, [
                                _createElementVNode("div", { class: "w-full h-full flex items-center justify-center" }, [
                                    (_openBlock(), _createElementBlock("svg", {
                                        class: "w-full h-full text-gray-800 dark:text-white text-center",
                                        fill: $setup.localFormData.fill === true
                                            ? 'currentColor'
                                            : 'none',
                                        height: "24",
                                        innerHTML: $setup.localFormData.icono,
                                        viewBox: "0 0 24 24",
                                        width: "24",
                                        xmlns: "http://www.w3.org/2000/svg"
                                    }, null, 8 /* PROPS */, ["fill", "innerHTML"]))
                                ])
                            ])
                        ])
                    ])
                ]),
                _createElementVNode("div", { class: "grid grid-cols-2 gap-4" }, [
                    _createElementVNode("div", { class: "space-y-2 col-span-2 sm:col-span-1" }, [
                        _createElementVNode("label", {
                            class: "block mb-2 text-sm font-medium text-gray-900 dark:text-white",
                            for: "url"
                        }, " URL "),
                        _withDirectives(_createElementVNode("input", {
                            id: "url",
                            type: "url",
                            class: "bg-gray-50 border border-gray-300 text-gray-900 sm:text-sm rounded-lg focus:ring-primary-500 focus:border-primary-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-primary-500 dark:focus:border-primary-500",
                            placeholder: "/admin/modulo",
                            required: "",
                            "onUpdate:modelValue": $event => (($setup.localFormData.url) = $event)
                        }, null, 8 /* PROPS */, ["onUpdate:modelValue"]), [
                            [_vModelText, $setup.localFormData.url]
                        ])
                    ]),
                    _createElementVNode("div", { class: "space-y-2 col-span-2 sm:col-span-1 flex items-center justify-center" }, [
                        _createElementVNode("div", { class: "flex gap-2 items-center justify-center" }, [
                            _withDirectives(_createElementVNode("input", {
                                id: "estado",
                                type: "checkbox",
                                class: "form-checkbox h-5 w-5 text-primary-600 dark:text-primary-400 focus:ring-primary-500 dark:focus:ring-primary-500",
                                "onUpdate:modelValue": $event => (($setup.localFormData.estado) = $event)
                            }, null, 8 /* PROPS */, ["onUpdate:modelValue"]), [
                                [_vModelCheckbox, $setup.localFormData.estado]
                            ]),
                            _createElementVNode("label", {
                                class: "block text-sm font-medium text-gray-900 dark:text-white",
                                for: "estado"
                            }, " Estado ")
                        ])
                    ])
                ])
            ])
        ]),
        modalFooter: _withCtx(() => [
            _createElementVNode("button", {
                type: "button",
                class: "text-white bg-blue-700 hover:bg-blue-800 focus:ring-4 focus:outline-none focus:ring-blue-300 font-medium rounded-lg text-sm px-5 py-2.5 text-center inline-flex items-center dark:bg-blue-600 dark:hover:bg-blue-700 dark:focus:ring-blue-800",
                onClick: $event => ($setup.accion({ accion: 'closeModal' }))
            }, " Cancelar ", 8 /* PROPS */, ["onClick"]),
            _createElementVNode("button", {
                type: "button",
                class: "text-white bg-green-700 hover:bg-green-800 focus:ring-4 focus:outline-none focus:ring-blue-300 font-medium rounded-lg text-sm px-5 py-2.5 text-center inline-flex items-center dark:bg-green-600 dark:hover:bg-green-700 dark:focus:ring-green-800",
                onClick: $setup.saveModule
            }, " Guardar ")
        ]),
        _: 1 /* STABLE */
    }, 8 /* PROPS */, ["showModal"]));
}
modulesForm_component.render = render_modulesForm_component;
export default modulesForm_component;
//# sourceMappingURL=modulesForm.vue.js.map