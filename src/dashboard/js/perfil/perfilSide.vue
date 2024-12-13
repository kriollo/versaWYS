<script setup>
    import { check } from '@/dashboard/js/components/check';
    import { inputEditable } from '@/dashboard/js/components/inputEditable';
    import { removeScape, versaFetch } from '@/dashboard/js/functions';
    import Swal from 'sweetalert2';
    import { inject, ref, watch } from 'vue';

    const emit = defineEmits(['accion']);

    const perfil = inject('perfil');
    const csrf_token = inject('csrf_token');

    const perfilData = ref({});
    const urls = ref([]);

    const removeScapeLocal = str => removeScape(str);
    const getPerfil = async () => {
        const response = await versaFetch({
            url: `/admin/perfiles/getPerfil/${perfil.value.id}`,
            method: 'GET',
        });

        if (response.success) {
            perfilData.value = response.data;
            urls.value = response.urls;
        }
    };

    watch(
        () => perfil.value,
        () => {
            getPerfil();
        },
    );

    const savePerfilPersmisos = async () => {
        if (perfil.value?.id === undefined) {
            return;
        }

        const data = {
            id: perfil.value.id,
            nombre: perfil.value.nombre,
            pagina_inicio: perfil.value.pagina_inicio,
            csrf_token: csrf_token,
            data:
                JSON.stringify(perfilData.value) === []
                    ? ''
                    : JSON.stringify(perfilData.value),
        };

        const response = await versaFetch({
            url: '/admin/perfiles/savePerfilPermisos',
            method: 'POST',
            data,
        });

        if (response.success) {
            Swal.fire({
                icon: 'success',
                title: 'Perfil actualizado',
                showConfirmButton: false,
                timer: 1500,
            });
        } else {
            Swal.fire({
                icon: 'error',
                title: 'Error al actualizar el perfil',
                showConfirmButton: false,
                timer: 1500,
            });
        }
    };

    const accion = (/** @type {Object} */ accion) => {
        const actions = {
            updateData: () => {
                savePerfilPersmisos();
            },
        };

        const selectedAction = actions[accion.accion] || actions['default'];
        if (typeof selectedAction === 'function') {
            selectedAction();
        }
    };
</script>
<template>
    <div class="w-full mx-auto rounded-lg shadow-md overflow-hidden">
        <div class="px-4 py-2">
            <inputEditable
                v-model="perfil.nombre"
                id="nombre"
                field="nombre"
                type="text"
                placeholder="Nombre del perfil"
                @accion="accion" />
            <hr class="my-4" />
        </div>
        <div class="px-6 text-gray-700 dark:text-white">
            <div class="flex justify-between">
                <h2 class="text-lg font-semibold">Pagina de Inicio:</h2>
                <select
                    class="w-[80%] p-2 border border-gray-300 rounded-lg bg-white dark:bg-gray-800"
                    v-model="perfil.pagina_inicio">
                    <option
                        v-for="(url, key) in urls"
                        :key="key"
                        :value="url.url">
                        <ruby>
                            <rt>{{ url.nombre }}&nbsp;</rt>
                            <rp>(</rp>
                            <rt>{{ url.url }}</rt>
                            <rp>)</rp>
                        </ruby>
                    </option>
                </select>
            </div>

            <h2 class="text-lg font-semibold">Permisos</h2>
            <hr class="my-4" />
            <div class="grid grid-cols-1 w-[80%]">
                <div v-for="(seccion, key) in perfilData" :key="key">
                    <span class="">{{ key }}</span>
                    <ul class="ml-4">
                        <li
                            v-for="(menu, index) in seccion"
                            :key="index"
                            class="py-2 flex gap-2">
                            <svg
                                class="flex-shrink-0 w-6 h-6 text-gray-500 transition duration-75 group-hover:text-gray-900 dark:text-gray-400 dark:group-hover:text-white"
                                aria-hidden="true"
                                xmlns="http://www.w3.org/2000/svg"
                                width="24"
                                height="24"
                                :fill="
                                    menu.fill_menu === '1'
                                        ? 'currentColor'
                                        : 'none'
                                "
                                viewbox="0 0 24 24"
                                v-html="removeScapeLocal(menu.icon)"></svg>
                            <div v-if="menu.submenu.length > 0" class="w-full">
                                <span>{{ index }}</span>
                                <ul class="ml-2">
                                    <li
                                        v-for="(submenu, i) in menu.submenu"
                                        :key="i"
                                        class="flex gap-2">
                                        <i class="bi bi-circle"></i>
                                        <div
                                            class="flex justify-between gap-2 w-full">
                                            <label
                                                class="block text-sm font-medium text-gray-900 dark:text-white"
                                                :for="
                                                    submenu.id_menu +
                                                    '_' +
                                                    submenu.id_submenu
                                                ">
                                                {{ submenu.submenu }}
                                            </label>
                                            <check
                                                :id="
                                                    submenu.id_menu +
                                                    '_' +
                                                    submenu.id_submenu
                                                "
                                                :key="
                                                    submenu.id_menu +
                                                    '_' +
                                                    submenu.id_submenu
                                                "
                                                v-model="submenu.checked" />
                                        </div>
                                    </li>
                                </ul>
                            </div>
                            <div v-else class="w-full">
                                <div class="flex justify-between gap-2">
                                    <label
                                        class="block text-sm font-medium text-gray-900 dark:text-white"
                                        :for="menu.id_menu">
                                        {{ index }}
                                    </label>
                                    <check
                                        :id="menu.id_menu"
                                        :key="menu.id_menu"
                                        v-model="menu.checked" />
                                </div>
                            </div>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</template>
