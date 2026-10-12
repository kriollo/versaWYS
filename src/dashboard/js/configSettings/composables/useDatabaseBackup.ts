import { inject, ref, type Ref } from 'vue';

import { API_RESPONSE_CODES } from '@/dashboard/js/constants';
import { getPanelUrl, versaFetch } from '@/dashboard/js/functions';

interface ExportPrepareResult {
    token: string;
    filename: string;
    size_bytes: number;
}

export function useDatabaseBackup(): {
    exporting: Ref<boolean>;
    importing: Ref<boolean>;
    exportDatabase: () => Promise<{ success: boolean; message?: string }>;
    importDatabase: (file: File) => Promise<{ success: boolean; message?: string }>;
} {
    const panelUrl = inject<string>('panelUrl', '') || getPanelUrl(),
        csrfToken = inject<Ref<string>>('csrf_token', ref('')),
        apiBase = `/${panelUrl}/configuracion/database`,
        exporting = ref(false),
        importing = ref(false);

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

    return { exporting, importing, exportDatabase, importDatabase };
}

export default useDatabaseBackup;
