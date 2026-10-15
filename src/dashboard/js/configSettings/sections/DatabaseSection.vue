<script setup lang="ts">
    import Swal from 'sweetalert2';
    import { ref } from 'vue';

    import { type QueryResult, useDatabaseBackup } from '@/dashboard/js/configSettings/composables/useDatabaseBackup';
    import { versaAlert } from '@/dashboard/js/functions';

    const CONFIRM_PHRASE = 'RESTAURAR',
        // En cualquier parte de la consulta: 'SELECT 1; DROP TABLE x' también pide confirmación
        DESTRUCTIVE_QUERY_PATTERN = /\b(UPDATE|DELETE|DROP|TRUNCATE|ALTER|INSERT|CREATE|GRANT|REVOKE)\b/i,
        { exporting, importing, running, exportDatabase, importDatabase, runQuery } = useDatabaseBackup(),
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

    const queryText = ref(''),
        queryResult = ref<QueryResult | null>(null),
        queryError = ref('');

    function formatCell(value: unknown): string {
        if (value === null || value === undefined) {
            return 'NULL';
        }
        if (typeof value === 'object') {
            return JSON.stringify(value);
        }

        return String(value);
    }

    async function handleRunQuery(): Promise<void> {
        const sql = queryText.value.trim();
        if (sql === '') {
            return;
        }

        if (DESTRUCTIVE_QUERY_PATTERN.test(sql)) {
            const { isConfirmed } = await Swal.fire({
                title: '¿Ejecutar esta consulta?',
                html: 'Esta consulta <strong>modifica datos</strong> y no se puede deshacer.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Ejecutar',
                cancelButtonText: 'Cancelar',
                confirmButtonColor: '#dc2626',
            });

            if (!isConfirmed) {
                return;
            }
        }

        queryError.value = '';
        queryResult.value = null;

        const result = await runQuery(sql);

        if (!result.success || !result.data || result.data.type === 'error') {
            queryError.value = result.data?.message ?? result.message ?? 'Error al ejecutar la consulta.';

            return;
        }

        queryResult.value = result.data;
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
                class="text-white bg-brand hover:bg-brand/90 focus:ring-4 focus:outline-hidden focus:ring-brand/30 font-medium rounded-lg text-sm px-5 py-2.5 text-center inline-flex items-center gap-2 disabled:opacity-50"
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
                class="block w-full text-sm text-gray-900 border border-gray-300 rounded-lg cursor-pointer bg-gray-50 dark:text-gray-400 focus:outline-hidden dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400"
                @change="onFileChange" />
            <button
                type="button"
                :disabled="!selectedFile || importing"
                class="text-white bg-red-600 hover:bg-red-700 focus:ring-4 focus:outline-hidden focus:ring-red-300 font-medium rounded-lg text-sm px-5 py-2.5 text-center inline-flex items-center gap-2 disabled:opacity-50 dark:focus:ring-red-800"
                @click="handleImport">
                <div
                    v-if="importing"
                    class="w-4 h-4 border-2 border-white border-t-transparent rounded-full animate-spin" />
                {{ importing ? 'Restaurando...' : 'Restaurar base de datos' }}
            </button>
        </div>

        <hr class="border-gray-200 dark:border-gray-700" />

        <!-- Consulta SQL -->
        <div class="space-y-2">
            <h3 class="text-sm font-medium text-gray-900 dark:text-white">Ejecutar consulta SQL</h3>
            <p class="text-sm text-gray-500 dark:text-gray-400">
                Ejecuta una consulta directa (SELECT, UPDATE, DELETE, etc.) contra la base de datos actual.
            </p>
            <textarea
                v-model="queryText"
                rows="5"
                placeholder="SELECT * FROM versa_users LIMIT 10;"
                class="block w-full font-mono text-sm text-gray-900 border border-gray-300 rounded-lg bg-gray-50 p-2.5 focus:ring-brand/30 focus:border-brand dark:bg-gray-700 dark:border-gray-600 dark:text-white dark:placeholder-gray-400" />
            <button
                type="button"
                :disabled="!queryText.trim() || running"
                class="text-white bg-gray-700 hover:bg-gray-800 focus:ring-4 focus:outline-hidden focus:ring-gray-300 font-medium rounded-lg text-sm px-5 py-2.5 text-center inline-flex items-center gap-2 disabled:opacity-50 dark:bg-gray-600 dark:hover:bg-gray-700 dark:focus:ring-gray-800"
                @click="handleRunQuery">
                <div
                    v-if="running"
                    class="w-4 h-4 border-2 border-white border-t-transparent rounded-full animate-spin" />
                {{ running ? 'Ejecutando...' : 'Ejecutar' }}
            </button>

            <!-- Error -->
            <div
                v-if="queryError"
                class="text-sm text-red-700 bg-red-50 border border-red-200 rounded-lg p-3 dark:bg-red-900/20 dark:border-red-800 dark:text-red-400">
                {{ queryError }}
            </div>

            <!-- Resultado: ack (no-select) -->
            <div
                v-if="queryResult && queryResult.type === 'ack'"
                class="text-sm text-green-700 bg-green-50 border border-green-200 rounded-lg p-3 dark:bg-green-900/20 dark:border-green-800 dark:text-green-400">
                {{ queryResult.message }}
            </div>

            <!-- Resultado: filas -->
            <div v-else-if="queryResult && queryResult.type === 'rows'" class="space-y-2">
                <p class="text-xs text-gray-500 dark:text-gray-400">
                    {{ queryResult.total }} fila{{ queryResult.total !== 1 ? 's' : '' }}
                    <template v-if="queryResult.truncated">
                        (mostrando las primeras {{ queryResult.rows.length }})
                    </template>
                </p>
                <div
                    v-if="queryResult.rows.length > 0"
                    class="overflow-x-auto border border-gray-200 dark:border-gray-700 rounded-lg max-h-96">
                    <table class="w-full text-xs text-left">
                        <thead class="bg-gray-50 dark:bg-gray-700/50 sticky top-0">
                            <tr>
                                <th
                                    v-for="col in queryResult.columns"
                                    :key="col"
                                    class="px-3 py-2 font-semibold text-gray-500 dark:text-gray-400 whitespace-nowrap">
                                    {{ col }}
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                            <tr v-for="(row, idx) in queryResult.rows" :key="idx">
                                <td
                                    v-for="col in queryResult.columns"
                                    :key="col"
                                    class="px-3 py-2 text-gray-700 dark:text-gray-300 whitespace-nowrap">
                                    {{ formatCell(row[col]) }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</template>
