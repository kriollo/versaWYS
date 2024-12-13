<script setup>
import { computed } from 'vue';

const props = defineProps({
    id: {
        type: String,
        default: 'radio',
    },
    label: {
        type: String,
        default: '',
    },
    options: {
        type: Array,
        required: true,
    },
    direction: {
        type: String,
        default: 'row',
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

const options = computed(() => props.options);
const value = defineModel()

const id = computed(() => props.id);
const label = computed(() => props.label);
const direction = computed(() => props.direction);
const type = computed(() => types[props.type]);
const disabled = computed(() => props.disabled);

const types = {
    success: 'text-green-600 dark:text-green-400 focus:ring-green-500 dark:focus:ring-green-500',
    danger: 'text-red-600 dark:text-red-400 focus:ring-red-500 dark:focus:ring-red-500',
    warning: 'text-yellow-600 dark:text-yellow-400 focus:ring-yellow-500 dark:focus:ring-yellow-500',
    info: 'text-blue-600 dark:text-blue-400 focus:ring-blue-500 dark:focus:ring-blue-500',
};

</script>
<template>
    <div class="flex flex-col">
        <label class="block text-sm font-medium text-gray-700 dark:text-white" :for="id + '_' + options[0]?.id">
            {{ label }}
        </label>
        <div class="gap-2" :class="direction === 'row' ? 'flex' : 'flex flex-col'">
            <div v-for="option in options" class="flex gap-2">
                <input :id="id + '_' + option.id" :name="id" type="radio" class="h-5 w-5" :class="type"
                    :value="option?.value" v-model="value" :disabled="disabled" />
                <label class="block text-sm font-medium text-gray-900 dark:text-white" :for="id + '_' + option?.id">
                    {{ option?.name }}
                </label>
            </div>
        </div>
    </div>
</template>
