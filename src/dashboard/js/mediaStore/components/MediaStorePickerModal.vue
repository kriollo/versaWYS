<script setup lang="ts">
    import type { VersaFetchResponse } from 'versaTypes';
    import { computed, inject, ref, type Ref, watch } from 'vue';

    import Modal from '@/dashboard/js/components/modal.vue';
    import { versaAlert, versaFetch } from '@/dashboard/js/functions';
    import MediaThumbnail from '@/dashboard/js/mediaStore/components/MediaThumbnail.vue';
    import type { MediaAsset, MediaFolder, MediaType } from '@/dashboard/js/mediaStore/types';

    interface Props {
        show: boolean;
        panelUrl: string;
        empresaToken: string;
        csrfToken: string;
        mediaType: MediaType;
    }

    const props = defineProps<Props>();

    const mediaTypes: { value: MediaType; label: string; icon: string }[] = [
        { value: 'image', label: 'Imágenes', icon: 'bi-image' },
        { value: 'video', label: 'Videos', icon: 'bi-film' },
        { value: 'audio', label: 'Audios', icon: 'bi-mic' },
        { value: 'file', label: 'Archivos', icon: 'bi-file-earmark' },
    ];

    const selectedType = ref<MediaType>(props.mediaType);

    const emit = defineEmits<{
        close: [];
        select: [asset: MediaAsset];
    }>();

    const loading = ref(false);
    const uploading = ref(false);
    const deletingId = ref<number | null>(null);
    const selectedFiles = ref<File[]>([]);
    const assets = ref<MediaAsset[]>([]);
    const csrf_token = inject('csrf_token') as Ref<string>;

    // Estado de navegación de carpetas
    const currentFolderId = ref<number | null>(null);
    const folderPath = ref<{ id: number; name: string }[]>([]);
    const folders = ref<MediaFolder[]>([]);
    const newFolderName = ref('');
    const showNewFolderInput = ref(false);
    const creatingFolder = ref(false);

    const acceptByType = computed(() => {
        switch (selectedType.value) {
            case 'image': {
                return 'image/*';
            }
            case 'video': {
                return 'video/*';
            }
            case 'audio': {
                return 'audio/*';
            }
            default: {
                return '.pdf,.txt,.doc,.docx,.xls,.xlsx,.zip';
            }
        }
    });

    const closeModal = () => {
        emit('close');
    };

    const formatBytes = (bytes: number): string => {
        if (!bytes || bytes <= 0) {
            return '0 B';
        }

        const units = ['B', 'KB', 'MB', 'GB'];
        let value = bytes;
        let index = 0;
        while (value >= 1024 && index < units.length - 1) {
            value /= 1024;
            index += 1;
        }

        return `${value.toFixed(value >= 10 ? 0 : 1)} ${units[index]}`;
    };

    const loadFolders = async () => {
        try {
            const parentParam =
                currentFolderId.value !== null ? `parent_folder_id=${currentFolderId.value}` : 'parent_folder_id=';
            const response: VersaFetchResponse = await versaFetch({
                url: `/${props.panelUrl}/mediaStore/api/folders/${props.empresaToken}?${parentParam}`,
                method: 'GET',
            });

            folders.value = response.success === 1 && Array.isArray(response.data) ? response.data : [];
        } catch {
            folders.value = [];
        }
    };

    const loadAssets = async () => {
        if (!props.show) {
            return;
        }
        loading.value = true;
        try {
            const folderParam =
                currentFolderId.value !== null ? `folder_id=${currentFolderId.value}` : 'folder_id=root';

            const response: VersaFetchResponse = await versaFetch({
                url: `/${props.panelUrl}/mediaStore/api/list/${props.empresaToken}?per_page=200&media_type=${selectedType.value}&${folderParam}`,
                method: 'GET',
            });

            if (response.success === 1 && Array.isArray(response.data)) {
                assets.value = response.data as MediaAsset[];
            } else {
                assets.value = [];
            }
        } catch {
            assets.value = [];
            versaAlert({
                type: 'error',
                title: 'Error al cargar archivos',
                message: 'No fue posible obtener la biblioteca de archivos.',
            });
        } finally {
            loading.value = false;
        }
    };

    const navigateToFolder = async (folder: MediaFolder) => {
        folderPath.value = [...folderPath.value, { id: folder.id, name: folder.name }];
        currentFolderId.value = folder.id;
        await Promise.all([loadFolders(), loadAssets()]);
    };

    const navigateToBreadcrumb = async (index: number) => {
        if (index === -1) {
            // Raíz
            folderPath.value = [];
            currentFolderId.value = null;
        } else {
            folderPath.value = folderPath.value.slice(0, index + 1);
            currentFolderId.value = folderPath.value[index]?.id ?? null;
        }
        await Promise.all([loadFolders(), loadAssets()]);
    };

    const createFolder = async () => {
        const name = newFolderName.value.trim();
        if (!name) {
            return;
        }

        creatingFolder.value = true;
        try {
            const response: VersaFetchResponse = await versaFetch({
                url: `/${props.panelUrl}/mediaStore/api/folders/${props.empresaToken}`,
                method: 'POST',
                data: {
                    name,
                    parent_folder_id: currentFolderId.value ?? '',
                    csrf_token: csrf_token.value,
                },
            });

            if (response.success === 1) {
                newFolderName.value = '';
                showNewFolderInput.value = false;
                await loadFolders();
            } else {
                versaAlert({
                    type: 'error',
                    title: 'Error',
                    message: response.message || 'No fue posible crear la carpeta.',
                });
            }
        } catch {
            versaAlert({
                type: 'error',
                title: 'Error de red',
                message: 'No fue posible crear la carpeta.',
            });
        } finally {
            creatingFolder.value = false;
        }
    };

    const uploadFile = async () => {
        if (selectedFiles.value.length === 0) {
            versaAlert({
                type: 'warning',
                title: 'Archivos requeridos',
                message: 'Selecciona al menos un archivo antes de subir.',
            });
            return;
        }

        uploading.value = true;
        try {
            const formData = new FormData();
            selectedFiles.value.forEach(file => {
                formData.append('media[]', file);
            });
            formData.append('media_type', selectedType.value);
            formData.append('csrf_token', csrf_token.value);
            if (currentFolderId.value !== null) {
                formData.append('folder_id', String(currentFolderId.value));
            }

            const response: VersaFetchResponse = await versaFetch({
                url: `/${props.panelUrl}/mediaStore/api/upload/${props.empresaToken}`,
                method: 'POST',
                data: formData,
            });

            if (response.success === 1) {
                selectedFiles.value = [];
                const fileInput = document.querySelector('#modal-upload-input') as HTMLInputElement;
                if (fileInput) {
                    fileInput.value = '';
                }

                await loadAssets();
                versaAlert({
                    type: 'success',
                    title: 'Archivos subidos',
                    message: 'Los archivos se subieron correctamente.',
                });
            } else {
                versaAlert({
                    type: 'error',
                    title: 'No se pudo subir',
                    message: response.message || 'Error al subir archivo.',
                });
            }
        } catch {
            versaAlert({
                type: 'error',
                title: 'Error de red',
                message: 'No fue posible subir el archivo.',
            });
        } finally {
            uploading.value = false;
        }
    };

    const deleteAsset = async (assetId: number) => {
        deletingId.value = assetId;
        try {
            const response: VersaFetchResponse = await versaFetch({
                url: `/${props.panelUrl}/mediaStore/api/delete/${props.empresaToken}`,
                method: 'POST',
                data: {
                    id: assetId,
                    csrf_token: csrf_token.value,
                },
            });

            if (response.success === 1) {
                await loadAssets();
            } else {
                versaAlert({
                    type: 'error',
                    title: 'No se pudo eliminar',
                    message: response.message || 'Error al eliminar archivo.',
                });
            }
        } catch {
            versaAlert({
                type: 'error',
                title: 'Error de red',
                message: 'No fue posible eliminar el archivo.',
            });
        } finally {
            deletingId.value = null;
        }
    };

    const handleFileChange = (event: Event) => {
        const target = event.target as HTMLInputElement;
        if (target.files) {
            selectedFiles.value.push(...target.files);
        }
    };

    const removeSelectedFile = (index: number) => {
        selectedFiles.value.splice(index, 1);
        if (selectedFiles.value.length === 0) {
            const fileInput = document.querySelector('#modal-upload-input') as HTMLInputElement;
            if (fileInput) {
                fileInput.value = '';
            }
        }
    };

    const selectAsset = (asset: MediaAsset) => {
        emit('select', asset);
        emit('close');
    };

    watch(selectedType, () => {
        loadAssets();
    });

    watch(
        () => props.show,
        show => {
            if (show) {
                selectedFiles.value = [];
                selectedType.value = props.mediaType;
                currentFolderId.value = null;
                folderPath.value = [];
                newFolderName.value = '';
                showNewFolderInput.value = false;
                Promise.all([loadFolders(), loadAssets()]);
            }
        },
    );
