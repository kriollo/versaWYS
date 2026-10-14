<script setup lang="ts">
    import type { Ref } from 'vue';

    import type { ConfigSettingsData } from '@/dashboard/js/configSettings/types';

    const model = defineModel({ required: true }) as Ref<ConfigSettingsData['build']>;
</script>

<template>
    <div class="space-y-6">
        <div class="grid grid-cols-2 gap-4">
            <div class="space-y-2 col-span-2 sm:col-span-1">
                <label class="block mb-2 text-sm font-medium text-gray-900 dark:text-white" for="build_name">
                    Nombre de la aplicación
                </label>
                <input
                    id="build_name"
                    type="text"
                    class="bg-gray-50 border border-gray-300 text-gray-900 sm:text-sm rounded-lg focus:ring-brand/30 focus:border-brand block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-brand/30 dark:focus:border-brand"
                    required
                    v-model="model.name" />
            </div>
            <div class="space-y-2 col-span-2 sm:col-span-1">
                <label class="block mb-2 text-sm font-medium text-gray-900 dark:text-white" for="build_version">
                    Versión
                </label>
                <input
                    id="build_version"
                    type="text"
                    class="bg-gray-50 border border-gray-300 text-gray-900 sm:text-sm rounded-lg focus:ring-brand/30 focus:border-brand block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-brand/30 dark:focus:border-brand"
                    required
                    v-model="model.version" />
            </div>
        </div>

        <div class="grid grid-cols-2 gap-4">
            <div class="space-y-2 col-span-2 sm:col-span-1">
                <label class="block mb-2 text-sm font-medium text-gray-900 dark:text-white" for="build_timezone">
                    Zona horaria
                </label>
                <input
                    id="build_timezone"
                    type="text"
                    placeholder="America/Santiago"
                    class="bg-gray-50 border border-gray-300 text-gray-900 sm:text-sm rounded-lg focus:ring-brand/30 focus:border-brand block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-brand/30 dark:focus:border-brand"
                    required
                    v-model="model.timezone" />
            </div>
            <div class="space-y-2 col-span-2 sm:col-span-1">
                <label class="block mb-2 text-sm font-medium text-gray-900 dark:text-white" for="build_charset">
                    Charset
                </label>
                <input
                    id="build_charset"
                    type="text"
                    class="bg-gray-50 border border-gray-300 text-gray-900 sm:text-sm rounded-lg focus:ring-brand/30 focus:border-brand block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-brand/30 dark:focus:border-brand"
                    required
                    v-model="model.charset" />
            </div>
        </div>
    </div>
</template>
