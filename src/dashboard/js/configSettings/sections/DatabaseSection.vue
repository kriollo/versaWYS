<script setup lang="ts">
    import Swal from 'sweetalert2';
    import { ref } from 'vue';

    import { useDatabaseBackup } from '@/dashboard/js/configSettings/composables/useDatabaseBackup';
    import { versaAlert } from '@/dashboard/js/functions';

    const CONFIRM_PHRASE = 'RESTAURAR',
        { exporting, importing, exportDatabase, importDatabase } = useDatabaseBackup(),
        fileInput = ref<HTMLInputElement | null>(null),
        selectedFile = ref<File | null>(null);

    function onFileChange(e: Event): void {
        const input = e.target as HTMLInputElement;
        selectedFile.value = input.files?.[0] ?? null;
    }

    async function handleExport(): Promise<void> {
        const result = await exportDatabase();
        if (!result.success) {
            await versaAlert({
                title: 'Error',
                message: result.message ?? 'No se pudo generar el respaldo de la base de datos.',
                type: 'error',
            });
        }
    }

    async function handleImport(): Promise<void> {
        if (!selectedFile.value) {
            return;
        }

        const { value: typed } = await Swal.fire({
            title: 'Esta acción es irreversible',
            html: `Vas a <strong>reemplazar por completo</strong> los datos actuales de la base de datos con el
                contenido de <strong>${selectedFile.value.name}</strong>.<br><br>
                Escribe <strong>${CONFIRM_PHRASE}</strong> para confirmar.`,
            icon: 'warning',
            input: 'text',
            inputPlaceholder: CONFIRM_PHRASE,
            showCancelButton: true,
            confirmButtonText: 'Restaurar base de datos',
            cancelButtonText: 'Cancelar',
            confirmButtonColor: '#dc2626',
            inputValidator: value =>
                value === CONFIRM_PHRASE ? undefined : `Debes escribir "${CONFIRM_PHRASE}" para continuar`,
        });

        if (typed !== CONFIRM_PHRASE) {
            return;
        }

        const result = await importDatabase(selectedFile.value);

        selectedFile.value = null;
        if (fileInput.value) {
            fileInput.value.value = '';
        }

        if (!result.success) {
            await versaAlert({
                title: 'Error',
                message: result.message ?? 'No se pudo restaurar la base de datos.',
                type: 'error',
            });

            return;
        }

        await versaAlert({
            title: 'Éxito',
            message: result.message ?? 'Base de datos restaurada correctamente.',
            type: 'success',
        });
    }
</script>

<template>
    <div class="space-y-8">
        <!-- Exportar -->
        <div class="space-y-2 max-w-md">
            <h3 class="text-sm font-medium text-gray-900 dark:text-white">Exportar base de datos</h3>
            <p class="text-sm text-gray-500 dark:text-gray-400">
                Genera y descarga un respaldo completo (.sql) de la base de datos actual. Útil para clonar datos a un
                entorno de desarrollo local.
            </p>
            <button
                type="button"
                :disabled="exporting"
                class="text-white bg-brand hover:bg-brand/90 focus:ring-4 focus:outline-none focus:ring-brand/30 font-medium rounded-lg text-sm px-5 py-2.5 text-center inline-flex items-center gap-2 disabled:opacity-50"
                @click="handleExport">
                <div
                    v-if="exporting"
                    class="w-4 h-4 border-2 border-white border-t-transparent rounded-full animate-spin" />
                {{ exporting ? 'Generando respaldo...' : 'Exportar base de datos' }}
            </button>
        </div>

        <hr class="border-gray-200 dark:border-gray-700" />

        <!-- Importar -->
        <div class="space-y-2 max-w-md">
            <h3 class="text-sm font-medium text-gray-900 dark:text-white">Importar base de datos</h3>
            <p class="text-sm text-red-600 dark:text-red-400">
                Acción destructiva: reemplaza por completo los datos actuales de la base de datos por los del archivo
                subido. No se puede deshacer.
            </p>
            <input
                ref="fileInput"
                type="file"
                accept=".sql"
                class="block w-full text-sm text-gray-900 border border-gray-300 rounded-lg cursor-pointer bg-gray-50 dark:text-gray-400 focus:outline-none dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400"
                @change="onFileChange" />
            <button
                type="button"
                :disabled="!selectedFile || importing"
                class="text-white bg-red-600 hover:bg-red-700 focus:ring-4 focus:outline-none focus:ring-red-300 font-medium rounded-lg text-sm px-5 py-2.5 text-center inline-flex items-center gap-2 disabled:opacity-50 dark:focus:ring-red-800"
                @click="handleImport">
                <div
                    v-if="importing"
                    class="w-4 h-4 border-2 border-white border-t-transparent rounded-full animate-spin" />
                {{ importing ? 'Restaurando...' : 'Restaurar base de datos' }}
            </button>
        </div>
    </div>
</template>
