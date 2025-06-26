<script setup lang="ts">
    import type { Perfil } from 'perfilTypes';
    import Swal from 'sweetalert2';
    import { computed, inject, type Ref, ref, watch } from 'vue';

    import { versaFetch, VersaToast } from '@/dashboard/js/functions';
import { API_RESPONSE_CODES } from '../constants';

    const props = defineProps({
        refreshData: {
            type: Boolean,
            dafault: false,
        },
    });

    const perfil = inject('perfil') as Ref<Perfil>;

    const refreshData = computed(() => props.refreshData);

    interface PerfilExtended extends Perfil {
        estado: string;
    }

    const data = ref<PerfilExtended[]>([]);

    const listPerfilesSide = async () => {
        const response = await versaFetch({
            url: '/admin/perfiles/all',
            method: 'GET',
        });

        if (response.success) {
            data.value = response.data;
        }
    };

    const editPerfil = (perfilSelected: Perfil) => {
        perfil.value = perfilSelected;
    };
    const deletePerfil = async (perfil: Perfil) => {
        const result = await Swal.fire({
            title: 'Actualizar estado del Perfil',
            text: `¿Estás seguro de cambiar el estado del perfil ${perfil.nombre}?`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Actualizar',
            cancelButtonText: 'Cancelar',
        });
        if (result.isConfirmed) {
            const response = await versaFetch({
                url: '/admin/perfiles/changeState',
                method: 'PATCH',
                data: { id: perfil.id },
            });

            if (API_RESPONSE_CODES.SUCCESS === response.success) {
                listPerfilesSide();
                VersaToast.fire({
                    icon: 'success',
                    title: 'Perfil actualizado correctamente',
                });
            } else {
                VersaToast.fire({
                    icon: 'error',
                    title: 'Error al actualizar el estado del perfil',
                });
            }
        }
    };

    watch(refreshData, () => {
        listPerfilesSide();
    });
</script>
<template>
    <div>
        <ul
            class="w-full text-sm font-medium text-gray-900 bg-white border border-gray-200 rounded-lg dark:bg-gray-700 dark:border-gray-600 dark:text-white">
            <li
                v-for="(item, key) in data"
                :key="item.id"
                class="flex justify-between items-center"
                :class="
                    key === data.length - 1
                        ? 'rounded-b-lg'
                        : 'w-full px-4 py-2 border-b border-gray-200 rounded-t-lg dark:border-gray-600'
                ">
                <div>
                    <span :class="item.estado === '0' ? 'line-through' : ''">
                        {{ item.nombre }}
                    </span>
                </div>
                <div class="flex gap-2">
                    <button type="button" class="text-xs text-blue-500 dark:text-blue-400" @click="editPerfil(item)">
                        Editar
                    </button>
                    <button
                        type="button"
                        class="text-xs"
                        :class="
                            item.estado === '0'
                                ? 'text-green-500 dark:text-green-400'
                                : 'text-red-500 dark:text-red-400'
                        "
                        @click="deletePerfil(item)">
                        {{ item.estado === '0' ? 'Activar' : 'Desactivar' }}
                    </button>
                </div>
            </li>
        </ul>
    </div>
</template>
