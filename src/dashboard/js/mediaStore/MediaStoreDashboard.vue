<script setup lang="ts">
    import Swal from 'sweetalert2';
    import type { VersaFetchResponse } from 'versaTypes';
    import { computed, inject, onMounted, ref, type Ref, watch } from 'vue';

    import Breadcrumb from '@/dashboard/js/components/breadcrumb.vue';
    import { versaAlert, versaFetch } from '@/dashboard/js/functions';
    import MediaThumbnail from '@/dashboard/js/mediaStore/components/MediaThumbnail.vue';
    import type { MediaAsset, MediaFolder, MediaType } from '@/dashboard/js/mediaStore/types';

    interface BreadcrumbItem {
        type: string;
        title: string;
        icon: string;
        link: string;
    }

    const panelUrl = inject<string>('panelUrl', '');
    const empresaSelected = inject<string>('empresaSelected', '');
    const csrfToken = inject<Ref<string>>('csrf_token', ref(''));

    const mediaTypes: { value: MediaType; label: string; icon: string }[] = [
        { value: 'image', label: 'Imágenes', icon: 'bi-image' },
        { value: 'video', label: 'Videos', icon: 'bi-film' },
        { value: 'audio', label: 'Audios', icon: 'bi-mic' },
        { value: 'file', label: 'Archivos', icon: 'bi-file-earmark' },
    ];

    const breadCrumbList: BreadcrumbItem[] = [
        {
            type: 'link',
            title: 'Home',
            icon: '<svg class="w-3 h-3 me-2.5" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg"><path d="m19.707 9.293-2-2-7-7a1 1 0 0 0-1.414 0l-7 7-2 2a1 1 0 0 0 1.414 1.414L2 10.414V18a2 2 0 0 0 2 2h3a1 1 0 0 0 1-1v-4a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1v4a1 1 0 0 0 1 1h3a2 2 0 0 0 2-2v-7.586l.293.293a1 1 0 0 0 1.414-1.414Z"/></svg>',
            link: `/${panelUrl}/dashboard`,
        },
        {
            type: 'text',
            title: 'Media Store',
            icon: '<svg class="w-3 h-3 text-gray-400 mx-1" fill="none" viewBox="0 0 6 10" xmlns="http://www.w3.org/2000/svg"><path d="m1 9 4-4-4-4" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"/></svg>',
            link: '',
        },
    ];

    const selectedType = ref<MediaType>('image');
    const loading = ref(false);
    const uploading = ref(false);
    const deletingId = ref<number | null>(null);
    const renamingId = ref<number | null>(null);
    const renameText = ref('');
    const selectedFiles = ref<File[]>([]);
    const assets = ref<MediaAsset[]>([]);
    const currentPage = ref(1);
    const perPage = ref(24);
    const totalAssets = ref(0);
    const searchTerm = ref('');
    const appliedSearch = ref('');
    const viewMode = ref<'grid' | 'list'>('grid');
    const selectedAssetIds = ref<number[]>([]);
    const bulkDeleting = ref(false);
    const bulkMoving = ref(false);
    const showBulkMovePanel = ref(false);
    const bulkMoveTargetFolderId = ref<number | null | 'root'>('root');
    let searchDebounceTimer: ReturnType<typeof setTimeout> | null = null;
    const maxFilesPerRequest = 15;

    // Estado de carpetas
    const folders = ref<MediaFolder[]>([]);
    const currentFolderId = ref<number | null>(null);
    const folderPath = ref<{ id: number; name: string }[]>([]);
    const newFolderName = ref('');
    const showNewFolderInput = ref(false);
    const creatingFolder = ref(false);
    const renamingFolderId = ref<number | null>(null);
    const renameFolderText = ref('');
    const deletingFolderId = ref<number | null>(null);

    // Mover archivo
    const movingAssetId = ref<number | null>(null);
    const moveTargetFolderId = ref<number | null | 'root'>('root');
    const allFolders = ref<MediaFolder[]>([]);

    const currentLabel = computed(() => mediaTypes.find(t => t.value === selectedType.value)?.label || 'Archivos');
    const totalPages = computed(() => Math.max(1, Math.ceil(totalAssets.value / perPage.value)));
    const allVisibleAssetsSelected = computed(
        () => assets.value.length > 0 && assets.value.every(asset => selectedAssetIds.value.includes(asset.id)),
    );

    const hasSelectedAssets = computed(() => selectedAssetIds.value.length > 0);

    const visiblePages = computed(() => {
        const pages: number[] = [];
        const maxButtons = 5;
        const start = Math.max(1, currentPage.value - Math.floor(maxButtons / 2));
        const end = Math.min(totalPages.value, start + maxButtons - 1);
        const adjustedStart = Math.max(1, end - maxButtons + 1);

        for (let page = adjustedStart; page <= end; page += 1) {
            pages.push(page);
        }

        return pages;
    });

    const acceptByType = computed(() => {
        switch (selectedType.value) {
            case 'image': {
                return 'image/*,.zip,application/zip,application/x-zip-compressed';
            }
            case 'video': {
                return 'video/*,.zip,application/zip,application/x-zip-compressed';
            }
            case 'audio': {
                return 'audio/*,.zip,application/zip,application/x-zip-compressed';
            }
            default: {
                return '.pdf,.txt,.doc,.docx,.xls,.xlsx,.zip';
            }
        }
    });

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

    const formatDate = (dateLike?: string): string => {
        if (!dateLike) {
            return '-';
        }

        const parsed = new Date(dateLike);
        if (Number.isNaN(parsed.getTime())) {
            return '-';
        }

        return parsed.toLocaleDateString('es-CL', {
            day: '2-digit',
            month: '2-digit',
            year: 'numeric',
        });
    };

    const splitInChunks = <T>(items: T[], size: number): T[][] => {
        const chunks: T[][] = [];
        for (let index = 0; index < items.length; index += size) {
            chunks.push(items.slice(index, index + size));
        }
        return chunks;
    };

    const escapeHtml = (value: string): string =>
        value
            .replaceAll('&', '&amp;')
            .replaceAll('<', '&lt;')
            .replaceAll('>', '&gt;')
            .replaceAll('"', '&quot;')
            .replaceAll("'", '&#39;');

    const showUploadReportModal = async (
        uploadedCount: number,
        failedCount: number,
        failedItems: { name: string; message: string }[],
    ): Promise<void> => {
        const visibleItems = failedItems.slice(0, 20);
        const listHtml = visibleItems
            .map(
                item =>
                    `<li class="text-left"><strong>${escapeHtml(item.name)}</strong>: ${escapeHtml(item.message)}</li>`,
            )
            .join('');
        const moreInfo =
            failedItems.length > visibleItems.length
                ? `<p class="text-xs text-gray-500 mt-2">Y ${failedItems.length - visibleItems.length} archivo(s) más...</p>`
                : '';

        await Swal.fire({
            icon: uploadedCount > 0 ? 'warning' : 'error',
            title: uploadedCount > 0 ? 'Subida parcial' : 'Subida rechazada',
            html: `
                <div class="text-sm text-left">
                    <p class="mb-2">Subidos: <strong>${uploadedCount}</strong> · Rechazados: <strong>${failedCount}</strong></p>
                    <ul class="list-disc pl-5 space-y-1 max-h-64 overflow-auto">${listHtml || '<li>Sin detalles adicionales.</li>'}</ul>
                    ${moreInfo}
                </div>
            `,
            confirmButtonText: 'Entendido',
        });
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
            const fileInput = document.querySelector('#upload-input') as HTMLInputElement;
            if (fileInput) {
                fileInput.value = '';
            }
        }
    };

    const loadFolders = async () => {
        try {
            const parentParam =
                currentFolderId.value !== null ? `parent_folder_id=${currentFolderId.value}` : 'parent_folder_id=';
            const response: VersaFetchResponse = await versaFetch({
                url: `/${panelUrl}/mediaStore/api/folders/${empresaSelected}?${parentParam}`,
                method: 'GET',
            });

            folders.value = response.success === 1 && Array.isArray(response.data) ? response.data : [];
        } catch {
            folders.value = [];
        }
    };

    const loadAllFolders = async () => {
        try {
            const response: VersaFetchResponse = await versaFetch({
                url: `/${panelUrl}/mediaStore/api/folders/${empresaSelected}?parent_folder_id=`,
                method: 'GET',
            });

            allFolders.value = response.success === 1 && Array.isArray(response.data) ? response.data : [];
        } catch {
            allFolders.value = [];
        }
    };

    const loadAssets = async (allowPageAdjust = true) => {
        loading.value = true;
        try {
            const folderParam =
                currentFolderId.value !== null ? `folder_id=${currentFolderId.value}` : 'folder_id=root';
            const filterParam = appliedSearch.value ? `&filter=${encodeURIComponent(appliedSearch.value)}` : '';

            const response: VersaFetchResponse = await versaFetch({
                url: `/${panelUrl}/mediaStore/api/list/${empresaSelected}?per_page=${perPage.value}&page=${currentPage.value}&media_type=${selectedType.value}&${folderParam}${filterParam}`,
                method: 'GET',
            });

            if (response.success === 1 && Array.isArray(response.data)) {
                assets.value = response.data as MediaAsset[];
                const responseWithMeta = response as VersaFetchResponse & {
                    meta?: { total?: number; page?: number; per_page?: number };
                };
                const meta =
                    responseWithMeta.meta && typeof responseWithMeta.meta === 'object' ? responseWithMeta.meta : {};

                totalAssets.value = Number(meta.total ?? assets.value.length);

                const normalizedPerPage = Number(meta.per_page ?? perPage.value);
                if (Number.isFinite(normalizedPerPage) && normalizedPerPage > 0) {
                    perPage.value = normalizedPerPage;
                }

                const normalizedPage = Number(meta.page ?? currentPage.value);
                if (Number.isFinite(normalizedPage) && normalizedPage > 0) {
                    currentPage.value = normalizedPage;
                }

                const latestTotalPages = Math.max(1, Math.ceil(totalAssets.value / perPage.value));
                if (allowPageAdjust && assets.value.length === 0 && currentPage.value > 1) {
                    currentPage.value = latestTotalPages;
                    await loadAssets(false);
                }

                selectedAssetIds.value = selectedAssetIds.value.filter(selectedId =>
                    assets.value.some(asset => asset.id === selectedId),
                );
            } else {
                assets.value = [];
                totalAssets.value = 0;
                selectedAssetIds.value = [];
            }
        } catch {
            assets.value = [];
            totalAssets.value = 0;
            selectedAssetIds.value = [];
            versaAlert({
                type: 'error',
                title: 'Error al cargar biblioteca',
                message: 'No fue posible obtener los archivos.',
            });
        } finally {
            loading.value = false;
        }
    };

    const navigateToFolder = async (folder: MediaFolder) => {
        folderPath.value = [...folderPath.value, { id: folder.id, name: folder.name }];
        currentFolderId.value = folder.id;
        currentPage.value = 1;
        selectedAssetIds.value = [];
        await Promise.all([loadFolders(), loadAssets()]);
    };

    const navigateToBreadcrumb = async (index: number) => {
        if (index === -1) {
            folderPath.value = [];
            currentFolderId.value = null;
        } else {
            folderPath.value = folderPath.value.slice(0, index + 1);
            currentFolderId.value = folderPath.value[index]?.id ?? null;
        }
        currentPage.value = 1;
        selectedAssetIds.value = [];
        await Promise.all([loadFolders(), loadAssets()]);
    };

    const goToPage = async (page: number) => {
        const nextPage = Math.min(totalPages.value, Math.max(1, page));
        if (nextPage === currentPage.value) {
            return;
        }

        currentPage.value = nextPage;
        selectedAssetIds.value = [];
        await loadAssets();
    };

    const toggleSelectAsset = (assetId: number) => {
        if (selectedAssetIds.value.includes(assetId)) {
            selectedAssetIds.value = selectedAssetIds.value.filter(id => id !== assetId);
            return;
        }
        selectedAssetIds.value = [...selectedAssetIds.value, assetId];
    };

    const handleAssetContainerClick = (event: MouseEvent, assetId: number) => {
        const target = event.target as HTMLElement | null;
        const clickedInteractive = target?.closest('a, button, input, select, textarea, label');
        if (clickedInteractive) {
            return;
        }

        if (event.ctrlKey || event.metaKey) {
            toggleSelectAsset(assetId);
        }
    };

    const toggleSelectAllVisible = () => {
        if (allVisibleAssetsSelected.value) {
            selectedAssetIds.value = [];
            return;
        }

        selectedAssetIds.value = assets.value.map(asset => asset.id);
    };

    const clearSelection = () => {
        selectedAssetIds.value = [];
    };

    const applySearch = async () => {
        const normalized = searchTerm.value.trim();
        if (normalized === appliedSearch.value) {
            return;
        }

        appliedSearch.value = normalized;
        currentPage.value = 1;
        clearSelection();
        await loadAssets();
    };

    const clearSearch = async () => {
        if (!searchTerm.value && !appliedSearch.value) {
            return;
        }

        searchTerm.value = '';
        appliedSearch.value = '';
        currentPage.value = 1;
        clearSelection();
        await loadAssets();
    };

    const confirmDeleteAssets = async (total: number): Promise<boolean> => {
        const result = await Swal.fire({
            icon: 'warning',
            title: total > 1 ? 'Eliminar archivos seleccionados' : 'Eliminar archivo',
            text:
                total > 1
                    ? `Se eliminarán ${total} archivo(s). Esta acción no se puede deshacer.`
                    : 'Este archivo se eliminará de forma permanente. Esta acción no se puede deshacer.',
            showCancelButton: true,
            confirmButtonText: 'Sí, eliminar',
            cancelButtonText: 'Cancelar',
            reverseButtons: true,
        });

        return result.isConfirmed;
    };

    const deleteSelectedAssets = async () => {
        if (selectedAssetIds.value.length === 0) {
            return;
        }

        const confirmed = await confirmDeleteAssets(selectedAssetIds.value.length);
        if (!confirmed) {
            return;
        }

        bulkDeleting.value = true;

        try {
            const selectedIds = [...selectedAssetIds.value];
            const deletionResults = await Promise.all(
                selectedIds.map(async assetId => {
                    try {
                        const response: VersaFetchResponse = await versaFetch({
                            url: `/${panelUrl}/mediaStore/api/delete/${empresaSelected}`,
                            method: 'POST',
                            data: {
                                id: assetId,
                                csrf_token: csrfToken.value,
                            },
                        });

                        return response.success === 1;
                    } catch {
                        return false;
                    }
                }),
            );

            const deletedCount = deletionResults.filter(Boolean).length;
            const failedCount = deletionResults.length - deletedCount;

            clearSelection();
            await loadAssets();

            if (deletedCount > 0 && failedCount === 0) {
                versaAlert({
                    type: 'success',
                    title: 'Eliminación completada',
                    message: `Se eliminaron ${deletedCount} archivo(s).`,
                });
                return;
            }

            if (deletedCount > 0 && failedCount > 0) {
                versaAlert({
                    type: 'warning',
                    title: 'Eliminación parcial',
                    message: `Se eliminaron ${deletedCount} archivo(s) y ${failedCount} no se pudieron eliminar.`,
                });
                return;
            }

            versaAlert({
                type: 'error',
                title: 'No se pudo eliminar',
                message: 'No fue posible eliminar los archivos seleccionados.',
            });
        } finally {
            bulkDeleting.value = false;
        }
    };

    const startBulkMove = async () => {
        if (selectedAssetIds.value.length === 0) {
            return;
        }

        bulkMoveTargetFolderId.value = 'root';
        showBulkMovePanel.value = true;
        await loadAllFolders();
    };

    const cancelBulkMove = () => {
        showBulkMovePanel.value = false;
        bulkMoveTargetFolderId.value = 'root';
    };

    const confirmBulkMove = async () => {
        if (selectedAssetIds.value.length === 0 || bulkMoving.value) {
            return;
        }

        bulkMoving.value = true;
        const folderId = bulkMoveTargetFolderId.value === 'root' ? null : bulkMoveTargetFolderId.value;

        try {
            const selectedIds = [...selectedAssetIds.value];
            const moveResults = await Promise.all(
                selectedIds.map(async assetId => {
                    try {
                        const response: VersaFetchResponse = await versaFetch({
                            url: `/${panelUrl}/mediaStore/api/move/${empresaSelected}`,
                            method: 'POST',
                            data: {
                                id: assetId,
                                folder_id: folderId ?? '',
                                csrf_token: csrfToken.value,
                            },
                        });

                        return response.success === 1;
                    } catch {
                        return false;
                    }
                }),
            );

            const movedCount = moveResults.filter(Boolean).length;
            const failedCount = moveResults.length - movedCount;

            if (movedCount > 0) {
                clearSelection();
                cancelBulkMove();
                await loadAssets();
            }

            if (movedCount > 0 && failedCount === 0) {
                versaAlert({
                    type: 'success',
                    title: 'Movimiento completado',
                    message: `Se movieron ${movedCount} archivo(s).`,
                });
                return;
            }

            if (movedCount > 0 && failedCount > 0) {
                versaAlert({
                    type: 'warning',
                    title: 'Movimiento parcial',
                    message: `Se movieron ${movedCount} archivo(s) y ${failedCount} no se pudieron mover.`,
                });
                return;
            }

            versaAlert({
                type: 'error',
                title: 'No se pudo mover',
                message: 'No fue posible mover los archivos seleccionados.',
            });
        } finally {
            bulkMoving.value = false;
        }
    };

    const createFolder = async () => {
        const name = newFolderName.value.trim();
        if (!name) {
            return;
        }

        creatingFolder.value = true;
        try {
            const response: VersaFetchResponse = await versaFetch({
                url: `/${panelUrl}/mediaStore/api/folders/${empresaSelected}`,
                method: 'POST',
                data: {
                    name,
                    parent_folder_id: currentFolderId.value ?? '',
                    csrf_token: csrfToken.value,
                },
            });

            if (response.success === 1) {
                newFolderName.value = '';
                showNewFolderInput.value = false;
                await loadFolders();
                versaAlert({
                    type: 'success',
                    title: 'Carpeta creada',
                    message: `Carpeta "${name}" creada.`,
                });
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

    const startRenamingFolder = (folder: MediaFolder) => {
        renamingFolderId.value = folder.id;
        renameFolderText.value = folder.name;
    };

    const cancelRenameFolder = () => {
        renamingFolderId.value = null;
        renameFolderText.value = '';
    };

    const saveRenameFolder = async (folderId: number) => {
        const name = renameFolderText.value.trim();
        if (!name) {
            versaAlert({
                type: 'warning',
                title: 'Nombre inválido',
                message: 'El nombre no puede estar vacío.',
            });
            return;
        }

        try {
            const response: VersaFetchResponse = await versaFetch({
                url: `/${panelUrl}/mediaStore/api/folders/rename/${empresaSelected}`,
                method: 'POST',
                data: { id: folderId, new_name: name, csrf_token: csrfToken.value },
            });

            if (response.success === 1) {
                cancelRenameFolder();
                await loadFolders();
                versaAlert({
                    type: 'success',
                    title: 'Renombrado',
                    message: 'Carpeta renombrada correctamente.',
                });
            } else {
                versaAlert({
                    type: 'error',
                    title: 'Error',
                    message: response.message || 'No fue posible renombrar la carpeta.',
                });
            }
        } catch {
            versaAlert({
                type: 'error',
                title: 'Error de red',
                message: 'No fue posible renombrar la carpeta.',
            });
        }
    };

    const deleteFolder = async (folderId: number) => {
        deletingFolderId.value = folderId;
        try {
            const response: VersaFetchResponse = await versaFetch({
                url: `/${panelUrl}/mediaStore/api/folders/delete/${empresaSelected}`,
                method: 'POST',
                data: { id: folderId, csrf_token: csrfToken.value },
            });

            if (response.success === 1) {
                await loadFolders();
                versaAlert({
                    type: 'success',
                    title: 'Eliminada',
                    message: 'Carpeta eliminada correctamente.',
                });
            } else {
                versaAlert({
                    type: 'error',
                    title: 'No se pudo eliminar',
                    message: response.message || 'La carpeta no está vacía o no existe.',
                });
            }
        } catch {
            versaAlert({
                type: 'error',
                title: 'Error de red',
                message: 'No fue posible eliminar la carpeta.',
            });
        } finally {
            deletingFolderId.value = null;
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
            const chunks = splitInChunks<File>(selectedFiles.value, maxFilesPerRequest);
            const uploads = chunks.map(async chunk => {
                const formData = new FormData();
                chunk.forEach(file => {
                    formData.append('media[]', file);
                });
                formData.append('media_type', selectedType.value);
                formData.append('csrf_token', csrfToken.value);
                if (currentFolderId.value !== null) {
                    formData.append('folder_id', String(currentFolderId.value));
                }

                try {
                    const response: VersaFetchResponse = await versaFetch({
                        url: `/${panelUrl}/mediaStore/api/upload/${empresaSelected}`,
                        method: 'POST',
                        data: formData,
                    });

                    if (!Array.isArray(response.data)) {
                        return {
                            ok: 0,
                            fail: chunk.length,
                            message: response.message || 'No fue posible subir el lote de archivos.',
                            failedItems: chunk.map(file => ({
                                name: file.name,
                                message: response.message || 'Rechazado por validación del servidor.',
                            })),
                        };
                    }

                    const fileResults = response.data as {
                        success?: boolean;
                        name?: string;
                        message?: string;
                    }[];
                    const ok = fileResults.filter(fileResult => fileResult.success !== false).length;
                    const failedItems = fileResults
                        .filter(fileResult => fileResult.success === false)
                        .map(fileResult => ({
                            name: fileResult.name || 'Archivo sin nombre',
                            message: fileResult.message || 'Rechazado por validación del servidor.',
                        }));

                    return {
                        ok,
                        fail: fileResults.length - ok,
                        message: '',
                        failedItems,
                    };
                } catch {
                    return {
                        ok: 0,
                        fail: chunk.length,
                        message: 'Error de red al subir un lote de archivos.',
                        failedItems: chunk.map(file => ({
                            name: file.name,
                            message: 'Error de red al subir este archivo.',
                        })),
                    };
                }
            });

            const uploadResults = await Promise.all(uploads);
            const uploadedCount = uploadResults.reduce((total, current) => total + current.ok, 0);
            const failedCount = uploadResults.reduce((total, current) => total + current.fail, 0);
            const firstError = uploadResults.find(result => result.message)?.message;
            const failedItems = uploadResults.flatMap(result => result.failedItems || []);

            if (uploadedCount > 0) {
                selectedFiles.value = [];
                const fileInput = document.querySelector('#upload-input') as HTMLInputElement;
                if (fileInput) {
                    fileInput.value = '';
                }

                await loadAssets();
            }

            if (uploadedCount > 0 && failedCount === 0) {
                versaAlert({
                    type: 'success',
                    title: 'Archivos subidos',
                    message: `Se subieron ${uploadedCount} archivo(s) en ${chunks.length} lote(s).`,
                });
                return;
            }

            if (uploadedCount > 0 && failedCount > 0) {
                await showUploadReportModal(uploadedCount, failedCount, failedItems);
                return;
            }

            if (failedCount > 0) {
                await showUploadReportModal(0, failedCount, failedItems);
                return;
            }

            versaAlert({
                type: 'error',
                title: 'Error al subir',
                message: firstError || 'No fue posible subir los archivos.',
            });
        } catch {
            versaAlert({
                type: 'error',
                title: 'Error de red',
                message: 'Ocurrió un error al intentar subir los archivos.',
            });
        } finally {
            uploading.value = false;
        }
    };

    const deleteAsset = async (assetId: number) => {
        const confirmed = await confirmDeleteAssets(1);
        if (!confirmed) {
            return;
        }

        deletingId.value = assetId;
        try {
            const response: VersaFetchResponse = await versaFetch({
                url: `/${panelUrl}/mediaStore/api/delete/${empresaSelected}`,
                method: 'POST',
                data: {
                    id: assetId,
                    csrf_token: csrfToken.value,
                },
            });

            if (response.success === 1) {
                await loadAssets();
                selectedAssetIds.value = selectedAssetIds.value.filter(id => id !== assetId);
                return;
            }

            versaAlert({
                type: 'error',
                title: 'No se pudo eliminar',
                message: response.message || 'Error al eliminar archivo.',
            });
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

    const startRenaming = (asset: MediaAsset) => {
        renamingId.value = asset.id;
        renameText.value = asset.original_name;
    };

    const cancelRename = () => {
        renamingId.value = null;
        renameText.value = '';
    };

    const saveRename = async (assetId: number) => {
        if (!renameText.value.trim()) {
            versaAlert({
                type: 'warning',
                title: 'Nombre inválido',
                message: 'El nombre no puede estar vacío.',
            });
            return;
        }

        try {
            const response: VersaFetchResponse = await versaFetch({
                url: `/${panelUrl}/mediaStore/api/rename/${empresaSelected}`,
                method: 'POST',
                data: {
                    id: assetId,
                    new_name: renameText.value.trim(),
                    csrf_token: csrfToken.value,
                },
            });

            if (response.success === 1) {
                renamingId.value = null;
                await loadAssets();
                versaAlert({
                    type: 'success',
                    title: 'Renombrado',
                    message: 'El archivo se renombró correctamente.',
                });
                return;
            }

            versaAlert({
                type: 'error',
                title: 'Error',
                message: response.message || 'No fue posible renombrar el archivo.',
            });
        } catch {
            versaAlert({
                type: 'error',
                title: 'Error de red',
                message: 'No fue posible renombrar el archivo.',
            });
        }
    };

    const startMoving = async (assetId: number) => {
        movingAssetId.value = assetId;
        moveTargetFolderId.value = 'root';
        await loadAllFolders();
    };

    const cancelMove = () => {
        movingAssetId.value = null;
    };

    const confirmMove = async () => {
        if (movingAssetId.value === null) {
            return;
        }

        const folderId = moveTargetFolderId.value === 'root' ? null : moveTargetFolderId.value;

        try {
            const response: VersaFetchResponse = await versaFetch({
                url: `/${panelUrl}/mediaStore/api/move/${empresaSelected}`,
                method: 'POST',
                data: {
                    id: movingAssetId.value,
                    folder_id: folderId ?? '',
                    csrf_token: csrfToken.value,
                },
            });

            if (response.success === 1) {
                movingAssetId.value = null;
                await loadAssets();
                versaAlert({ type: 'success', title: 'Movido', message: 'Archivo movido correctamente.' });
            } else {
                versaAlert({
                    type: 'error',
                    title: 'Error',
                    message: response.message || 'No fue posible mover el archivo.',
                });
            }
        } catch {
            versaAlert({
                type: 'error',
                title: 'Error de red',
                message: 'No fue posible mover el archivo.',
            });
        }
    };

    watch(
        () => selectedType.value,
        () => {
            currentPage.value = 1;
            selectedAssetIds.value = [];
            loadAssets();
        },
    );

    watch(
        () => perPage.value,
        (newPerPage, oldPerPage) => {
            if (newPerPage === oldPerPage) {
                return;
            }

            currentPage.value = 1;
            clearSelection();
            loadAssets();
        },
    );

    watch(
        () => searchTerm.value,
        value => {
            if (searchDebounceTimer) {
                clearTimeout(searchDebounceTimer);
            }

            searchDebounceTimer = setTimeout(() => {
                if (value.trim() === appliedSearch.value) {
                    return;
                }

                appliedSearch.value = value.trim();
                currentPage.value = 1;
                clearSelection();
                loadAssets();
            }, 350);
        },
    );

    onMounted(async () => {
        await Promise.all([loadFolders(), loadAssets()]);
    });
</script>

<template>
    <div class="w-full p-4 md:p-6 space-y-6 animate-fade-in">
        <Breadcrumb
            title="Media Store"
            iconSVG="<svg class='w-[32px] h-[32px] text-gray-800 dark:text-white' fill='none' stroke='currentColor' viewBox='0 0 24 24' xmlns='http://www.w3.org/2000/svg'><path stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M4 16l4.586-4.586a2 2 0 0 1 2.828 0L16 16m-2-2 1.586-1.586a2 2 0 0 1 2.828 0L20 14m-6-8h.01M6 20h12a2 2 0 0 0 2-2V6a2 2 0 0 0-2-2H6a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2z'></path></svg>"
            :items="breadCrumbList" />

        <!-- Header y Filtros -->
        <div
            class="bg-white dark:bg-gray-900 rounded-2xl shadow-xs border border-gray-100 dark:border-gray-800 p-6 space-y-6">
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                <div class="flex flex-wrap items-center gap-2 p-1 bg-gray-50 dark:bg-gray-800/50 rounded-xl w-fit">
                    <button
                        v-for="type in mediaTypes"
                        :key="type.value"
                        @click="selectedType = type.value"
                        class="flex items-center gap-2 px-4 py-2 text-sm font-medium rounded-lg transition-all duration-200"
                        :class="
                            selectedType === type.value
                                ? 'bg-white dark:bg-gray-700 text-brand shadow-xs ring-1 ring-black/5 dark:ring-white/10'
                                : 'text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200'
                        ">
                        <i :class="['bi', type.icon]"></i>
                        {{ type.label }}
                    </button>
                </div>

                <!-- Upload Area -->
                <div class="flex flex-col md:flex-row items-start md:items-end gap-3 flex-1">
                    <div class="flex-1 w-full">
                        <label class="block text-xs font-bold text-gray-500 dark:text-gray-400 uppercase mb-2 ml-1">
                            Subir nuevos archivos
                        </label>
                        <div class="flex flex-wrap gap-2 mb-2" v-if="selectedFiles.length > 0">
                            <div
                                v-for="(file, index) in selectedFiles"
                                :key="index"
                                class="flex items-center gap-2 px-3 py-1 bg-brand/10 text-brand border border-brand/20 rounded-full text-xs">
                                <span class="max-w-[150px] truncate">{{ file.name }}</span>
                                <button @click="removeSelectedFile(index)" class="hover:text-red-500 transition-colors">
                                    <i class="bi bi-x-lg"></i>
                                </button>
                            </div>
                        </div>
                        <div class="relative">
                            <input
                                id="upload-input"
                                type="file"
                                multiple
                                :accept="acceptByType"
                                class="hidden"
                                @change="handleFileChange" />
                            <label
                                for="upload-input"
                                class="flex items-center gap-2 px-4 py-2.5 text-sm bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-2xl cursor-pointer hover:border-brand transition-colors truncate">
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
                        class="px-8 py-2.5 rounded-2xl text-white bg-brand font-bold hover:shadow-lg hover:shadow-brand/20 active:scale-95 transition-all disabled:opacity-50"
                        @click="uploadFile">
                        <span v-if="uploading" class="spinner-border spinner-border-sm mr-2"></span>
                        {{ uploading ? 'Subiendo' : 'Subir' }}
                    </button>
                </div>
            </div>
        </div>

        <!-- Grid de Contenido con navegación de carpetas -->
        <div class="space-y-4">
            <!-- Breadcrumb y toolbar -->
            <div class="flex items-center justify-between gap-2 px-2">
                <div class="flex items-center gap-3">
                    <h2 class="text-lg font-bold text-gray-900 dark:text-white flex items-center gap-2">
                        <i class="bi bi-grid-3x3-gap-fill text-brand"></i>
                        {{ currentLabel }}
                        <span
                            class="text-xs font-normal text-gray-500 dark:text-gray-400 bg-gray-100 dark:bg-gray-800 px-2 py-0.5 rounded-full ml-1">
                            {{ totalAssets }}
                        </span>
                    </h2>

                    <!-- Breadcrumb de carpeta -->
                    <nav class="flex items-center gap-1 text-xs text-gray-500 dark:text-gray-400">
                        <button
                            class="flex items-center gap-1 hover:text-brand transition-colors"
                            @click="navigateToBreadcrumb(-1)">
                            <i class="bi bi-house-fill text-xs"></i>
                            Raíz
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
                </div>

                <!-- Nueva carpeta -->
                <div v-if="showNewFolderInput" class="flex items-center gap-1">
                    <input
                        v-model="newFolderName"
                        type="text"
                        placeholder="Nombre carpeta"
                        class="text-sm px-3 py-1.5 rounded-xl border border-brand bg-white dark:bg-gray-900 text-gray-900 dark:text-white focus:outline-hidden w-40"
                        @keyup.enter="createFolder"
                        @keyup.esc="showNewFolderInput = false"
                        autofocus />
                    <button
                        @click="createFolder"
                        :disabled="creatingFolder || !newFolderName.trim()"
                        class="px-3 py-1.5 rounded-xl bg-brand text-white text-sm disabled:opacity-50">
                        <span v-if="creatingFolder" class="spinner-border spinner-border-sm w-3 h-3"></span>
                        <i v-else class="bi bi-check"></i>
                    </button>
                    <button
                        @click="showNewFolderInput = false"
                        class="px-3 py-1.5 rounded-xl bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300 text-sm">
                        <i class="bi bi-x"></i>
                    </button>
                </div>
                <button
                    v-else
                    @click="showNewFolderInput = true"
                    class="flex items-center gap-2 px-3 py-2 text-sm font-medium rounded-xl bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-400 hover:bg-gray-200 dark:hover:bg-gray-700 transition-colors">
                    <i class="bi bi-folder-plus"></i>
                    Nueva carpeta
                </button>
            </div>

            <div
                class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-3 bg-white dark:bg-gray-900 border border-gray-100 dark:border-gray-800 rounded-2xl px-4 py-3">
                <div class="flex flex-col sm:flex-row sm:items-center gap-2 w-full lg:max-w-2xl">
                    <div class="relative flex-1">
                        <i class="bi bi-search absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"></i>
                        <input
                            v-model="searchTerm"
                            type="text"
                            placeholder="Buscar por nombre de archivo..."
                            class="w-full pl-9 pr-9 py-2 text-sm rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-white focus:outline-hidden focus:border-brand" />
                        <button
                            v-if="searchTerm"
                            @click="clearSearch"
                            class="absolute right-2 top-1/2 -translate-y-1/2 w-6 h-6 rounded-md text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-700">
                            <i class="bi bi-x"></i>
                        </button>
                    </div>

                    <button
                        @click="applySearch"
                        class="px-3 py-2 text-xs rounded-xl border border-gray-200 dark:border-gray-700 text-gray-600 dark:text-gray-300 hover:border-brand hover:text-brand transition-colors">
                        Buscar
                    </button>

                    <select
                        v-model.number="perPage"
                        class="px-3 py-2 text-xs rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-600 dark:text-gray-300 focus:outline-hidden focus:border-brand">
                        <option :value="12">12 por página</option>
                        <option :value="24">24 por página</option>
                        <option :value="48">48 por página</option>
                        <option :value="96">96 por página</option>
                    </select>
                </div>

                <div class="flex flex-wrap items-center gap-2 justify-end">
                    <div class="flex items-center gap-1 p-1 rounded-xl bg-gray-100 dark:bg-gray-800">
                        <button
                            @click="viewMode = 'grid'"
                            :class="
                                viewMode === 'grid'
                                    ? 'bg-white dark:bg-gray-700 text-brand shadow-xs'
                                    : 'text-gray-500 dark:text-gray-400 hover:text-brand'
                            "
                            class="px-3 py-1.5 text-xs rounded-lg transition-colors">
                            <i class="bi bi-grid-3x3-gap mr-1"></i>
                            Grilla
                        </button>
                        <button
                            @click="viewMode = 'list'"
                            :class="
                                viewMode === 'list'
                                    ? 'bg-white dark:bg-gray-700 text-brand shadow-xs'
                                    : 'text-gray-500 dark:text-gray-400 hover:text-brand'
                            "
                            class="px-3 py-1.5 text-xs rounded-lg transition-colors">
                            <i class="bi bi-list-ul mr-1"></i>
                            Lista
                        </button>
                    </div>

                    <button
                        :disabled="assets.length === 0"
                        @click="toggleSelectAllVisible"
                        class="px-3 py-2 text-xs rounded-xl border border-gray-200 dark:border-gray-700 text-gray-600 dark:text-gray-300 disabled:opacity-50 disabled:cursor-not-allowed hover:border-brand hover:text-brand transition-colors">
                        {{ allVisibleAssetsSelected ? 'Deseleccionar' : 'Seleccionar página' }}
                    </button>

                    <button
                        :disabled="!hasSelectedAssets || bulkDeleting || bulkMoving"
                        @click="startBulkMove"
                        class="px-3 py-2 text-xs rounded-xl bg-amber-500 text-white disabled:opacity-50 disabled:cursor-not-allowed hover:bg-amber-600 transition-colors">
                        <span v-if="bulkMoving" class="spinner-border spinner-border-sm w-3 h-3 mr-1"></span>
                        Mover seleccionados ({{ selectedAssetIds.length }})
                    </button>

                    <button
                        :disabled="!hasSelectedAssets || bulkDeleting || bulkMoving"
                        @click="deleteSelectedAssets"
                        class="px-3 py-2 text-xs rounded-xl bg-red-500 text-white disabled:opacity-50 disabled:cursor-not-allowed hover:bg-red-600 transition-colors">
                        <span v-if="bulkDeleting" class="spinner-border spinner-border-sm w-3 h-3 mr-1"></span>
                        Eliminar seleccionados ({{ selectedAssetIds.length }})
                    </button>
                </div>
            </div>

            <div
                v-if="showBulkMovePanel"
                class="flex flex-col sm:flex-row sm:items-center gap-2 bg-amber-50 dark:bg-amber-900/10 border border-amber-200 dark:border-amber-800/30 rounded-2xl px-4 py-3">
                <p class="text-xs text-amber-700 dark:text-amber-300 font-semibold">
                    Mover {{ selectedAssetIds.length }} archivo(s) a:
                </p>
                <select
                    v-model="bulkMoveTargetFolderId"
                    class="w-full sm:w-72 text-xs px-2 py-1.5 rounded-lg border border-amber-300 dark:border-amber-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-white focus:outline-hidden">
                    <option value="root">Raíz (sin carpeta)</option>
                    <option v-for="f in allFolders" :key="`bulk-${f.id}`" :value="f.id">{{ f.name }}</option>
                </select>

                <div class="flex items-center gap-2 sm:ml-auto">
                    <button
                        @click="cancelBulkMove"
                        :disabled="bulkMoving"
                        class="px-3 py-1.5 rounded-lg text-xs border border-gray-200 dark:border-gray-700 text-gray-600 dark:text-gray-300 disabled:opacity-50">
                        Cancelar
                    </button>
                    <button
                        @click="confirmBulkMove"
                        :disabled="bulkMoving"
                        class="px-3 py-1.5 rounded-lg text-xs bg-amber-500 text-white hover:bg-amber-600 disabled:opacity-50">
                        <span v-if="bulkMoving" class="spinner-border spinner-border-sm w-3 h-3 mr-1"></span>
                        Confirmar movimiento
                    </button>
                </div>
            </div>

            <div v-if="loading" class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-5 gap-4">
                <div
                    v-for="i in 8"
                    :key="i"
                    class="aspect-square bg-gray-100 dark:bg-gray-800 rounded-2xl animate-pulse"></div>
            </div>

            <div
                v-else-if="folders.length === 0 && assets.length === 0"
                class="flex flex-col items-center justify-center py-20 bg-white dark:bg-gray-900 rounded-2xl border border-dashed border-gray-200 dark:border-gray-800">
                <div class="w-16 h-16 bg-gray-50 dark:bg-gray-800 rounded-full flex items-center justify-center mb-4">
                    <i class="bi bi-folder2-open text-3xl text-gray-300"></i>
                </div>
                <h3 class="text-sm font-medium text-gray-900 dark:text-white">Sin elementos</h3>
                <p class="text-xs text-gray-500 dark:text-gray-400">Sube tu primer archivo o crea una carpeta</p>
            </div>

            <div v-else>
                <div
                    v-if="viewMode === 'grid'"
                    class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-6">
                    <!-- Carpetas -->
                    <div
                        v-for="folder in folders"
                        :key="`folder-${folder.id}`"
                        class="group relative bg-yellow-50 dark:bg-yellow-900/10 rounded-2xl border border-yellow-100 dark:border-yellow-800/30 shadow-xs hover:shadow-xl hover:shadow-yellow-100/50 dark:hover:shadow-yellow-900/10 hover:-translate-y-1 transition-all duration-300 overflow-hidden">
                        <div
                            class="aspect-square flex flex-col items-center justify-center gap-2 p-4 cursor-pointer"
                            @click="navigateToFolder(folder)">
                            <i class="bi bi-folder-fill text-4xl text-yellow-400"></i>
                        </div>

                        <div class="p-3 border-t border-yellow-100/50 dark:border-yellow-800/20">
                            <div v-if="renamingFolderId === folder.id" class="space-y-2">
                                <input
                                    v-model="renameFolderText"
                                    type="text"
                                    class="w-full text-xs px-2 py-1.5 rounded-lg border border-brand bg-white dark:bg-gray-800 text-gray-900 dark:text-white focus:outline-hidden"
                                    @keyup.enter="saveRenameFolder(folder.id)"
                                    @keyup.esc="cancelRenameFolder"
                                    autofocus />
                                <div class="flex gap-1 justify-end">
                                    <button
                                        @click="cancelRenameFolder"
                                        class="text-[10px] text-gray-500 hover:text-gray-700 dark:hover:text-gray-300">
                                        Cancelar
                                    </button>
                                    <button
                                        @click="saveRenameFolder(folder.id)"
                                        class="text-[10px] text-brand font-bold">
                                        Guardar
                                    </button>
                                </div>
                            </div>
                            <template v-else>
                                <h3
                                    class="text-[13px] font-semibold text-gray-700 dark:text-gray-300 truncate cursor-pointer"
                                    :title="folder.name"
                                    @click="navigateToFolder(folder)">
                                    {{ folder.name }}
                                </h3>
                            </template>
                        </div>

                        <div
                            class="absolute inset-0 bg-black/30 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center gap-2"
                            @click.stop>
                            <button
                                @click="navigateToFolder(folder)"
                                title="Abrir"
                                class="w-8 h-8 flex items-center justify-center rounded-lg bg-white/20 hover:bg-white/40 text-white backdrop-blur-md transition-all">
                                <i class="bi bi-folder2-open"></i>
                            </button>
                            <button
                                @click="startRenamingFolder(folder)"
                                title="Renombrar"
                                class="w-8 h-8 flex items-center justify-center rounded-lg bg-white/20 hover:bg-white/40 text-white backdrop-blur-md transition-all">
                                <i class="bi bi-pencil"></i>
                            </button>
                            <button
                                @click="deleteFolder(folder.id)"
                                :disabled="deletingFolderId === folder.id"
                                title="Eliminar"
                                class="w-8 h-8 flex items-center justify-center rounded-lg bg-red-500/80 hover:bg-red-500 text-white backdrop-blur-md transition-all">
                                <span
                                    v-if="deletingFolderId === folder.id"
                                    class="spinner-border spinner-border-sm w-3 h-3"></span>
                                <i v-else class="bi bi-trash"></i>
                            </button>
                        </div>
                    </div>

                    <!-- Archivos -->
                    <div
                        v-for="asset in assets"
                        :key="asset.id"
                        :class="
                            selectedAssetIds.includes(asset.id)
                                ? 'ring-2 ring-brand/40 border-brand/40'
                                : 'border-gray-100 dark:border-gray-800'
                        "
                        class="group relative bg-white dark:bg-gray-900 rounded-2xl border shadow-xs hover:shadow-xl hover:shadow-black/5 dark:hover:shadow-brand/5 hover:-translate-y-1 transition-all duration-300 overflow-hidden"
                        @click="handleAssetContainerClick($event, asset.id)">
                        <div class="absolute top-2 left-2 z-20">
                            <input
                                :checked="selectedAssetIds.includes(asset.id)"
                                @change="toggleSelectAsset(asset.id)"
                                type="checkbox"
                                class="media-select-checkbox" />
                        </div>

                        <div class="aspect-square relative overflow-hidden bg-gray-50 dark:bg-gray-800/50">
                            <MediaThumbnail
                                :type="asset.media_type"
                                :url="asset.public_url"
                                :mime="asset.mime"
                                className="w-full h-full border-0 rounded-none shadow-none" />

                            <div
                                class="absolute inset-0 bg-black/40 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center gap-2 p-2">
                                <a
                                    :href="asset.public_url"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    title="Ver pantalla completa"
                                    class="w-8 h-8 flex items-center justify-center rounded-lg bg-white/20 hover:bg-white/40 text-white backdrop-blur-md transition-all">
                                    <i class="bi bi-eye"></i>
                                </a>
                                <button
                                    @click="startRenaming(asset)"
                                    title="Renombrar"
                                    class="w-8 h-8 flex items-center justify-center rounded-lg bg-white/20 hover:bg-white/40 text-white backdrop-blur-md transition-all">
                                    <i class="bi bi-pencil"></i>
                                </button>
                                <button
                                    @click="startMoving(asset.id)"
                                    title="Mover a carpeta"
                                    class="w-8 h-8 flex items-center justify-center rounded-lg bg-white/20 hover:bg-white/40 text-white backdrop-blur-md transition-all">
                                    <i class="bi bi-folder-symlink"></i>
                                </button>
                                <button
                                    v-if="deletingId !== asset.id"
                                    @click="deleteAsset(asset.id)"
                                    title="Eliminar"
                                    class="w-8 h-8 flex items-center justify-center rounded-lg bg-red-500/80 hover:bg-red-500 text-white backdrop-blur-md transition-all">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </div>
                        </div>

                        <div class="p-3">
                            <div v-if="renamingId === asset.id" class="space-y-2">
                                <input
                                    v-model="renameText"
                                    type="text"
                                    class="w-full text-xs px-2 py-1.5 rounded-lg border border-brand bg-white dark:bg-gray-800 text-gray-900 dark:text-white focus:outline-hidden"
                                    @keyup.enter="saveRename(asset.id)"
                                    @keyup.esc="cancelRename"
                                    autofocus />
                                <div class="flex gap-1 justify-end">
                                    <button
                                        @click="cancelRename"
                                        class="text-[10px] text-gray-500 hover:text-gray-700 dark:hover:text-gray-300">
                                        Cancelar
                                    </button>
                                    <button @click="saveRename(asset.id)" class="text-[10px] text-brand font-bold">
                                        Guardar
                                    </button>
                                </div>
                            </div>

                            <div v-else-if="movingAssetId === asset.id" class="space-y-2">
                                <select
                                    v-model="moveTargetFolderId"
                                    class="w-full text-xs px-2 py-1.5 rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-white focus:outline-hidden">
                                    <option value="root">Raíz (sin carpeta)</option>
                                    <option v-for="f in allFolders" :key="f.id" :value="f.id">{{ f.name }}</option>
                                </select>
                                <div class="flex gap-1 justify-end">
                                    <button
                                        @click="cancelMove"
                                        class="text-[10px] text-gray-500 hover:text-gray-700 dark:hover:text-gray-300">
                                        Cancelar
                                    </button>
                                    <button @click="confirmMove" class="text-[10px] text-brand font-bold">Mover</button>
                                </div>
                            </div>

                            <template v-else>
                                <h3
                                    class="text-[13px] font-semibold text-gray-900 dark:text-white truncate mb-0.5"
                                    :title="asset.original_name">
                                    {{ asset.original_name }}
                                </h3>
                                <div class="flex items-center justify-between">
                                    <span class="text-[10px] text-gray-400 font-medium">
                                        {{ formatBytes(asset.size_bytes) }}
                                    </span>
                                    <span
                                        class="text-[10px] text-gray-400 capitalize bg-gray-50 dark:bg-gray-800/50 px-1.5 py-0.5 rounded-md border border-gray-100 dark:border-gray-800">
                                        {{ asset.mime.split('/')[1] || asset.media_type }}
                                    </span>
                                </div>
                            </template>
                        </div>
                    </div>
                </div>

                <div v-else class="space-y-3">
                    <div
                        v-for="folder in folders"
                        :key="`list-folder-${folder.id}`"
                        class="flex items-center gap-3 bg-yellow-50 dark:bg-yellow-900/10 border border-yellow-100 dark:border-yellow-800/30 rounded-xl px-3 py-2">
                        <div
                            class="w-10 h-10 rounded-lg flex items-center justify-center bg-yellow-100 dark:bg-yellow-900/20">
                            <i class="bi bi-folder-fill text-yellow-500 text-xl"></i>
                        </div>

                        <div class="min-w-0 flex-1">
                            <div v-if="renamingFolderId === folder.id" class="flex items-center gap-2">
                                <input
                                    v-model="renameFolderText"
                                    type="text"
                                    class="w-full text-xs px-2 py-1.5 rounded-lg border border-brand bg-white dark:bg-gray-800 text-gray-900 dark:text-white focus:outline-hidden"
                                    @keyup.enter="saveRenameFolder(folder.id)"
                                    @keyup.esc="cancelRenameFolder"
                                    autofocus />
                                <button @click="saveRenameFolder(folder.id)" class="text-xs text-brand font-bold">
                                    Guardar
                                </button>
                            </div>
                            <button
                                v-else
                                class="text-sm font-semibold text-left text-gray-800 dark:text-gray-200 hover:text-brand truncate"
                                @click="navigateToFolder(folder)">
                                {{ folder.name }}
                            </button>
                        </div>

                        <div class="flex items-center gap-1">
                            <button
                                @click="navigateToFolder(folder)"
                                class="w-8 h-8 rounded-lg text-gray-500 dark:text-gray-300 hover:bg-white dark:hover:bg-gray-800">
                                <i class="bi bi-folder2-open"></i>
                            </button>
                            <button
                                @click="startRenamingFolder(folder)"
                                class="w-8 h-8 rounded-lg text-gray-500 dark:text-gray-300 hover:bg-white dark:hover:bg-gray-800">
                                <i class="bi bi-pencil"></i>
                            </button>
                            <button
                                @click="deleteFolder(folder.id)"
                                :disabled="deletingFolderId === folder.id"
                                class="w-8 h-8 rounded-lg text-red-500 hover:bg-red-500/10 disabled:opacity-50">
                                <i class="bi bi-trash"></i>
                            </button>
                        </div>
                    </div>

                    <div
                        v-for="asset in assets"
                        :key="`list-asset-${asset.id}`"
                        :class="
                            selectedAssetIds.includes(asset.id)
                                ? 'border-brand/40 ring-1 ring-brand/30'
                                : 'border-gray-200 dark:border-gray-800'
                        "
                        class="grid grid-cols-[auto_auto_1fr_auto_auto_auto] items-center gap-3 bg-white dark:bg-gray-900 border rounded-xl px-3 py-2"
                        @click="handleAssetContainerClick($event, asset.id)">
                        <input
                            :checked="selectedAssetIds.includes(asset.id)"
                            @change="toggleSelectAsset(asset.id)"
                            type="checkbox"
                            class="media-select-checkbox" />

                        <MediaThumbnail
                            :type="asset.media_type"
                            :url="asset.public_url"
                            :mime="asset.mime"
                            className="w-12 h-12 border border-gray-200 dark:border-gray-700" />

                        <div class="min-w-0">
                            <div v-if="renamingId === asset.id" class="flex items-center gap-2">
                                <input
                                    v-model="renameText"
                                    type="text"
                                    class="w-full text-xs px-2 py-1.5 rounded-lg border border-brand bg-white dark:bg-gray-800 text-gray-900 dark:text-white focus:outline-hidden"
                                    @keyup.enter="saveRename(asset.id)"
                                    @keyup.esc="cancelRename"
                                    autofocus />
                                <button @click="saveRename(asset.id)" class="text-xs text-brand font-bold">
                                    Guardar
                                </button>
                            </div>

                            <div v-else-if="movingAssetId === asset.id" class="flex items-center gap-2">
                                <select
                                    v-model="moveTargetFolderId"
                                    class="w-full text-xs px-2 py-1.5 rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-white focus:outline-hidden">
                                    <option value="root">Raíz (sin carpeta)</option>
                                    <option v-for="f in allFolders" :key="f.id" :value="f.id">{{ f.name }}</option>
                                </select>
                                <button @click="confirmMove" class="text-xs text-brand font-bold">Mover</button>
                            </div>

                            <template v-else>
                                <p class="text-sm font-semibold text-gray-900 dark:text-white truncate">
                                    {{ asset.original_name }}
                                </p>
                                <p class="text-[11px] text-gray-500 dark:text-gray-400">
                                    {{ asset.mime }} · {{ formatBytes(asset.size_bytes) }}
                                </p>
                            </template>
                        </div>

                        <span class="hidden md:block text-[11px] text-gray-500 dark:text-gray-400 whitespace-nowrap">
                            {{ formatDate(asset.created_at) }}
                        </span>

                        <a
                            :href="asset.public_url"
                            target="_blank"
                            rel="noopener noreferrer"
                            class="w-8 h-8 rounded-lg text-gray-500 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800 flex items-center justify-center">
                            <i class="bi bi-eye"></i>
                        </a>

                        <div class="flex items-center gap-1">
                            <button
                                @click="startRenaming(asset)"
                                class="w-8 h-8 rounded-lg text-gray-500 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800">
                                <i class="bi bi-pencil"></i>
                            </button>
                            <button
                                @click="startMoving(asset.id)"
                                class="w-8 h-8 rounded-lg text-gray-500 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800">
                                <i class="bi bi-folder-symlink"></i>
                            </button>
                            <button
                                @click="deleteAsset(asset.id)"
                                class="w-8 h-8 rounded-lg text-red-500 hover:bg-red-500/10">
                                <i class="bi bi-trash"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <div
                v-if="!loading && totalPages > 1"
                class="flex flex-col md:flex-row md:items-center md:justify-between gap-3 bg-white dark:bg-gray-900 border border-gray-100 dark:border-gray-800 rounded-2xl px-4 py-3">
                <p class="text-xs text-gray-500 dark:text-gray-400">
                    Página {{ currentPage }} de {{ totalPages }} · {{ totalAssets }} elementos
                </p>

                <div class="flex items-center gap-1 self-end md:self-auto">
                    <button
                        :disabled="currentPage === 1"
                        class="px-3 py-1.5 text-xs rounded-lg border border-gray-200 dark:border-gray-700 text-gray-600 dark:text-gray-300 disabled:opacity-50 disabled:cursor-not-allowed hover:border-brand hover:text-brand transition-colors"
                        @click="goToPage(currentPage - 1)">
                        Anterior
                    </button>

                    <button
                        v-for="pageNumber in visiblePages"
                        :key="pageNumber"
                        :class="
                            pageNumber === currentPage
                                ? 'bg-brand text-white border-brand'
                                : 'bg-transparent text-gray-600 dark:text-gray-300 border-gray-200 dark:border-gray-700 hover:border-brand hover:text-brand'
                        "
                        class="min-w-9 h-9 px-2 text-xs rounded-lg border font-semibold transition-colors"
                        @click="goToPage(pageNumber)">
                        {{ pageNumber }}
                    </button>

                    <button
                        :disabled="currentPage === totalPages"
                        class="px-3 py-1.5 text-xs rounded-lg border border-gray-200 dark:border-gray-700 text-gray-600 dark:text-gray-300 disabled:opacity-50 disabled:cursor-not-allowed hover:border-brand hover:text-brand transition-colors"
                        @click="goToPage(currentPage + 1)">
                        Siguiente
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
    .animate-fade-in {
        animation: fadeIn 0.3s ease-out;
    }

    .media-select-checkbox {
        appearance: none;
        -webkit-appearance: none;
        width: 1.25rem;
        height: 1.25rem;
        border-radius: 9999px;
        border: 2px solid #cbd5e1;
        background-color: #ffffff;
        cursor: pointer;
        transition:
            border-color 0.2s ease,
            box-shadow 0.2s ease,
            background-color 0.2s ease;
        display: inline-block;
    }

    .media-select-checkbox:checked {
        border-color: #f97316;
        background-image: radial-gradient(circle at center, #f97316 0 45%, transparent 46%);
        background-color: #fff7ed;
    }

    .media-select-checkbox:focus-visible {
        outline: none;
        box-shadow: 0 0 0 3px rgba(249, 115, 22, 0.25);
    }

    :global(.dark) .media-select-checkbox {
        border-color: #64748b;
        background-color: #0f172a;
    }

    :global(.dark) .media-select-checkbox:checked {
        border-color: #fb923c;
        background-image: radial-gradient(circle at center, #fb923c 0 45%, transparent 46%);
        background-color: #1e293b;
    }

    @keyframes fadeIn {
        from {
            opacity: 0;
            transform: translateY(10px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
</style>