</script>

<template>
    <Modal
        :showModal="show"
        idModal="mediaStorePickerModal"
        size="max-w-4xl"
        zIndexClass="z-[70]"
        :drag="true"
        :resizable="true"
        :blurBackground="false"
        @accion="closeModal">
        <template #modalTitle>
            <div class="flex items-center gap-2">
                <i class="bi bi-images text-brand"></i>
                <h3 class="text-lg font-bold text-gray-900 dark:text-white">Biblioteca de Archivos</h3>
            </div>
        </template>

        <template #modalBody>
            <div class="space-y-4">
                <!-- Selector de tipo -->
                <div class="flex items-center gap-1 p-1 bg-gray-50 dark:bg-gray-800/50 rounded-xl w-fit">
                    <button
                        v-for="type in mediaTypes"
                        :key="type.value"
                        type="button"
                        @click="selectedType = type.value"
                        class="flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium rounded-lg transition-all duration-200"
                        :class="
                            selectedType === type.value
                                ? 'bg-white dark:bg-gray-700 text-brand shadow-sm ring-1 ring-black/5 dark:ring-white/10'
                                : 'text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200'
                        ">
                        <i :class="['bi', type.icon]"></i>
                        {{ type.label }}
                    </button>
                </div>

                <!-- Zona de subida -->
                <div
                    class="flex flex-col md:flex-row items-start md:items-end gap-3 p-4 bg-gray-50 dark:bg-gray-800/50 rounded-2xl border border-gray-100 dark:border-gray-800">
                    <div class="flex-1 w-full">
                        <label class="block text-xs font-bold text-gray-500 dark:text-gray-400 uppercase mb-2 ml-1">
                            Subir nuevos archivos
                        </label>
                        <div class="flex flex-wrap gap-2 mb-2" v-if="selectedFiles.length > 0">
                            <div
                                v-for="(file, index) in selectedFiles"
                                :key="index"
                                class="flex items-center gap-2 px-3 py-1 bg-brand/10 text-brand border border-brand/20 rounded-full text-[10px]">
                                <span class="max-w-[120px] truncate">{{ file.name }}</span>
                                <button @click="removeSelectedFile(index)" class="hover:text-red-500 transition-colors">
                                    <i class="bi bi-x-lg"></i>
                                </button>
                            </div>
                        </div>
                        <div class="relative">
                            <input
                                id="modal-upload-input"
                                type="file"
                                multiple
                                :accept="acceptByType"
                                class="hidden"
                                @change="handleFileChange" />
                            <label
                                for="modal-upload-input"
                                class="flex items-center gap-2 px-4 py-2.5 text-sm bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-xl cursor-pointer hover:border-brand transition-colors truncate">
                                <i class="bi bi-cloud-arrow-up text-brand"></i>
                                <span class="text-gray-600 dark:text-gray-300">
                                    {{
                                        selectedFiles.length > 0
                                            ? `${selectedFiles.length} archivos seleccionados`
                                            : 'Seleccionar archivos...'
                                    }}
                                </span>
                            </label>
                        </div>
                    </div>
                    <button
                        :disabled="uploading || selectedFiles.length === 0"
                        class="px-6 py-2.5 rounded-xl text-white bg-brand font-bold hover:shadow-lg hover:shadow-brand/20 active:scale-95 transition-all disabled:opacity-50"
                        @click="uploadFile">
                        <span v-if="uploading" class="spinner-border spinner-border-sm mr-2"></span>
                        {{ uploading ? 'Subiendo' : 'Subir' }}
                    </button>
                </div>

                <!-- Breadcrumb y toolbar de carpetas -->
                <div class="flex items-center justify-between gap-2">
                    <nav class="flex items-center gap-1 text-xs text-gray-500 dark:text-gray-400 flex-wrap">
                        <button
                            class="flex items-center gap-1 hover:text-brand transition-colors font-medium"
                            @click="navigateToBreadcrumb(-1)">
                            <i class="bi bi-house-fill text-xs"></i>
                            Inicio
                        </button>
                        <template v-for="(crumb, idx) in folderPath" :key="crumb.id">
                            <i class="bi bi-chevron-right text-[10px] text-gray-300 dark:text-gray-600"></i>
                            <button
                                class="hover:text-brand transition-colors"
                                :class="
                                    Number(idx) === folderPath.length - 1
                                        ? 'text-gray-700 dark:text-gray-300 font-semibold'
                                        : ''
                                "
                                @click="navigateToBreadcrumb(Number(idx))">
                                {{ crumb.name }}
                            </button>
                        </template>
                    </nav>

                    <!-- Nueva carpeta -->
                    <div v-if="showNewFolderInput" class="flex items-center gap-1">
                        <input
                            v-model="newFolderName"
                            type="text"
                            placeholder="Nombre carpeta"
                            class="text-xs px-2 py-1 rounded-lg border border-brand bg-white dark:bg-gray-900 text-gray-900 dark:text-white focus:outline-none w-32"
                            @keyup.enter="createFolder"
                            @keyup.esc="showNewFolderInput = false"
                            autofocus />
                        <button
                            @click="createFolder"
                            :disabled="creatingFolder || !newFolderName.trim()"
                            class="text-xs px-2 py-1 rounded-lg bg-brand text-white disabled:opacity-50">
                            <span v-if="creatingFolder" class="spinner-border spinner-border-sm w-3 h-3"></span>
                            <i v-else class="bi bi-check"></i>
                        </button>
                        <button
                            @click="showNewFolderInput = false"
                            class="text-xs px-2 py-1 rounded-lg bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300">
                            <i class="bi bi-x"></i>
                        </button>
                    </div>
                    <button
                        v-else
                        @click="showNewFolderInput = true"
                        class="flex items-center gap-1 text-xs px-2 py-1.5 rounded-lg bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-400 hover:bg-gray-200 dark:hover:bg-gray-700 transition-colors whitespace-nowrap">
                        <i class="bi bi-folder-plus"></i>
                        Nueva carpeta
                    </button>
                </div>

                <!-- Grid de contenido -->
                <div class="relative">
                    <div v-if="loading" class="flex flex-col items-center justify-center py-12">
                        <div class="spinner-border text-brand mb-4"></div>
                        <p class="text-sm text-gray-500">Cargando biblioteca...</p>
                    </div>

                    <div
                        v-else-if="folders.length === 0 && assets.length === 0"
                        class="flex flex-col items-center justify-center py-12 bg-gray-50/50 dark:bg-gray-800/30 rounded-2xl border border-dashed border-gray-200 dark:border-gray-700">
                        <i class="bi bi-folder2-open text-3xl text-gray-300 mb-2"></i>
                        <p class="text-sm text-gray-500">No hay archivos disponibles</p>
                    </div>

                    <div
                        v-else
                        class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-4 max-h-[400px] overflow-y-auto p-1 custom-scrollbar">
                        <!-- Carpetas primero -->
                        <div
                            v-for="folder in folders"
                            :key="`folder-${folder.id}`"
                            class="group relative bg-yellow-50 dark:bg-yellow-900/10 rounded-xl border border-yellow-100 dark:border-yellow-800/30 shadow-sm hover:shadow-md transition-all overflow-hidden cursor-pointer"
                            @click="navigateToFolder(folder)">
                            <div class="aspect-square flex flex-col items-center justify-center gap-2 p-3">
                                <i class="bi bi-folder-fill text-3xl text-yellow-400"></i>
                            </div>
                            <div class="p-2 border-t border-yellow-100/50 dark:border-yellow-800/20">
                                <p
                                    class="text-[11px] font-medium text-gray-700 dark:text-gray-300 truncate"
                                    :title="folder.name">
                                    {{ folder.name }}
                                </p>
                            </div>
                        </div>

                        <!-- Archivos -->
                        <div
                            v-for="asset in assets"
                            :key="asset.id"
                            class="group relative bg-white dark:bg-gray-900 rounded-xl border border-gray-100 dark:border-gray-800 shadow-sm hover:shadow-md transition-all overflow-hidden cursor-pointer"
                            @click="selectAsset(asset)">
                            <div class="aspect-square relative">
                                <MediaThumbnail
                                    :type="asset.media_type"
                                    :url="asset.public_url"
                                    :mime="asset.mime"
                                    className="w-full h-full border-0 rounded-none" />

                                <div
                                    class="absolute inset-0 bg-brand/10 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center">
                                    <div
                                        class="bg-brand text-white text-[10px] font-bold px-2 py-1 rounded-full shadow-lg">
                                        SELECCIONAR
                                    </div>
                                </div>
                            </div>

                            <div class="p-2 border-t border-gray-50 dark:border-gray-800">
                                <p
                                    class="text-[11px] font-medium text-gray-700 dark:text-gray-300 truncate"
                                    :title="asset.original_name">
                                    {{ asset.original_name }}
                                </p>
                            </div>

                            <button
                                @click.stop="deleteAsset(asset.id)"
                                class="absolute top-1 right-1 w-6 h-6 flex items-center justify-center rounded-lg bg-red-500/80 text-white opacity-0 group-hover:opacity-100 transition-opacity hover:bg-red-500"
                                :disabled="deletingId === asset.id">
                                <i v-if="deletingId === asset.id" class="spinner-border spinner-border-sm w-3 h-3"></i>
                                <i v-else class="bi bi-trash text-xs"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </template>

        <template #modalFooter>
            <div class="w-full flex justify-end">
                <button
                    class="px-5 py-2 text-sm font-bold rounded-xl bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-400 hover:bg-gray-200 dark:hover:bg-gray-700 transition-colors"
                    @click="closeModal">
                    Cerrar
                </button>
            </div>
        </template>
    </Modal>
</template>

<style scoped>
    .custom-scrollbar::-webkit-scrollbar {
        width: 4px;
    }
    .custom-scrollbar::-webkit-scrollbar-track {
        background: transparent;
    }
    .custom-scrollbar::-webkit-scrollbar-thumb {
        background: #cbd5e1;
        border-radius: 10px;
    }
    .dark .custom-scrollbar::-webkit-scrollbar-thumb {
        background: #475569;
    }
</style>
