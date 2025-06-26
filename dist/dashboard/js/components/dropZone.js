(function () {
    let styleTag = document.createElement('style');
    styleTag.setAttribute('data-v-r9ygdrcgqf', '');
    styleTag.innerHTML = `
.DragDropArea[data-v-r9ygdrcgqf] {
        border: 3px dashed #5f5b5b;
        height: 100%;
        width: 100%;
        border-radius: 5px;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-direction: column;
        cursor: pointer;
        transition:
            background-color 0.3s,
            border-color 0.3s,
            color 0.3s;
        margin: 0;
        padding: 0;
        overflow: hidden;
        position: relative;
        background-color: #f5f5f5;
        color: #bdbdbd;
        text-transform: uppercase;
        font-family: 'Roboto', sans-serif;
        box-shadow: 0px 0px 10px 0px rgba(0, 0, 0, 0.75);
}
.DragDropArea.active[data-v-r9ygdrcgqf] {
        background-color: #b8d4fe;
        color: black;
        border: 4px dashed #618ac9;
}
.DragDropArea h4[data-v-r9ygdrcgqf] {
        font-size: 25px;
        font-weight: 500;
        color: #000;
        margin: 0;
        padding: 10px;
}
.DragDropArea span[data-v-r9ygdrcgqf] {
        font-size: 12px;
        font-weight: 500;
        color: #000;
        margin-top: 5px;
}
.DragDropArea .icon[data-v-r9ygdrcgqf] {
        font-size: 50px;
        color: #bdbdbd;
        margin-bottom: 10px;
        transition: color 0.3s;
}
.DragDropArea.active .icon[data-v-r9ygdrcgqf] {
        color: #618ac9;
}
.DragDropArea .loader[data-v-r9ygdrcgqf] {
        margin-top: 10px;
}
`;
    document.head.appendChild(styleTag);
})();
import { defineComponent as _defineComponent } from '/node_modules/vue/dist/vue.esm-browser.js';

