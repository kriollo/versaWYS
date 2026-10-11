<script setup lang="ts">
    interface Props {
        title: string;
        confirmLabel?: string;
        loadingLabel?: string;
        cancelLabel?: string;
        loading?: boolean;
    }

    withDefaults(defineProps<Props>(), {
        confirmLabel: 'Eliminar',
        loadingLabel: 'Eliminando...',
        cancelLabel: 'Cancelar',
        loading: false,
    });

    const emit = defineEmits<{
        confirm: [];
        close: [];
    }>();
</script>

<template>
    <div class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4">
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl w-full max-w-sm p-5">
            <h3 class="text-base font-semibold text-gray-900 dark:text-white mb-2">{{ title }}</h3>
            <div class="text-sm text-gray-600 dark:text-gray-300 mb-4">
                <slot />
            </div>
            <div class="flex justify-end gap-2">
                <button
                    class="px-4 py-2 text-sm text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition-colors"
                    @click="emit('close')">
                    {{ cancelLabel }}
                </button>
                <button
                    :disabled="loading"
                    class="px-4 py-2 text-sm bg-red-600 text-white rounded-lg hover:bg-red-700 transition-colors disabled:opacity-50"
                    @click="emit('confirm')">
                    {{ loading ? loadingLabel : confirmLabel }}
                </button>
            </div>
        </div>
    </div>
</template>
