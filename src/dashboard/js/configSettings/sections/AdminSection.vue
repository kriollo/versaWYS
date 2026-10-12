<script setup lang="ts">
    import type { Ref } from 'vue';

    import switchToggle from '@/dashboard/js/components/switchToggle.vue';
    import type { ConfigSettingsData } from '@/dashboard/js/configSettings/types';

    const model = defineModel({ required: true }) as Ref<ConfigSettingsData['admin']>;
</script>

<template>
    <div class="space-y-6">
        <div class="space-y-2 max-w-md">
            <label class="block mb-2 text-sm font-medium text-gray-900 dark:text-white" for="admin_custom_url">
                Prefijo de URL del panel
            </label>
            <input
                id="admin_custom_url"
                type="text"
                class="bg-gray-50 border border-gray-300 text-gray-900 sm:text-sm rounded-lg focus:ring-brand/30 focus:border-brand block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-brand/30 dark:focus:border-brand"
                required
                v-model="model.custom_url" />
            <p class="text-sm text-red-600 dark:text-red-400">
                Cuidado: si escribe un valor incorrecto, puede perder acceso al panel de administración hasta corregir
                manualmente el archivo de configuración.
            </p>
        </div>

        <div class="flex flex-wrap gap-8">
            <switchToggle
                id="admin_hide_default"
                label="Ocultar ruta por defecto"
                type="success"
                v-model="model.hide_default" />
            <switchToggle id="admin_redirect_404" label="Redirigir 404" type="success" v-model="model.redirect_404" />
        </div>
    </div>
</template>
