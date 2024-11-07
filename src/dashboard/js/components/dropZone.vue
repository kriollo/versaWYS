<script setup>
import { loader } from '@/dashboard/js/components/loader';
import { $dom } from '@/dashboard/js/composables/dom';
import {
    useFileZise,
    useValidFile,
} from '@/dashboard/js/composables/useValidFile';
import Swal from 'sweetalert2';
import { computed, ref } from 'vue';

const emit = defineEmits(['accion']);
const props = defineProps({
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
        default:
            'Tipos Validos: .doc .docx .pdf .xlsx .xls .ppt .pptx - < 10 MB',
    },
    fileTypeValid: {
        type: Array,
        required: false,
        default: () => ['xlsx', 'xls', 'doc', 'docx', 'pdf', 'ppt', 'pptx'],
    },
});
const multiple = computed(() => props.multiple);
const nfilesMultiple = computed(() => props.nfilesMultiple);
const fileTypeValid = computed(() => props.fileTypeValid);
const msgTiposArchivos = computed(() => props.msgTiposArchivos);
const ArrayFilesErrors = ref([]);

const files = computed(() => {
    if (props.files !== undefined) return props.files;
    return [];
});

const mensaje = ref('Arrastra y Suelta Archivos');
const classActive = ref(false);
const loading = ref(false);

const btn_SelectFile = () => {
    loading.value = true;
};

const showError = async () => {
    const htmlInner = /*html*/ `
                        <div>
                            <div class="p-0 m-0 overflow-auto">
                                <ul class="list-group list-group-flush">
                                    ${ArrayFilesErrors.value
            .map(
                (
                                                /** @type {{ name: string; error_msg: string; }} */ item,
                ) => /*html*/ `<li>
                                                    <i class="bi bi-exclamation-circle text-red-500"></i>
                                                    ${item.name} - ${item.error_msg}
                                                </li>`,
            )
            .join('')}
                                </ul>
                            </div>
                        </div>`;

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

const validaFiles = file => {
    if (!useValidFile(fileTypeValid.value, file)) {
        return {
            name: file.name,
            error_msg: 'Tipo de Archivo no permitido',
            isValid: false,
        };
    }
    if (!useFileZise(file, 10)) {
        return {
            name: file.name,
            error_msg: 'Archivo no debe ser mayor a 10MB',
            isValid: false,
        };
    }

    const indexFile = files.value.findIndex(
        (/** @type {{ archivo: any; }} */ item) =>
            item?.archivo === file.name,
    );
    if (indexFile >= 0) {
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

const setFilesLocal = filesInput => {
    ArrayFilesErrors.value = [];
    if (filesInput.length <= 0) {
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
        if (result.isValid)
            files.value.push({
                archivo: file.name,
                type: file.type,
                size: file.size,
                file: file,
            });
        else ArrayFilesErrors.value.push(result);
    }
    const inputFile = $dom('#file');
    if (inputFile && inputFile instanceof HTMLInputElement) {
        inputFile.value = '';
    }

    loading.value = false;

    if (ArrayFilesErrors.value.length > 0) {
        showError();
    } else if (files.value.length > 0) {
        emit('accion', {
            accion: 'addFiles',
            files: multiple.value ? files.value : files.value[0],
        });
    }
};

const DesdeInputChange = e => {
    e.preventDefault();
    setFilesLocal(e.target.files);
};
const drag = (/** @type {{ preventDefault: () => void; }} */ e) => {
    e.preventDefault();
    classActive.value = true;
    mensaje.value = 'Suelta para Subir';
};
const drop = (
        /** @type {{ preventDefault: () => void; dataTransfer: { files: any; }; }} */ e,
) => {
    loading.value = true;
    e.preventDefault();
    classActive.value = false;
    mensaje.value = 'Arrastra y Suelta Archivos';
    setFilesLocal(e.dataTransfer.files);
};
const dragleave = e => {
    e.preventDefault();
    classActive.value = false;
    mensaje.value = 'Arrastra y Suelta Archivos';
};
</script>
<template>
    <div class="w-full h-full dark:text-white">
        <label class="DragDropArea" style="cursor: pointer" :class="classActive ? 'active' : ''" @click="btn_SelectFile"
            @dragleave="dragleave" @dragover="drag" @drop="drop" ref="DragDropArea"
            title="Puedes dar click o arrastrar los archivos">
            <div class="absolute z-10 pointer-events-none">
                <div class="text-center">
                    <h4 class="text-center">
                        {{ mensaje }}
                        <loader v-if="loading"></loader>
                    </h4>
                </div>
                <span class="font-bold text-xs text-center" for="file">
                    {{ msgTiposArchivos }}
                </span>
            </div>

            <input id="file" type="file" :multiple="multiple" @change="DesdeInputChange" hidden name="file"
                ref="fileInput" />
        </label>
    </div>
</template>
<style scoped>
.DragDropArea {
    border: 3px dashed #5f5b5b;
    height: 100%;
    width: 100%;
    border-radius: 5px;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-direction: column;
    cursor: pointer;
    transition: 0.3s;
    margin: 0px;
    padding: 0px;
    overflow: hidden;
    position: relative;
    background-color: #f5f5f5;
    color: #bdbdbd;
    font-size: 20px;
    font-weight: 500;
    text-align: center;
    text-transform: uppercase;
    font-family: 'Roboto', sans-serif;
    box-shadow: 0px 0px 10px 0px rgba(0, 0, 0, 0.75);
    -webkit-box-shadow: 0px 0px 10px 0px rgba(0, 0, 0, 0.75);
    -moz-box-shadow: 0px 0px 10px 0px rgba(0, 0, 0, 0.75);
}

.DragDropArea.active {
    background-color: #b8d4fe;
    color: black;
    border: 4px dashed #618ac9;
}

.DragDropArea h4 {
    font-size: 25px;
    font-weight: 500;
    color: #000;
}

.DragDropArea span {
    font-size: 12px;
    font-weight: 500;
    color: #000;
}
</style>
