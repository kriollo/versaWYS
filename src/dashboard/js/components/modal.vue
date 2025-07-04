<docs lang="JSDoc">
/**
 * @preserve
 * This Vue component is a modal dialog that can be shown or hidden based on the `showModal` prop.
 *
 * Props:
 * @property {string} idModal - (required): The ID of the modal.
 * @property {boolean} showModal - (required): Determines whether the modal is visible.
 * @property {('max-w-md'|'max-w-lg'|'max-w-2xl'|'max-w-4xl'|'max-w-7xl')} [size='max-w-md'] - (optional): The size of the modal.
 *
 * Emits:
 * @event {Object} accion - Emits an object with the action to be performed.
 */
</docs>
<script setup lang="ts">
    import { computed, ref } from 'vue';

    import { GLOBAL_CONSTANTS } from '@/dashboard/js/constants';

    interface Props {
        idModal: string;
        showModal: boolean;
        size?: 'max-w-md' | 'max-w-lg' | 'max-w-2xl' | 'max-w-4xl' | 'max-w-7xl' | string;
        showFooter?: boolean;
    }

    const props = withDefaults(defineProps<Props>(), {
        showModal: true,
        size: 'max-w-md',
        showFooter: true,
    });

    const emit = defineEmits(['accion']);
    const componentKey = ref(GLOBAL_CONSTANTS.ZERO);

    const showModal = computed(() => props.showModal);
    const idModal = computed(() => props.idModal);
    const size = computed(() => props.size);
    const showFooter = computed(() => props.showFooter);
    const modal = ref(undefined);
</script>
<template>
    <Transition mode="in-out">
        <div
            v-if="showModal"
            class="flex overflow-y-auto overflow-x-hidden fixed top-0 right-0 left-0 z-50 justify-center items-center w-full md:inset-0 h-[calc(100%-1rem)] max-h-full backdrop-blur drop-shadow-versaWYS"
            :id="idModal"
            ref="modal"
            tabindex="-1">
            <div class="relative p-4 w-full max-h-full" :class="size">
                <!-- Modal content -->
                <div class="relative bg-white rounded-lg shadow dark:bg-gray-700">
                    <!-- Modal header -->
                    <div class="p-4 md:p-3 border-b rounded-t dark:border-gray-600">
                        <slot name="modalTitle"></slot>
                    </div>
                    <!-- Modal body -->
                    <div class="p-4 md:p-5 space-y-4">
                        <slot name="modalBody"></slot>
                    </div>
                    <!-- Modal footer -->
                    <div
                        v-if="showFooter"
                        class="flex justify-between gap-2 items-center p-4 md:p-5 border-t border-gray-200 rounded-b dark:border-gray-600">
                        <slot name="modalFooter"></slot>
                    </div>
                </div>
            </div>
        </div>
    </Transition>
</template>
<style scoped>
    .v-enter-active,
    .v-leave-active {
        transition: opacity 0.3s ease;
    }

    .v-enter-from,
    .v-leave-to {
        opacity: 0;
    }
</style>
