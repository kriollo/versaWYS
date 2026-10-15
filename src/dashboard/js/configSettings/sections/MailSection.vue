<script setup lang="ts">
    import { inject, ref, type Ref } from 'vue';

    import type { ConfigSettingsData } from '@/dashboard/js/configSettings/types';
    import { getPanelUrl, versaAlert, versaFetch } from '@/dashboard/js/functions';

    const model = defineModel({ required: true }) as Ref<ConfigSettingsData['mail']>,
        panelUrl = inject<string>('panelUrl', '') || getPanelUrl(),
        testing = ref(false);

    // Usa la configuración guardada: hay que guardar antes de probar
    async function sendTestMail(): Promise<void> {
        testing.value = true;
        const res = await versaFetch({ url: `/${panelUrl}/configuracion/testMail`, method: 'POST', data: {} });
        testing.value = false;

        await versaAlert({
            title: res.success ? 'Éxito' : 'Error',
            message: res.message ?? 'No se pudo enviar el correo de prueba.',
            type: res.success ? 'success' : 'error',
        });
    }
</script>

<template>
    <div class="space-y-6">
        <div class="grid grid-cols-2 gap-4">
            <div class="space-y-2 col-span-2 sm:col-span-1">
                <label class="block mb-2 text-sm font-medium text-gray-900 dark:text-white" for="mail_host">
                    Host SMTP
                </label>
                <input
                    id="mail_host"
                    type="text"
                    class="bg-gray-50 border border-gray-300 text-gray-900 sm:text-sm rounded-lg focus:ring-brand/30 focus:border-brand block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-brand/30 dark:focus:border-brand"
                    placeholder="mail.dominio.cl"
                    required
                    v-model="model.host" />
            </div>
            <div class="space-y-2 col-span-2 sm:col-span-1">
                <label class="block mb-2 text-sm font-medium text-gray-900 dark:text-white" for="mail_port">
                    Puerto
                </label>
                <input
                    id="mail_port"
                    type="number"
                    min="1"
                    max="65535"
                    class="bg-gray-50 border border-gray-300 text-gray-900 sm:text-sm rounded-lg focus:ring-brand/30 focus:border-brand block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-brand/30 dark:focus:border-brand"
                    required
                    v-model.number="model.port" />
            </div>
        </div>

        <div class="grid grid-cols-2 gap-4">
            <div class="space-y-2 col-span-2 sm:col-span-1">
                <label class="block mb-2 text-sm font-medium text-gray-900 dark:text-white" for="mail_transport">
                    Transporte
                </label>
                <input
                    id="mail_transport"
                    type="text"
                    class="bg-gray-50 border border-gray-300 text-gray-900 sm:text-sm rounded-lg focus:ring-brand/30 focus:border-brand block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-brand/30 dark:focus:border-brand"
                    placeholder="smtp"
                    required
                    v-model="model.transport" />
            </div>
            <div class="space-y-2 col-span-2 sm:col-span-1">
                <label class="block mb-2 text-sm font-medium text-gray-900 dark:text-white" for="mail_secure">
                    Seguridad
                </label>
                <select
                    id="mail_secure"
                    class="bg-gray-50 border border-gray-300 text-gray-900 sm:text-sm rounded-lg focus:ring-brand/30 focus:border-brand block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:text-white dark:focus:ring-brand/30 dark:focus:border-brand"
                    v-model="model.secure">
                    <option value="tls">TLS</option>
                    <option value="ssl">SSL</option>
                    <option value="">Ninguna</option>
                </select>
            </div>
        </div>

        <div class="space-y-2">
            <label class="block mb-2 text-sm font-medium text-gray-900 dark:text-white" for="mail_name_from">
                Nombre del remitente
            </label>
            <input
                id="mail_name_from"
                type="text"
                class="bg-gray-50 border border-gray-300 text-gray-900 sm:text-sm rounded-lg focus:ring-brand/30 focus:border-brand block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-brand/30 dark:focus:border-brand"
                required
                v-model="model.name_from" />
        </div>

        <div class="grid grid-cols-2 gap-4">
            <div class="space-y-2 col-span-2 sm:col-span-1">
                <label class="block mb-2 text-sm font-medium text-gray-900 dark:text-white" for="mail_username">
                    Usuario
                </label>
                <input
                    id="mail_username"
                    type="text"
                    class="bg-gray-50 border border-gray-300 text-gray-900 sm:text-sm rounded-lg focus:ring-brand/30 focus:border-brand block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-brand/30 dark:focus:border-brand"
                    required
                    v-model="model.username" />
            </div>
            <div class="space-y-2 col-span-2 sm:col-span-1">
                <label class="block mb-2 text-sm font-medium text-gray-900 dark:text-white" for="mail_password">
                    Contraseña
                </label>
                <input
                    id="mail_password"
                    type="password"
                    autocomplete="new-password"
                    class="bg-gray-50 border border-gray-300 text-gray-900 sm:text-sm rounded-lg focus:ring-brand/30 focus:border-brand block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-brand/30 dark:focus:border-brand"
                    placeholder="Dejar en blanco para mantener la actual"
                    v-model="model.password" />
            </div>
        </div>

        <div class="space-y-2">
            <button
                type="button"
                :disabled="testing"
                class="text-gray-900 bg-white border border-gray-300 hover:bg-gray-100 focus:ring-4 focus:outline-hidden focus:ring-gray-100 font-medium rounded-lg text-sm px-4 py-2 disabled:opacity-50 dark:bg-gray-800 dark:text-white dark:border-gray-600 dark:hover:bg-gray-700"
                @click="sendTestMail">
                {{ testing ? 'Enviando...' : 'Enviar correo de prueba' }}
            </button>
            <p class="text-sm text-gray-500 dark:text-gray-400">
                Se envía a tu correo con la configuración guardada: guarda los cambios antes de probar.
            </p>
        </div>
    </div>
</template>