import Swal from '/node_modules/sweetalert2/dist/sweetalert2.esm.all.js';
import { computed, ref } from '/node_modules/vue/dist/vue.esm-browser.js';
import loader from '@/dashboard/js/components/loader.vue';
import { $dom } from '@/dashboard/js/composables/dom';
import { useFileZise, useValidFile, } from '@/dashboard/js/composables/useValidFile';
const dropZone_component = /*@__PURE__*/ _defineComponent({
    __file: 'C:/Users/jjara/Desktop/proyectos/versaWYS-PHP/src/dashboard/js/components/dropZone.vue',
    __name: 'dropZone',
    components: {
        loader
    },
    props: {
        multiple: {
            type: Boolean,
            required: false,
            default: false,
        },
        nfilesMultiple: {
            type: Number,
            required: false,
            default: 1,
        },
        files: {
            type: Array,
            required: false,
        },
        msgTiposArchivos: {
            type: String,
            required: false,
            default: 'Tipos Validos: .doc .docx .pdf .xlsx .xls .ppt .pptx - < 10 MB',
        },
        fileTypeValid: {
            type: Array,
            required: false,
            default: () => ['xlsx', 'xls', 'doc', 'docx', 'pdf', 'ppt', 'pptx'],
        },
        maxSizeFileMB: {
            type: Number,
            required: false,
            default: 10,
        },
    },
    emits: ['accion'],
    setup(__props, { expose: __expose, emit: __emit }) {
        __expose();
        const versaComponentKey = ref(0);
        const emit = __emit;
        const props = __props;
        const multiple = computed(() => props.multiple);
        const nfilesMultiple = computed(() => props.nfilesMultiple);
        const fileTypeValid = computed(() => props.fileTypeValid);
        const msgTiposArchivos = computed(() => props.msgTiposArchivos);
        const maxSizeFile = computed(() => props.maxSizeFileMB);
        const ArrayFilesErrors = ref([]);
        const files = computed(() => {
            if (props.files !== undefined && null !== props.files) {
                return props.files;
            }
            return [];
        });
        const mensaje = ref('Arrastra y Suelta Archivos');
        const classActive = ref(false);
        const loading = ref(false);
        const btn_SelectFile = () => {
            loading.value = true;
        };
        const showError = async () => {
            const htmlInner = `
            <div>
                <div class="p-0 m-0 overflow-auto">
                    <ul class="list-group list-group-flush">
                        ${ArrayFilesErrors.value
                .map((
            /** @type {{ name: string; error_msg: string; }} */ item) => `
                                    <li>
                                        <i
                                            class="bi bi-exclamation-circle text-red-500"></i>
                                        ${item.name} - ${item.error_msg}
                                    </li>
                                `)
                .join('')}
                    </ul>
                </div>
            </div>
        `;
            await Swal.fire({
                title: 'Error al cargar Archivos',
                html: htmlInner,
                icon: 'error',
                confirmButtonColor: '#3085d6',
                confirmButtonText: 'Salir',
                customClass: {
                    popup: 'swal-wide',
                    htmlContainer: 'swal-target',
                },
            });
        };
        const validaFiles = (file) => {
            if (!useValidFile(fileTypeValid.value, file)) {
                return {
                    name: file.name,
                    error_msg: 'Tipo de Archivo no permitido',
                    isValid: false,
                };
            }
            if (!useFileZise(file, maxSizeFile.value)) {
                return {
                    name: file.name,
                    error_msg: 'Archivo no debe ser mayor a 10MB',
                    isValid: false,
                };
            }
            if (null === files.value) {
                return {
                    name: file.name,
                    isValid: true,
                };
            }
            const indexFile = files.value.findIndex((item) => item?.archivo === file.name);
            if (-1 !== indexFile) {
                return {
                    name: file.name,
                    error_msg: 'Archivo ya existe en la lista',
                    isValid: false,
                };
            }
            return {
                name: file.name,
                isValid: true,
            };
        };
        const setFilesLocal = (filesInput) => {
            ArrayFilesErrors.value = [];
            if (!filesInput || filesInput.length <= 0) {
                loading.value = false;
                return;
            }
            if (filesInput.length > nfilesMultiple.value) {
                ArrayFilesErrors.value.push({
                    name: 'Multiples Archivos',
                    error_msg: `Solo se permiten ${nfilesMultiple.value} archivo a la vez`,
                });
            }
            if (ArrayFilesErrors.value.length > 0) {
                showError();
                loading.value = false;
                return;
            }
            for (const file of filesInput) {
                const result = validaFiles(file);
                if (result.isValid) {
                    files.value.push({
                        archivo: file.name,
                        type: file.type,
                        size: file.size,
                        file: file,
                    });
                }
                else {
                    ArrayFilesErrors.value.push(result);
                }
            }
            const inputFile = $dom('#file');
            if (inputFile && inputFile instanceof HTMLInputElement) {
                inputFile.value = '';
            }
            loading.value = false;
            if (ArrayFilesErrors.value.length > 0) {
                showError();
            }
            else if (files.value.length > 0) {
                emit('accion', {
                    accion: 'addFiles',
                    files: multiple.value ? files.value : files.value[0],
                });
            }
        };
        const DesdeInputChange = (e) => {
            e.preventDefault();
            const target = e.target;
            if (target.files) {
                setFilesLocal(target.files);
            }
        };
        const drag = (e) => {
            e.preventDefault();
            classActive.value = true;
            mensaje.value = 'Suelta para Subir';
        };
        const drop = (e) => {
            loading.value = true;
            e.preventDefault();
            classActive.value = false;
            mensaje.value = 'Arrastra y Suelta Archivos';
            if (e.dataTransfer?.files) {
                setFilesLocal(e.dataTransfer.files);
            }
        };
        const dragleave = (e) => {
            e.preventDefault();
            classActive.value = false;
            mensaje.value = 'Arrastra y Suelta Archivos';
        };
        const __returned__ = { versaComponentKey, emit, props, multiple, nfilesMultiple, fileTypeValid, msgTiposArchivos, maxSizeFile, ArrayFilesErrors, files, mensaje, classActive, loading, btn_SelectFile, showError, validaFiles, setFilesLocal, DesdeInputChange, drag, drop, dragleave, loader };
        Object.defineProperty(__returned__, '__isScriptSetup', { enumerable: false, value: true });
        return __returned__;
    }
});
import { toDisplayString as _toDisplayString, resolveComponent as _resolveComponent, openBlock as _openBlock, createBlock as _createBlock, createCommentVNode as _createCommentVNode, createTextVNode as _createTextVNode, createElementVNode as _createElementVNode, normalizeClass as _normalizeClass, createElementBlock as _createElementBlock } from "/node_modules/vue/dist/vue.esm-browser.js";
function render_dropZone_component(_ctx, _cache, $props, $setup, $data, $options) {
    const _component_loader = _resolveComponent("loader");
    return (_openBlock(), _createElementBlock("div", {
        class: "w-full h-full dark:text-white",
        key: $setup.versaComponentKey
    }, [
        _createElementVNode("label", {
            class: _normalizeClass(["DragDropArea", $setup.classActive ? 'active' : '']),
            style: { "cursor": "pointer" },
            onClick: $setup.btn_SelectFile,
            onDragleave: $setup.dragleave,
            onDragover: $setup.drag,
            onDrop: $setup.drop,
            ref: "DragDropArea",
            title: "Puedes dar click o arrastrar los archivos"
        }, [
            _createElementVNode("div", { class: "absolute z-10 pointer-events-none p-2" }, [
                _createElementVNode("div", { class: "text-center" }, [
                    _createElementVNode("h4", { class: "text-center text-sm/6" }, [
                        _createTextVNode(_toDisplayString($setup.mensaje) + " ", 1 /* TEXT */),
                        ($setup.loading)
                            ? (_openBlock(), _createBlock(_component_loader, { key: 0 }))
                            : _createCommentVNode("v-if", true)
                    ])
                ]),
                _createElementVNode("span", { class: "font-bold text-xs text-center" }, _toDisplayString($setup.msgTiposArchivos), 1 /* TEXT */)
            ]),
            _createElementVNode("input", {
                id: "file",
                type: "file",
                multiple: $setup.multiple,
                onChange: $setup.DesdeInputChange,
                hidden: "",
                name: "file",
                ref: "fileInput"
            }, null, 40 /* PROPS, NEED_HYDRATION */, ["multiple"])
        ], 34 /* CLASS, NEED_HYDRATION */)
    ]));
}
dropZone_component.render = render_dropZone_component;
dropZone_component.__scopeId = 'data-v-r9ygdrcgqf';
export default dropZone_component;
//# sourceMappingURL=dropZone.vue.js.map