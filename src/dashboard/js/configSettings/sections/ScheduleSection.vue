<script setup lang="ts">
    import { inject, onMounted, ref, type Ref } from 'vue';

    import { API_RESPONSE_CODES } from '@/dashboard/js/constants';
    import { getPanelUrl, versaAlert, versaFetch } from '@/dashboard/js/functions';

    interface LastRun {
        started_at: string;
        ms: number;
        exit: number;
        error: string | null;
    }

    interface ScheduledTask {
        id: string;
        task: string;
        type: 'cron' | 'command';
        expression: string;
        frequency: string;
        overlaps: boolean;
        next_run: string | null;
        last_run: LastRun | null;
    }

    interface ScheduleReport {
        success: number;
        tasks: ScheduledTask[];
        last_tick: string | null;
        cron_running: boolean;
        cron_line: string;
    }

    const panelUrl = inject<string>('panelUrl', '') || getPanelUrl(),
        csrfToken = inject<Ref<string>>('csrf_token', ref('')),
        report = ref<ScheduleReport | null>(null),
        loading = ref(false),
        launching = ref<string | null>(null);

    async function load(): Promise<void> {
        loading.value = true;
        try {
            const res = (await versaFetch({
                url: `/${panelUrl}/configuracion/tareas`,
                method: 'GET',
            })) as unknown as ScheduleReport;
            report.value = res.success === API_RESPONSE_CODES.SUCCESS ? res : null;
        } finally {
            loading.value = false;
        }
    }

    async function runNow(task: ScheduledTask): Promise<void> {
        launching.value = task.id;
        try {
            const res = (await versaFetch({
                url: `/${panelUrl}/configuracion/tareas/ejecutar`,
                method: 'POST',
                data: { id: task.id, csrf_token: csrfToken.value },
            })) as unknown as { success: number; message?: string };

            const ok = res.success === API_RESPONSE_CODES.SUCCESS;
            await versaAlert({
                title: ok ? 'Tarea lanzada' : 'Error',
                message: res.message ?? (ok ? 'La tarea se está ejecutando.' : 'No se pudo lanzar la tarea.'),
                type: ok ? 'success' : 'error',
            });
            // Corre en segundo plano: en unos segundos ya aparece su resultado
            if (ok) {
                setTimeout(load, 3000);
            }
        } finally {
            launching.value = null;
        }
    }

    // Nombre corto: app\cron\LimpiarTemporales → LimpiarTemporales; los comandos se muestran completos
    function shortName(task: ScheduledTask): string {
        return task.type === 'cron' ? (task.task.split('\\').pop() ?? task.task) : task.task.replace(/^versaCLI /, '');
    }

    async function copyCronLine(): Promise<void> {
        if (report.value) {
            await navigator.clipboard.writeText(report.value.cron_line);
        }
    }

    onMounted(load);
</script>

