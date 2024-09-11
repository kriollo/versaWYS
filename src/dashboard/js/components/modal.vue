<script setup>
    import { computed, ref, watch } from 'vue';

    const props = defineProps({
        idModal: {
            type: String,
            required: true,
        },
        showModal: {
            type: Boolean,
            required: true,
        },
        size: {
            type: String,
            default: 'max-w-md',
        },
    });

    const emit = defineEmits(['accion']);

    const showModal = computed(() => props.showModal);
    const idModal = computed(() => props.idModal);
    const size = computed(() => props.size);
    const modal = ref(null);

    watch(showModal, (/** @type {Boolean} */ val) => {
        if (val) {
            modal.value.classList.remove('hidden');
            modal.value.classList.add('flex');
        } else {
            modal.value.classList.remove('flex');
            modal.value.classList.add('hidden');
        }
    });

    const closeModal = () => {
        emit('accion', {
            accion: 'closeModal',
        });
    };
</script>
<template>
    <div
        class="hidden overflow-y-auto overflow-x-hidden fixed top-0 right-0 left-0 z-50 justify-center items-center w-full md:inset-0 h-[calc(100%-1rem)] max-h-full backdrop-blur drop-shadow-versaWYS"
        :id="idModal"
        aria-hidden="true"
        ref="modal"
        tabindex="-1">
        <div class="relative p-4 w-full max-h-full" :class="size">
            <!-- Modal content -->
            <div class="relative bg-white rounded-lg shadow dark:bg-gray-700">
                <!-- Modal header -->
                <div class="p-4 md:p-5 border-b rounded-t dark:border-gray-600">
                    <slot name="modalTitle"></slot>
                </div>
                <!-- Modal body -->
                <div class="p-4 md:p-5 space-y-4">
                    <slot name="modalBody"></slot>
                </div>
                <!-- Modal footer -->
                <div
                    class="flex justify-between gap-2 items-center p-4 md:p-5 border-t border-gray-200 rounded-b dark:border-gray-600">
                    <slot name="modalFooter"></slot>
                </div>
            </div>
        </div>
    </div>
</template>
