'use strict';

// @ts-ignore
import { computed, ref } from 'vue';

import { useFileZise, useValidFile } from '../composables/useValidFile.js';

/**
 * Componente de Vue.js para mostrar errores de archivos.
 * @component filesError
 * @props {Array} FilesErrors - Array de errores de archivos.
 * @returns {Object} - Objeto con la propiedad FilesErrors.
 */
export const filesError = {
    props: {
        FilesErrors: {
            type: Array,
            required: true,
        },
    },
    setup(props) {
        const FilesErrors = computed(() => props.FilesErrors);

        return {
            FilesErrors,
        };
    },
    template: `
        <div class="">
            <div class="p-0 m-0 overflow-auto">
                <ul class="" >
                    <li class="" v-for="item in FilesErrors">
                        <i class="bi bi-exclamation-circle text-red-500"></i>
                        {{ item.name }} - {{ item.error_msg }}
                    </li>
                </ul>
            </div>
        </div>
    `,
};

/**
 * Componente de modal para mostrar errores de archivos.
 * @typedef {Object} fileErrorModal
 * @property {Array} FilesErrors - Array de errores de archivos.
 * @property {Boolean} ShowModalFilesError - Indica si se debe mostrar el modal de errores de archivos.
 * @property {Function} accion - Función para manejar la acción del modal.
 */
export const fileErrorModal = {
    emits: ['accion'],
    props: {
        FilesErrors: {
            type: Array,
            required: true,
        },
        ShowModalFilesError: {
            type: Boolean,
            required: true,
        },
    },
    setup(props) {
        const FilesErrors = computed(() => props.FilesErrors);
        const ShowModalFilesError = computed(() => props.ShowModalFilesError);

        return {
            FilesErrors,
            ShowModalFilesError,
        };
    },
    methods: {
        accion(accion) {
            this.$emit('accion', accion);
        },
    },
    template: `
        <modal idModal="filesErrorModal" :showModal="ShowModalFilesError" @accion="accion" key="filesErrorModal">
            <template v-slot:modalTitle>Archivos con Errores</template>
            <template v-slot:modalBody>
                <filesError :FilesErrors="FilesErrors" key="fileErrorDropZone" />
            </template>
            <template v-slot:modalFooter>
                <button @click="accion({accion: 'closeModalFilesError'})" type="button" class="text-white bg-blue-700 hover:bg-blue-800 focus:ring-4 focus:outline-none focus:ring-blue-300 font-medium rounded-lg text-sm px-5 py-2.5 text-center inline-flex items-center dark:bg-blue-600 dark:hover:bg-blue-700 dark:focus:ring-blue-800">
                    Cancelar
                </button>
            </template>
        </modal>
    `,
};

/**
 * Componente de zona de arrastre de archivos.
 *
 * @emits {string} accion - Evento emitido al realizar una acción.
 * @prop {Array} FileTypeValid - Tipos de archivo válidos.
 * @prop {boolean} multiple - Indica si se permite seleccionar múltiples archivos.
 * @prop {number} nfilesMultiple - Número máximo de archivos permitidos cuando no se permite la selección múltiple.
 * @prop {Array} files - Archivos seleccionados.
 * @returns {object} - Objeto con las propiedades y métodos del componente.
 */
export const dropZone = {
    emits: ['accion'],
    props: {
        FileTypeValid: {
            type: Array,
            required: true,
        },
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
    },
    setup(props) {
        const mensaje = ref('Arrastra y Suelta Archivos');
        const multiple = ref(props.multiple);
        const classActive = ref(false);
        const files = computed(() => {
            if (props.files !== undefined) return props.files;
            return [];
        });
        const showModalFilesError = ref(false);
        const ArrayFilesErrors = ref([]);

        return {
            mensaje,
            classActive,
            showModalFilesError,
            ArrayFilesErrors,
            files,
            multiple,
        };
    },
    methods: {
        btn_SelectFile: function () {
            this.$refs.fileInput.click();
        },
        DesdeInputChange: function (e) {
            e.preventDefault();
            this.setFilesLocal(e.target.files);
        },
        drag: function (e) {
            e.preventDefault();
            this.classActive = true;
            this.mensaje = 'Suelta para Subir';
        },
        drop: function (e) {
            e.preventDefault();
            this.classActive = false;
            this.mensaje = 'Arrastra y Suelta Archivos';
            this.setFilesLocal(e.dataTransfer.files);
        },
        dragleave: function (e) {
            e.preventDefault();
            this.classActive = false;
            this.mensaje = 'Arrastra y Suelta Archivos';
        },
        validaFiles: function (file) {
            const self = this;

            if (!useValidFile(this.FileTypeValid, file)) {
                return {
                    name: file.name,
                    error_msg: 'Archivo no permitido',
                };
            }
            if (!useFileZise(file, 10)) {
                return {
                    name: file.name,
                    error_msg: 'Archivo no debe ser mayor a 10MB',
                };
            }

            const indexFile = self.files.findIndex(item => item.name === file.name);
            if (indexFile >= 0) {
                return {
                    name: file.name,
                    error_msg: 'Archivo ya existe en la lista',
                };
            }

            return true;
        },
        setFilesLocal(filesInput) {
            const self = this;
            const files = [];
            this.ArrayFilesErrors = [];
            if (filesInput.length <= 0) return;

            if (this.multiple === true && filesInput.length > this.nfilesMultiple) {
                this.ArrayFilesErrors.push({
                    name: 'Multiples Archivos',
                    error_msg: `Solo se permiten ${this.nfilesMultiple} archivo a la vez`,
                });
                this.showModalFilesError = true;
                return;
            }

            for (let file of filesInput) {
                const result = self.validaFiles(file);
                if (true === result)
                    files.push({
                        archivo: file.name,
                        type: file.type,
                        size: file.size,
                        file: file,
                    });
                else this.ArrayFilesErrors.push(result);
            }
            if (files.length > 0) this.accion({ accion: 'addFiles', files: this.multiple ? files : files[0] });
            if (this.ArrayFilesErrors.length > 0) this.showModalFilesError = true;
        },
        accion(/** @type {Object} */ accion) {
            const actions = {
                addFiles: () => this.$emit('accion', accion),
                closeModalFilesError: () => {
                    this.showModalFilesError = false;
                },
                closeModal: () => {
                    this.showModalFilesError = false;
                },
            };

            const selectedAction = actions[accion.accion] || actions['default'];
            if (typeof selectedAction === 'function') {
                selectedAction();
            }
        },
    },
    template: `
        <div class="w-full h-full dark:text-white">
            <a @click="btn_SelectFile" class="DragDropArea" ref="DragDropArea"  :class="classActive?'active':''" @dragover="drag" @drop="drop" @dragleave="dragleave" style="cursor:pointer;" title="Puedes dar click o arrastrar los archivos">
                <div class="text-center">
                    <h4 class="text-center">{{ mensaje }}</h4>
                </div>
                <p class="font-bold text-xs text-center">Tipos Validos: .png .jpg .svg</p>
                <p class="font-bold text-xs text-center">Tamaño Maximo: 10MB</p>

                <input type="file" id="file" ref="fileInput" name="file" class="form-control" @change="DesdeInputChange" hidden :multiple="multiple"/>
            </a>
            <fileErrorModal :ShowModalFilesError="showModalFilesError" :FilesErrors="ArrayFilesErrors" @accion="accion" key="fileErrorModal" />
        </div>
    `,
};
