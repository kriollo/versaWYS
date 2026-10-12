<script setup lang="ts">
    import type { Ref } from 'vue';

    import type { ConfigSettingsData } from '@/dashboard/js/configSettings/types';

    const model = defineModel({ required: true }) as Ref<ConfigSettingsData['login_attempt']>;
</script>

<template>
    <div class="grid grid-cols-2 gap-4">
        <div class="space-y-2 col-span-2 sm:col-span-1">
            <label class="block mb-2 text-sm font-medium text-gray-900 dark:text-white" for="login_max">
                Intentos máximos
            </label>
            <input
                id="login_max"
                type="number"
                min="1"
                class="bg-gray-50 border border-gray-300 text-gray-900 sm:text-sm rounded-lg focus:ring-brand/30 focus:border-brand block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-brand/30 dark:focus:border-brand"
                required
                v-model.number="model.max" />
        </div>
        <div class="space-y-2 col-span-2 sm:col-span-1">
            <label class="block mb-2 text-sm font-medium text-gray-900 dark:text-white" for="login_time">
                Tiempo de bloqueo (segundos)
            </label>
            <input
                id="login_time"
                type="number"
                min="1"
                class="bg-gray-50 border border-gray-300 text-gray-900 sm:text-sm rounded-lg focus:ring-brand/30 focus:border-brand block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-brand/30 dark:focus:border-brand"
                required
                v-model.number="model.time" />
        </div>
    </div>
</template>
