<script setup lang="ts">
    import type { Ref } from 'vue';

    import switchToggle from '@/dashboard/js/components/switchToggle.vue';
    import type { ConfigSettingsData } from '@/dashboard/js/configSettings/types';

    const model = defineModel({ required: true }) as Ref<ConfigSettingsData['auth']>;
</script>

<template>
    <div class="space-y-6">
        <div class="grid grid-cols-2 gap-4">
            <div class="space-y-2 col-span-2 sm:col-span-1">
                <label
                    class="block mb-2 text-sm font-medium text-gray-900 dark:text-white"
                    for="expiration_days_password">
                    Días de expiración de contraseña
                </label>
                <input
                    id="expiration_days_password"
                    type="number"
                    min="1"
                    class="bg-gray-50 border border-gray-300 text-gray-900 sm:text-sm rounded-lg focus:ring-brand/30 focus:border-brand block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-brand/30 dark:focus:border-brand"
                    required
                    v-model.number="model.expiration_days_password" />
            </div>
            <div class="space-y-2 col-span-2 sm:col-span-1">
                <label class="block mb-2 text-sm font-medium text-gray-900 dark:text-white" for="inactive_account_days">
                    Días de inactividad para bloqueo de cuenta
                </label>
                <input
                    id="inactive_account_days"
                    type="number"
                    min="1"
                    class="bg-gray-50 border border-gray-300 text-gray-900 sm:text-sm rounded-lg focus:ring-brand/30 focus:border-brand block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-brand/30 dark:focus:border-brand"
                    required
                    v-model.number="model.inactive_account_days" />
            </div>
        </div>

        <div class="space-y-2 max-w-xs">
            <label class="block mb-2 text-sm font-medium text-gray-900 dark:text-white" for="password_large">
                Largo mínimo de contraseña
            </label>
            <input
                id="password_large"
                type="number"
                min="1"
                class="bg-gray-50 border border-gray-300 text-gray-900 sm:text-sm rounded-lg focus:ring-brand/30 focus:border-brand block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-brand/30 dark:focus:border-brand"
                required
                v-model.number="model.password_policy.large" />
        </div>

        <div class="flex flex-wrap gap-8">
            <switchToggle
                id="pp_uppercase"
                label="Mayúsculas"
                type="success"
                v-model="model.password_policy.uppercase" />
            <switchToggle
                id="pp_lowercase"
                label="Minúsculas"
                type="success"
                v-model="model.password_policy.lowercase" />
            <switchToggle id="pp_number" label="Números" type="success" v-model="model.password_policy.number" />
            <switchToggle
                id="pp_special_chars"
                label="Caracteres especiales"
                type="success"
                v-model="model.password_policy.special_chars" />
        </div>
    </div>
</template>
