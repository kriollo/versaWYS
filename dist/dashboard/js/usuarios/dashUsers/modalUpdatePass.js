import { defineComponent as _defineComponent } from '/node_modules/vue/dist/vue.esm-browser.js';
import { ref } from "/node_modules/vue/dist/vue.esm-browser.js";

import { computed, inject } from '/node_modules/vue/dist/vue.esm-browser.js';
import modal from '@/dashboard/js/components/modal.vue';
import { $dom } from '@/dashboard/js/composables/dom';
import { versaAlert, versaFetch, VersaToast } from '@/dashboard/js/functions';
import { API_RESPONSE_CODES } from '../../constants.js';
const modalUpdatePass_component = /*@__PURE__*/ _defineComponent({
    __file: 'C:/Users/jjara/Desktop/proyectos/versaWYS-PHP/src/dashboard/js/usuarios/dashUsers/modalUpdatePass.vue',
    __name: 'modalUpdatePass',
    components: {
        modal
    },
    props: {
        showModal: { type: Boolean, required: true, default: false },
        tokenId: { type: String, required: true, default: '' },
        origen: { type: String, required: true, default: '' }
    },
    emits: ['accion'],
    setup(__props, { expose: __expose, emit: __emit }) {
        __expose();
        const versaComponentKey = ref(0);
        const props = __props;
        const emit = __emit;
        const showModalLocal = computed(() => props.showModal);
        const tokenId = computed(() => props.tokenId);
        const origen = computed(() => props.origen);
        const csrf_token = inject('csrf_token');
        const tooglePassword = (
        /** @type {String} */ idInput, 
        /** @type {String} */ idImgShow, 
        /** @type {String} */ idImgHidden) => {
            const togglePassword = $dom(`#${idInput}`);
            const imgShowPass = $dom(`#${idImgShow}`);
            const imgHiddenPass = $dom(`#${idImgHidden}`);
            if (!togglePassword || !imgShowPass || !imgHiddenPass) {
                return;
            }
            if ('password' === togglePassword.type) {
                togglePassword.type = 'text';
                imgShowPass.classList.remove('hidden');
                imgHiddenPass.classList.add('hidden');
            }
            else {
                togglePassword.type = 'password';
                imgShowPass.classList.add('hidden');
                imgHiddenPass.classList.remove('hidden');
            }
        };
        const accion = (/** @type {Object} */ accion) => {
            emit('accion', accion);
        };
        const sendResetPass = async () => {
            const formChangePass = $dom('#formChangePass');
            if (!(formChangePass instanceof HTMLFormElement)) {
                return false;
            }
            const formData = new FormData(formChangePass);
            const newPass = document.querySelector('#new_password');
            const confirmNewPass = document.querySelector('#comfirm_new_password');
            if (!(newPass instanceof HTMLInputElement)) {
                return;
            }
            if (!(confirmNewPass instanceof HTMLInputElement)) {
                return;
            }
            if (newPass.value !== confirmNewPass.value) {
                versaAlert({
                    message: 'Las contraseñas no coinciden',
                    title: 'Error',
                    type: 'error',
                });
                return false;
            }
            const objectData = Object.fromEntries(formData.entries());
            const params = {
                url: '/admin/users/changePassword',
                method: 'PATCH',
                headers: {
                    'content-type': 'application/json',
                },
                data: JSON.stringify(objectData),
            };
            const response = await versaFetch(params);
            if (API_RESPONSE_CODES.SUCCESS === response.success) {
                await VersaToast.fire({
                    icon: 'success',
                    title: response.message,
                });
                accion({ accion: 'closeModal' });
            }
            else {
                let errors = '';
                if (response?.errors) {
                    errors = `
                    <ul class="w-full text-left space-y-1 text-gray-500 list-disc list-inside dark:text-gray-400">
                        ${Object.keys(response.errors)
                        .map(key => `
                                    <li>${response.errors[key]}</li>
                                `)
                        .join('')}
                    </ul>
                `;
                }
                versaAlert({
                    message: response.message,
                    html: `${response.message}<br>${errors}`,
                    title: 'Error',
                    type: 'error',
                    customClass: {
                        popup: 'swal-wide',
                        // htmlContainer: 'swal-target',
                    },
                });
            }
        };
        const __returned__ = { versaComponentKey, props, emit, showModalLocal, tokenId, origen, csrf_token, tooglePassword, accion, sendResetPass, modal };
        Object.defineProperty(__returned__, '__isScriptSetup', { enumerable: false, value: true });
        return __returned__;
    }
});
import { createElementVNode as _createElementVNode, openBlock as _openBlock, createElementBlock as _createElementBlock, resolveComponent as _resolveComponent, withCtx as _withCtx, createBlock as _createBlock } from "/node_modules/vue/dist/vue.esm-browser.js";
function render_modalUpdatePass_component(_ctx, _cache, $props, $setup, $data, $options) {
    const _component_modal = _resolveComponent("modal");
    return (_openBlock(), _createBlock(_component_modal, {
        "id-modal": $setup.origen + 'resetPass',
        "show-modal": $setup.showModalLocal,
        onAccion: $setup.accion,
        key: $setup.versaComponentKey
    }, {
        modalTitle: _withCtx(() => [
            _createElementVNode("div", { class: "flex justify-between" }, [
                _createElementVNode("h3", { class: "text-lg font-medium text-gray-900 dark:text-white" }, "Actualizar Contraseña"),
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
            _createElementVNode("form", {
                id: "formChangePass",
                class: "space-y-4"
            }, [
                _createElementVNode("input", {
                    type: "hidden",
                    value: $setup.csrf_token,
                    name: "csrf_token"
                }, null, 8 /* PROPS */, ["value"]),
                _createElementVNode("input", {
                    type: "hidden",
                    value: $setup.tokenId,
                    name: "tokenid"
                }, null, 8 /* PROPS */, ["value"]),
                _createElementVNode("div", { class: "relative" }, [
                    _createElementVNode("label", {
                        class: "block mb-2 text-sm font-medium text-gray-900 dark:text-white",
                        for: "new_password"
                    }, " Contraseña "),
                    _createElementVNode("span", {
                        id: "togglePasswordNew",
                        class: "absolute end-0 flex items-center cursor-pointer pr-2 top-[60%]",
                        onClick: $event => ($setup.tooglePassword('new_password', 'imgShowPassNew', 'imgHiddenPassNew'))
                    }, [
                        (_openBlock(), _createElementBlock("svg", {
                            id: "imgShowPassNew",
                            class: "hidden w-6 h-6 text-gray-800 dark:text-slate-400",
                            fill: "none",
                            xmlns: "http://www.w3.org/2000/svg"
                        }, [
                            _createElementVNode("path", {
                                d: "M1.933 10.909A4.357 4.357 0 0 1 1 9c0-1 4-6 9-6m7.6 3.8A5.068 5.068 0 0 1 19 9c0 1-3 6-9 6-.314 0-.62-.014-.918-.04M2 17 18 1m-5 8a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z",
                                stroke: "currentColor",
                                "stroke-linecap": "round",
                                "stroke-linejoin": "round",
                                "stroke-width": "2"
                            })
                        ])),
                        (_openBlock(), _createElementBlock("svg", {
                            id: "imgHiddenPassNew",
                            class: "show w-6 h-6 text-gray-800 dark:text-slate-400",
                            fill: "none",
                            xmlns: "http://www.w3.org/2000/svg"
                        }, [
                            _createElementVNode("g", {
                                stroke: "currentColor",
                                "stroke-linecap": "round",
                                "stroke-linejoin": "round",
                                "stroke-width": "2"
                            }, [
                                _createElementVNode("path", { d: "M10 10a3 3 0 1 0 0-6 3 3 0 0 0 0 6Z" }),
                                _createElementVNode("path", { d: "M10 13c4.97 0 9-2.686 9-6s-4.03-6-9-6-9 2.686-9 6 4.03 6 9 6Z" })
                            ])
                        ]))
                    ], 8 /* PROPS */, ["onClick"]),
                    _createElementVNode("input", {
                        id: "new_password",
                        type: "password",
                        class: "bg-gray-50 border border-gray-300 text-gray-900 sm:text-sm rounded-lg focus:ring-primary-500 focus:border-primary-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-primary-500 dark:focus:border-primary-500",
                        name: "new_password",
                        placeholder: "••••••••",
                        autocomplete: "off",
                        required: ""
                    })
                ]),
                _createElementVNode("div", { class: "relative" }, [
                    _createElementVNode("label", {
                        class: "block mb-2 text-sm font-medium text-gray-900 dark:text-white",
                        for: "comfirm_new_password"
                    }, " Contraseña "),
                    _createElementVNode("span", {
                        id: "togglePasswordConfirmNew",
                        class: "absolute end-0 flex items-center cursor-pointer pr-2 top-[60%]",
                        onClick: $event => ($setup.tooglePassword('comfirm_new_password', 'imgShowPassConfirmNew', 'imgHiddenPassConfirmNew'))
                    }, [
                        (_openBlock(), _createElementBlock("svg", {
                            id: "imgShowPassConfirmNew",
                            class: "hidden w-6 h-6 text-gray-800 dark:text-slate-400",
                            fill: "none",
                            xmlns: "http://www.w3.org/2000/svg"
                        }, [
                            _createElementVNode("path", {
                                d: "M1.933 10.909A4.357 4.357 0 0 1 1 9c0-1 4-6 9-6m7.6 3.8A5.068 5.068 0 0 1 19 9c0 1-3 6-9 6-.314 0-.62-.014-.918-.04M2 17 18 1m-5 8a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z",
                                stroke: "currentColor",
                                "stroke-linecap": "round",
                                "stroke-linejoin": "round",
                                "stroke-width": "2"
                            })
                        ])),
                        (_openBlock(), _createElementBlock("svg", {
                            id: "imgHiddenPassConfirmNew",
                            class: "show w-6 h-6 text-gray-800 dark:text-slate-400",
                            fill: "none",
                            xmlns: "http://www.w3.org/2000/svg"
                        }, [
                            _createElementVNode("g", {
                                stroke: "currentColor",
                                "stroke-linecap": "round",
                                "stroke-linejoin": "round",
                                "stroke-width": "2"
                            }, [
                                _createElementVNode("path", { d: "M10 10a3 3 0 1 0 0-6 3 3 0 0 0 0 6Z" }),
                                _createElementVNode("path", { d: "M10 13c4.97 0 9-2.686 9-6s-4.03-6-9-6-9 2.686-9 6 4.03 6 9 6Z" })
                            ])
                        ]))
                    ], 8 /* PROPS */, ["onClick"]),
                    _createElementVNode("input", {
                        id: "comfirm_new_password",
                        type: "password",
                        class: "bg-gray-50 border border-gray-300 text-gray-900 sm:text-sm rounded-lg focus:ring-primary-500 focus:border-primary-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-primary-500 dark:focus:border-primary-500",
                        name: "comfirm_new_password",
                        placeholder: "••••••••",
                        autocomplete: "off",
                        required: ""
                    })
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
                onClick: $event => ($setup.sendResetPass())
            }, " Actualizar Contraseña ", 8 /* PROPS */, ["onClick"])
        ]),
        _: 1 /* STABLE */
    }, 8 /* PROPS */, ["id-modal", "show-modal"]));
}
modalUpdatePass_component.render = render_modalUpdatePass_component;
export default modalUpdatePass_component;
//# sourceMappingURL=modalUpdatePass.vue.js.map