<script setup>
    import { computed } from 'vue';

    const props = defineProps({
        id: {
            type: String,
            default: 'estado',
        },
        label: {
            type: String,
            default: '',
        },
        type: {
            type: String,
            default: 'success',
        },
        disabled: {
            type: Boolean,
            default: false,
        },
    });
    const estado = defineModel();
    const type = computed(() => types[props.type]);
    const id = computed(() => props.id);
    const label = computed(() => props.label);
    const disabled = computed(() => props.disabled);

    const types = {
        success:
            'text-green-600 dark:text-green-400 focus:ring-green-500 dark:focus:ring-green-500',
        danger: 'text-red-600 dark:text-red-400 focus:ring-red-500 dark:focus:ring-red-500',
        warning:
            'text-yellow-600 dark:text-yellow-400 focus:ring-yellow-500 dark:focus:ring-yellow-500',
        info: 'text-blue-600 dark:text-blue-400 focus:ring-blue-500 dark:focus:ring-blue-500',
    };
</script>
<template>
    <div class="flex gap-2">
        <input
            :id="id"
            type="checkbox"
            class="h-5 w-5"
            :class="type"
            v-model="estado"
            :disabled="disabled" />
        <label
            class="block text-sm font-medium text-gray-900 dark:text-white"
            :for="id">
            {{ label }}
        </label>
    </div>
</template>