<template>
    <div class="space-y-6">
        <div class="flex items-start justify-between gap-4">
            <p class="text-sm text-gray-500 dark:text-gray-400">
                Tareas definidas en
                <code class="text-xs">app/schedule.php</code>
                . El servidor las ejecuta con una sola línea en el crontab.
            </p>
            <button
                type="button"
                :disabled="loading"
                class="text-gray-900 bg-white border border-gray-300 hover:bg-gray-100 font-medium rounded-lg text-sm px-4 py-2 disabled:opacity-50 dark:bg-gray-800 dark:text-white dark:border-gray-600 dark:hover:bg-gray-700"
                @click="load">
                Actualizar
            </button>
        </div>

        <template v-if="report">
            <!-- ¿El crontab llama al programador? Sin eso, ninguna tarea corre -->
            <div
                v-if="report.cron_running"
                class="p-3 text-sm rounded-lg bg-green-50 text-green-800 dark:bg-gray-800 dark:text-green-400">
                El programador está activo: corrió por última vez el {{ report.last_tick }}.
            </div>
            <div
                v-else
                class="p-3 text-sm rounded-lg bg-yellow-50 text-yellow-800 dark:bg-gray-800 dark:text-yellow-300">
                <p class="font-medium">
                    El crontab no está ejecutando el programador{{
                        report.last_tick ? ` (última vez: ${report.last_tick})` : ''
                    }}: ninguna tarea va a correr sola.
                </p>
                <p class="mt-1">
                    Agrega esta línea en el crontab del servidor (en cPanel: «Trabajos de cron», cada minuto):
                </p>
                <div class="flex items-center gap-2 mt-2">
                    <code class="block flex-1 p-2 text-xs rounded bg-white/70 dark:bg-gray-900 break-all">
                        {{ report.cron_line }}
                    </code>
                    <button type="button" class="text-xs underline" @click="copyCronLine">Copiar</button>
                </div>
            </div>

            <p v-if="report.tasks.length === 0" class="text-sm text-gray-500 dark:text-gray-400">
                No hay tareas programadas. Se agregan en
                <code class="text-xs">app/schedule.php</code>
                (ver documentacion/Tareas_Programadas.md).
            </p>

            <template v-else>
                <!-- Pantallas anchas: tabla -->
                <div class="hidden md:block overflow-x-auto rounded-lg border border-gray-200 dark:border-gray-700">
                    <table class="w-full text-sm text-left text-gray-600 dark:text-gray-300">
                        <thead class="text-xs uppercase text-gray-700 bg-gray-50 dark:bg-gray-700 dark:text-gray-300">
                            <tr>
                                <th class="px-4 py-3">Tarea</th>
                                <th class="px-4 py-3">Frecuencia</th>
                                <th class="px-4 py-3">Próxima</th>
                                <th class="px-4 py-3">Última ejecución</th>
                                <th class="px-4 py-3 text-right">Acción</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr
                                v-for="task in report.tasks"
                                :key="task.id"
                                class="border-t border-gray-200 dark:border-gray-700 align-top">
                                <td class="px-4 py-3">
                                    <div class="font-medium text-gray-900 dark:text-white">
                                        {{ shortName(task) }}
                                        <span
                                            v-if="!task.overlaps"
                                            class="ml-1 text-xs font-normal px-1.5 py-0.5 rounded bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-300"
                                            title="Si la ejecución anterior sigue corriendo, esta vuelta se omite">
                                            sin solapar
                                        </span>
                                    </div>
                                    <div class="font-mono text-xs text-gray-500 dark:text-gray-400 break-all">
                                        {{ task.task }}
                                    </div>
                                </td>
                                <td class="px-4 py-3">
                                    <div class="text-gray-900 dark:text-white whitespace-nowrap">
                                        {{ task.frequency }}
                                    </div>
                                    <div class="font-mono text-xs text-gray-500 dark:text-gray-400 whitespace-nowrap">
                                        {{ task.expression }}
                                    </div>
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap">{{ task.next_run ?? '—' }}</td>
                                <td class="px-4 py-3">
                                    <template v-if="task.last_run">
                                        <div class="flex items-center gap-2 whitespace-nowrap">
                                            <span
                                                :class="
                                                    task.last_run.exit === 0
                                                        ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300'
                                                        : 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300'
                                                "
                                                class="text-xs font-medium px-2 py-0.5 rounded">
                                                {{ task.last_run.exit === 0 ? 'OK' : `Error ${task.last_run.exit}` }}
                                            </span>
                                            <span>{{ task.last_run.started_at }}</span>
                                            <span class="text-xs text-gray-500">{{ task.last_run.ms }} ms</span>
                                        </div>
                                        <p
                                            v-if="task.last_run.error"
                                            class="mt-1 text-xs text-red-700 dark:text-red-400 break-words">
                                            {{ task.last_run.error }}
                                        </p>
                                    </template>
                                    <span v-else class="text-gray-400">Todavía no corre</span>
                                </td>
                                <td class="px-4 py-3 text-right">
                                    <button
                                        type="button"
                                        :disabled="launching === task.id"
                                        class="text-white bg-brand hover:bg-brand/90 font-medium rounded-lg text-xs px-3 py-1.5 disabled:opacity-50 whitespace-nowrap"
                                        @click="runNow(task)">
                                        {{ launching === task.id ? 'Lanzando…' : 'Ejecutar ahora' }}
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pantallas angostas: una tarjeta por tarea -->
                <div class="md:hidden space-y-3">
                    <div
                        v-for="task in report.tasks"
                        :key="task.id"
                        class="p-4 rounded-lg border border-gray-200 dark:border-gray-700 text-sm space-y-2">
                        <div class="font-medium text-gray-900 dark:text-white">{{ shortName(task) }}</div>
                        <div class="text-gray-600 dark:text-gray-300">
                            {{ task.frequency }} · próxima {{ task.next_run ?? '—' }}
                        </div>
                        <div v-if="task.last_run" class="flex flex-wrap items-center gap-2">
                            <span
                                :class="
                                    task.last_run.exit === 0
                                        ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300'
                                        : 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300'
                                "
                                class="text-xs font-medium px-2 py-0.5 rounded">
                                {{ task.last_run.exit === 0 ? 'OK' : `Error ${task.last_run.exit}` }}
                            </span>
                            <span class="text-gray-600 dark:text-gray-300">
                                {{ task.last_run.started_at }} · {{ task.last_run.ms }} ms
                            </span>
                        </div>
                        <p v-if="task.last_run?.error" class="text-xs text-red-700 dark:text-red-400 break-words">
                            {{ task.last_run.error }}
                        </p>
                        <button
                            type="button"
                            :disabled="launching === task.id"
                            class="w-full text-white bg-brand hover:bg-brand/90 font-medium rounded-lg text-xs px-3 py-2 disabled:opacity-50"
                            @click="runNow(task)">
                            {{ launching === task.id ? 'Lanzando…' : 'Ejecutar ahora' }}
                        </button>
                    </div>
                </div>
            </template>
        </template>
    </div>
</template>
