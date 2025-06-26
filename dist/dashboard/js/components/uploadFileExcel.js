import { defineComponent as _defineComponent } from '/node_modules/vue/dist/vue.esm-browser.js';

import Swal from '/node_modules/sweetalert2/dist/sweetalert2.esm.all.js';
import { ref, toRefs } from '/node_modules/vue/dist/vue.esm-browser.js';
import dropZone from '@/dashboard/js/components/dropZone.vue';
import loader from '@/dashboard/js/components/loader.vue';
import modal from '@/dashboard/js/components/modal.vue';
import { getSheetNames, readXlsx } from '@/dashboard/js/composables/useXlsx';
import { GLOBAL_CONSTANTS } from '@/dashboard/js/constants';
const uploadFileExcel_component = /*@__PURE__*/ _defineComponent({
    __file: 'C:/Users/jjara/Desktop/proyectos/versaWYS-PHP/src/dashboard/js/components/uploadFileExcel.vue',
    __name: 'uploadFileExcel',
    name: 'uploadFileExcel',
    components: {
        dropZone,
        loader,
        modal
    },
    props: {
        from: { type: String, required: false, default: '' },
        files: { type: Array, required: true },
        showModal: { type: Boolean, required: true, default: false },
        returnData: { type: Boolean, required: false, default: true }
    },
    emits: ['accion'],
    setup(__props, { expose: __expose, emit: __emit }) {
        __expose();
        const versaComponentKey = ref(0);
        const emit = __emit;
        const props = __props;
        const fileTypes = ['xlsx'];
        const showLoader = ref(false);
        const { files, showModal, from, returnData } = toRefs(props);
        const accion = (response) => {
            const actions = {
                addFiles: () => showDialogSelectSheet(response.files),
                closeModal: () => emit('accion', {
                    accion: 'closeModalUploadExcel',
                    from: from.value,
                }),
            };
            const fn = actions[response.accion];
            if ('function' === typeof fn) {
                fn();
            }
        };
        const showDialogSelectSheet = async (file) => {
            showLoader.value = true;
            const sheets = await getSheetNames(file.file);
            const result = await Swal.fire({
                title: '¿Está seguro de subir el archivo?',
                text: `Una vez subido el archivo: ${file.archivo}, no podrá ser revertido `,
                icon: 'warning',
                html: `
                <div class="flex flex-wrap content-start ">
                    <label class="block text-sm font-medium text-gray-900 dark:text-white">
                        Una vez subido el archivo: ${file.archivo}, no podrá ser revertido
                    </label>
                    <div class="flex gap-2">
                        <input
                            id="checkPeraLinea"
                            type="checkbox"
                            checked
                            class="h-5 w-5 text-green-600 dark:text-green-400 focus:ring-green-500 dark:focus:ring-green-500" />
                        <label class="block text-sm font-medium text-gray-900 dark:text-white" for="checkPeraLinea">
                            Usar Primera línea como encabezado
                        </label>
                    </div>
                </div>
            `,
                input: 'select',
                inputOptions: {
                    ...sheets,
                },
                inputPlaceholder: 'Seleccione la hoja',
                showCancelButton: true,
                confirmButtonText: 'Subir',
                cancelButtonText: 'Cancelar',
                inputValidator: (value) => {
                    if (!value) {
                        return 'Debe seleccionar una hoja';
                    }
                },
            });
            showLoader.value = false;
            if (result.isConfirmed) {
                const sheet = result.value;
                const data = returnData.value ? await readXlsx(file.file, sheet) : [];
                let primeraLinea = false;
                const check = document.querySelector('#checkPeraLinea');
                if (check instanceof HTMLInputElement) {
                    primeraLinea = check.checked;
                }
                if (returnData.value && data.length === GLOBAL_CONSTANTS.ZERO) {
                    Swal.fire({
                        title: 'Error',
                        text: 'No se encontraron datos en la hoja seleccionada',
                        icon: 'error',
                    });
                    accion({ accion: 'closeModal' });
                    return;
                }
                emit('accion', {
                    accion: 'responseUploadExcel',
                    data,
                    primeraLinea,
                    hoja: sheet,
                    files: file,
                    from: from.value,
                });
                accion({ accion: 'closeModal' });
            }
        };
        const __returned__ = { versaComponentKey, emit, props, fileTypes, showLoader, files, showModal, from, returnData, accion, showDialogSelectSheet, dropZone, loader, modal };
        Object.defineProperty(__returned__, '__isScriptSetup', { enumerable: false, value: true });
        return __returned__;
    }
});
import { resolveComponent as _resolveComponent, openBlock as _openBlock, createBlock as _createBlock, createCommentVNode as _createCommentVNode, createTextVNode as _createTextVNode, createElementVNode as _createElementVNode, createElementBlock as _createElementBlock, createVNode as _createVNode, withCtx as _withCtx } from "/node_modules/vue/dist/vue.esm-browser.js";
function render_uploadFileExcel_component(_ctx, _cache, $props, $setup, $data, $options) {
    const _component_loader = _resolveComponent("loader");
    const _component_dropZone = _resolveComponent("dropZone");
    const _component_modal = _resolveComponent("modal");
    return (_openBlock(), _createBlock(_component_modal, {
        idModal: "uploadFile",
        showModal: $setup.showModal,
        key: $setup.versaComponentKey
    }, {
        modalTitle: _withCtx(() => [
            _createElementVNode("div", { class: "flex justify-between" }, [
                _createElementVNode("h3", { class: "text-lg font-medium text-gray-900 dark:text-white flex gap-2" }, [
                    _createTextVNode(" Importar Archivo "),
                    ($setup.showLoader)
                        ? (_openBlock(), _createBlock(_component_loader, { key: 0 }))
                        : _createCommentVNode("v-if", true)
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
            _createElementVNode("div", { class: "h-40" }, [
                _createVNode(_component_dropZone, {
                    fileTypeValid: $setup.fileTypes,
                    onAccion: $setup.accion,
                    files: $setup.files,
                    msgTiposArchivos: 'Tipos Validos: ' + $setup.fileTypes.join(', ') + ' - < 10 MB'
                }, null, 8 /* PROPS */, ["files", "msgTiposArchivos"])
            ])
        ]),
        modalFooter: _withCtx(() => [
            _createElementVNode("button", {
                type: "button",
                onClick: $event => ($setup.accion({ accion: 'closeModal' })),
                class: "text-xs text-gray-400 dark:text-white border border-transparent p-2 bg-primary-600 rounded-md shadow-sm hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500"
            }, " Cerrar ", 8 /* PROPS */, ["onClick"])
        ]),
        _: 1 /* STABLE */
    }, 8 /* PROPS */, ["showModal"]));
}
uploadFileExcel_component.render = render_uploadFileExcel_component;
export default uploadFileExcel_component;
//# sourceMappingURL=uploadFileExcel.vue.js.map