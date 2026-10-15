import { inject, ref, type Ref } from 'vue';

import { API_RESPONSE_CODES } from '@/dashboard/js/constants';
import { getPanelUrl, versaFetch } from '@/dashboard/js/functions';

interface ExportPrepareResult {
    token: string;
    filename: string;
    size_bytes: number;
}

export interface QueryResult {
    // Error: la base rechazó la consulta (el mensaje viene en message)
    type: 'rows' | 'ack' | 'error';
    columns: string[];
    rows: Record<string, unknown>[];
    total: number;
    truncated: boolean;
    message?: string;
}

export function useDatabaseBackup(): {
    exporting: Ref<boolean>;
    importing: Ref<boolean>;
    running: Ref<boolean>;
    exportDatabase: () => Promise<{ success: boolean; message?: string }>;
    importDatabase: (file: File) => Promise<{ success: boolean; message?: string }>;
    runQuery: (query: string) => Promise<{ success: boolean; message?: string; data?: QueryResult }>;
} {
    const panelUrl = inject<string>('panelUrl', '') || getPanelUrl(),
        csrfToken = inject<Ref<string>>('csrf_token', ref('')),
        apiBase = `/${panelUrl}/configuracion/database`,
        exporting = ref(false),
        importing = ref(false),
        running = ref(false);

    async function exportDatabase(): Promise<{ success: boolean; message?: string }> {
        exporting.value = true;
        try {
            const res = await versaFetch({
                url: `${apiBase}/export/prepare`,
                method: 'POST',
                data: { csrf_token: csrfToken.value },
            });

            if (res.success !== API_RESPONSE_CODES.SUCCESS) {
                return { success: false, message: (res as unknown as { message?: string }).message };
            }

            const data = res.data as ExportPrepareResult;
            window.open(`${apiBase}/export/${data.token}/download`, '_blank');

            return { success: true };
        } finally {
            exporting.value = false;
        }
    }

    async function importDatabase(file: File): Promise<{ success: boolean; message?: string }> {
        importing.value = true;
        try {
            const formData = new FormData();
            formData.append('dump', file);
            formData.append('csrf_token', csrfToken.value);

            const res = await versaFetch({
                url: `${apiBase}/import`,
                method: 'POST',
                data: formData,
            });

            return {
                success: res.success === API_RESPONSE_CODES.SUCCESS,
                message: (res as unknown as { message?: string }).message,
            };
        } finally {
            importing.value = false;
        }
    }

    async function runQuery(query: string): Promise<{ success: boolean; message?: string; data?: QueryResult }> {
        running.value = true;
        try {
            const res = await versaFetch({
                url: `${apiBase}/query`,
                method: 'POST',
                data: { query, csrf_token: csrfToken.value },
            });

            return {
                success: res.success === API_RESPONSE_CODES.SUCCESS,
                message: (res as unknown as { message?: string }).message,
                data: res.data as QueryResult | undefined,
            };
        } finally {
            running.value = false;
        }
    }

    return { exporting, importing, running, exportDatabase, importDatabase, runQuery };
}

export default useDatabaseBackup;
