<script setup>
    import { versaFetch } from '@/dashboard/js/functions';
    import { computed, ref, watch } from 'vue';

    const props = defineProps({
        refreshData: {
            type: Boolean,
            dafault: false,
        },
    });

    const refreshData = computed(() => props.refreshData);

    const data = ref([]);

    const listPerfilesSide = async () => {
        const response = await versaFetch({
            url: '/admin/perfiles/all',
            method: 'GET',
        });

        if (response.success) {
            data.value = response.data;
        }
    };

    const editPerfil = perfil => {
        console.log('Editando perfil', perfil);
    };
    const deletePerfil = perfil => {
        console.log('Eliminando perfil', perfil);
    };

    watch(refreshData, () => {
        listPerfilesSide();
    });
</script>
<template>
    <ul
        class="w-48 text-sm font-medium text-gray-900 bg-white border border-gray-200 rounded-lg dark:bg-gray-700 dark:border-gray-600 dark:text-white">
        <li
            v-for="(item, key) in data"
            :key="item.id"
            :class="
                key === item.length - 1
                    ? 'rounded-t-lg'
                    : 'w-full px-4 py-2 border-b border-gray-200 rounded-t-lg dark:border-gray-600'
            ">
            <div>
                <span>{{ item.nombre }}</span>
                <span
                    class="text-xs text-gray-500 dark:text-gray-400"
                    v-if="item.estado === '1'">
                    Activo
                </span>
                <span class="text-xs text-red-500 dark:text-red-400" v-else>
                    Inactivo
                </span>
            </div>
            <div class="flex gap-2">
                <button
                    type="button"
                    class="text-xs text-blue-500 dark:text-blue-400"
                    @click="editPerfil(item)">
                    Editar
                </button>
                <button
                    type="button"
                    class="text-xs text-red-500 dark:text-red-400"
                    @click="deletePerfil(item)">
                    Desactivar
                </button>
            </div>
        </li>
    </ul>
</template>
