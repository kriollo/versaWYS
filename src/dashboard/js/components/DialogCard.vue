<script setup lang="ts">
    interface Props {
        title: string;
        subtitle?: string;
        maxWidth?: string;
        /** Para diálogos con contenido largo (p. ej. historial paginado): limita la altura y hace scroll solo en el cuerpo, dejando el header y el footer fijos. */
        scrollBody?: boolean;
    }

    withDefaults(defineProps<Props>(), {
        subtitle: '',
        maxWidth: 'max-w-sm',
        scrollBody: false,
    });

    const emit = defineEmits<{
        close: [];
    }>();
</script>

<template>
    <div class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4">
        <div
            :class="[
                'bg-white dark:bg-gray-800 rounded-2xl shadow-xl w-full',
                maxWidth,
                scrollBody ? 'flex flex-col max-h-[80vh]' : '',
            ]">
            <div class="flex items-center justify-between p-4 border-b dark:border-gray-700 flex-shrink-0">
                <div class="min-w-0">
                    <h2 class="text-base font-semibold text-gray-900 dark:text-white">{{ title }}</h2>
                    <p v-if="subtitle" class="text-xs text-gray-500 dark:text-gray-400 truncate max-w-sm">
                        {{ subtitle }}
                    </p>
                </div>
                <button
                    class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 flex-shrink-0 ml-4"
                    @click="emit('close')">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path
                            d="M6 18 18 6M6 6l12 12"
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2" />
                    </svg>
                </button>
            </div>
            <div :class="scrollBody ? 'flex-1 overflow-y-auto' : ''">
                <slot />
            </div>
            <div v-if="$slots.footer" class="flex-shrink-0">
                <slot name="footer" />
            </div>
        </div>
    </div>
</template>
