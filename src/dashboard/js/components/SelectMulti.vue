<docs lang="JSDoc">
/**
 * @preserve
 * Selector múltiple con etiquetas (tags) y agrupación de opciones.
 *
 * Props:
 * @property {SelectMultiOption[]} options - Lista de opciones con value, label y group opcional.
 * @property {string[]} [modelValue=[]] - Array de valores seleccionados (v-model).
 * @property {string} [placeholder='Seleccionar...'] - Texto cuando no hay selección.
 * @property {string} from - ID único para el componente (evita colisiones con múltiples instancias).
 *
 * Emits:
 * @event update:modelValue - Emite el array actualizado de valores seleccionados.
 */
</docs>

<script setup lang="ts">
    import { computed, onMounted, onUnmounted, ref } from 'vue';

    export interface SelectMultiOption {
        value: string;
        label: string;
        group?: string;
    }

    interface Props {
        modelValue?: string[];
        options: SelectMultiOption[];
        placeholder?: string;
        from: string;
    }

    const props = withDefaults(defineProps<Props>(), {
        modelValue: () => [],
        placeholder: 'Seleccionar...',
    });

    const emit = defineEmits<{
        'update:modelValue': [value: string[]];
    }>();

    const isOpen = ref(false);

    // Agrupa las opciones preservando el orden de inserción
    const groupedEntries = computed((): [string, SelectMultiOption[]][] => {
        const map = new Map<string, SelectMultiOption[]>();
        for (const opt of props.options) {
            const g = opt.group ?? '';
            const list = map.get(g);
            if (list) {
                list.push(opt);
            } else {
                map.set(g, [opt]);
            }
        }
        return [...map.entries()];
    });

    const selected = computed((): string[] => props.modelValue ?? []);

    const selectedItems = computed((): { value: string; label: string }[] =>
        selected.value.map(v => ({
            value: v,
            label: props.options.find(o => o.value === v)?.label ?? v,
        })),
    );

    const triggerLabel = computed((): string => {
        const count = selected.value.length;
        if (count === 0) {
            return props.placeholder;
        }
        if (count === 1) {
            return `1 seleccionado`;
        }
        return `${count} seleccionados`;
    });

    function toggle(value: string): void {
        const current = [...selected.value];
        const idx = current.indexOf(value);
        if (idx === -1) {
            current.push(value);
        } else {
            current.splice(idx, 1);
        }
        emit('update:modelValue', current);
    }

    function isSelected(value: string): boolean {
        return selected.value.includes(value);
    }

    function remove(value: string): void {
        emit(
            'update:modelValue',
            selected.value.filter(v => v !== value),
        );
    }

    function onClickOutside(e: MouseEvent): void {
        const el = document.querySelector(`#selectMulti-${props.from}`);
        if (el && !el.contains(e.target as Node)) {
            isOpen.value = false;
        }
    }

    onMounted(() => document.addEventListener('click', onClickOutside));
    onUnmounted(() => document.removeEventListener('click', onClickOutside));
</script>

<template>
    <div :id="`selectMulti-${from}`" class="relative flex flex-col gap-1">
        <!-- Botón disparador -->
        <button
            type="button"
            class="inline-flex items-center gap-2 px-3 py-2 text-sm rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-brand/50 min-w-[160px]"
            @click="isOpen = !isOpen">
            <span class="flex-1 text-left truncate">{{ triggerLabel }}</span>
            <svg
                class="w-3 h-3 flex-shrink-0 transition-transform duration-200"
                :class="isOpen ? 'rotate-180' : ''"
                fill="none"
                viewBox="0 0 10 6"
                xmlns="http://www.w3.org/2000/svg">
                <path
                    d="m1 1 4 4 4-4"
                    stroke="currentColor"
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2" />
            </svg>
        </button>

        <!-- Tags de elementos seleccionados -->
        <div v-if="selectedItems.length > 0" class="flex flex-wrap gap-1">
            <span
                v-for="item in selectedItems"
                :key="item.value"
                class="inline-flex items-center gap-1 px-2 py-0.5 text-xs font-medium bg-brand/10 text-brand rounded-full">
                {{ item.label }}
                <button
                    type="button"
                    :aria-label="`Quitar ${item.label}`"
                    class="hover:text-brand/60 focus:outline-none"
                    @click.stop="remove(item.value)">
                    <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                        <path
                            clip-rule="evenodd"
                            d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z"
                            fill-rule="evenodd" />
                    </svg>
                </button>
            </span>
        </div>

        <!-- Menú desplegable con grupos -->
        <div
            v-if="isOpen"
            class="absolute top-full left-0 z-20 mt-1 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg shadow-lg min-w-[200px] max-h-72 overflow-y-auto py-1">
            <template v-for="[groupName, groupOptions] in groupedEntries" :key="groupName">
                <!-- Cabecera de grupo (solo si tiene nombre) -->
                <div
                    v-if="groupName"
                    class="px-3 pt-2 pb-1 text-xs font-semibold text-gray-400 dark:text-gray-500 uppercase tracking-wide border-b border-gray-100 dark:border-gray-700">
                    {{ groupName }}
                </div>
                <!-- Opciones del grupo -->
                <label
                    v-for="opt in groupOptions"
                    :key="opt.value"
                    class="flex items-center gap-3 px-4 py-2.5 text-sm text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-700/60 cursor-pointer transition-colors select-none"
                    :class="isSelected(opt.value) ? 'bg-brand/5 dark:bg-brand/10' : ''">
                    <!-- Checkbox estilizado -->
                    <span
                        class="flex-shrink-0 w-4.5 h-4.5 rounded border-2 flex items-center justify-center transition-all duration-150"
                        :class="
                            isSelected(opt.value)
                                ? 'bg-brand border-brand'
                                : 'border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800'
                        ">
                        <svg
                            v-if="isSelected(opt.value)"
                            class="w-2.5 h-2.5 text-white"
                            fill="none"
                            viewBox="0 0 12 10"
                            xmlns="http://www.w3.org/2000/svg">
                            <path
                                d="M1 5l3.5 3.5L11 1"
                                stroke="currentColor"
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2" />
                        </svg>
                    </span>
                    <input
                        type="checkbox"
                        class="sr-only"
                        :checked="isSelected(opt.value)"
                        @change="toggle(opt.value)" />
                    <span class="leading-tight">{{ opt.label }}</span>
                </label>
            </template>
        </div>
    </div>
</template>
