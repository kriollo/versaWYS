<script setup>
    import { dropDown } from '@/dashboard/js/components/dropdown';
    import { loader } from '@/dashboard/js/components/loader';

    import { $dom } from '@/dashboard/js/composables/dom';
    import { createXlsxFromJson } from '@/dashboard/js/composables/useXlsx';
    import { removeScape, versaFetch } from '@/dashboard/js/functions';
    import {
        computed,
        defineEmits,
        defineProps,
        reactive,
        ref,
        watch,
        watchEffect,
    } from 'vue';

    const props = defineProps({
        id: {
            type: String,
            default: 'table',
        },
        tablaTitle: {
            type: String,
            required: true,
        },
        urlData: {
            type: String,
            required: true,
        },
        refreshData: {
            type: Boolean,
            default: false,
        },
        totalRegisters: {
            type: Number,
            default: 0,
        },
        externalFilters: {
            type: String,
            required: false,
            default: '',
        },
        fieldOrder: {
            type: String,
            required: false,
            default: 'id',
        },
    });
    const tablaTitle = computed(() => props.tablaTitle);
    const emit = defineEmits(['accion', 'update:totalRegisters']);

    const slots = defineSlots();

    const msg = ref('Cargando...');
    const url = computed(() => props.urlData);
    const refresh = computed(() => props.refreshData);
    const externalFilters = computed(() => props.externalFilters);
    const idTable = computed(() => props.id);
    const fieldOrder = computed(() => props.fieldOrder);

    const loading = ref(false);
    const loadingData = ref(false);

    const showPerPages = [1, 5, 10, 25, 50, 100];

    const data = reactive({
        data: [],
        columns: [],
        colspan: [],
        meta: {
            total: 0,
            per_page: 25,
            page: 1,
            total_pages: 0,
            filter: '',
            from: 0,
            to: 0,
            order: [`${fieldOrder.value}`, 'asc'],
        },
    });

    const getRefreshData = async () => {
        loadingData.value = true;
        const page =
            new URLSearchParams(url.value).get('page') ?? data.meta.page;
        const per_page =
            new URLSearchParams(url.value).get('per_page') ??
            data.meta.per_page;
        const filter =
            new URLSearchParams(url.value).get('filter') ?? data.meta.filter;
        const order =
            new URLSearchParams(url.value).get('order') ?? data.meta.order;

        const response = await versaFetch({
            url: `${url.value}?page=${page}&per_page=${per_page}&filter=${filter}&order=${order}&externalFilters=${externalFilters.value}`,
            method: 'GET',
        });

        data.data = [];
        data.columns = [];
        if (response.success === 1) {
            data.data = response.data;
            data.columns = response.columns;
            data.colspan = response.colspan ?? [];

            data.meta.total = response.meta.total;
            data.meta.total_pages = response.meta.total_pages;
            data.meta.from = response.meta.from;
            data.meta.to = response.meta.to;
            data.meta.filter = response.meta.filter;

            if (data.data.length === 0) {
                msg.value = 'No hay registros para mostrar';
            }
        } else {
            msg.value = response.message;
        }
        loadingData.value = false;
    };

    watch(url, () => {
        getRefreshData();
    });

    watch(
        () => refresh.value,
        () => {
            getRefreshData();
        },
        { immediate: true },
    );

    watchEffect(() => {
        emit('update:totalRegisters', data.meta?.total ?? 0);
    });

    const exportExcelPage = async () => {
        loading.value = true;
        await createXlsxFromJson(data.data, idTable.value);
        loading.value = false;
    };

    const exportExcelAll = async () => {
        loading.value = true;
        const response = await versaFetch({
            url: `${url.value}?page=1&per_page=${data.meta.total}&filter=${data.meta.filter}&order=${data.meta.order}&externalFilters=${externalFilters.value}`,
            method: 'GET',
        });
        await createXlsxFromJson(response.data, idTable.value);
        loading.value = false;
    };

    const removeScapeLocal = str => removeScape(str);

    const accion = ({ item, accion, from = 'local' }) => {
        const actions = {
            setButtonValue: () => {
                if (from === 'per_page') {
                    setPerPage(item);
                } else if (from === 'excel') {
                    if (item === 'Exportar Página') {
                        exportExcelPage();
                    } else {
                        exportExcelAll();
                    }
                }
            },
            default: () => emit('accion', { item, accion }),
        };
        const fn = actions[accion] || actions.default;
        if (typeof fn === 'function') {
            fn();
        }
    };

    const setPerPage = per_page => {
        if (data.meta.per_page === per_page) return;
        data.meta.page = 1;
        data.meta.per_page = per_page;

        getRefreshData();

        const dropdownAction = $dom('#dropdownAction');
        if (!(dropdownAction instanceof HTMLElement)) return;
        dropdownAction.classList.remove('show');
        dropdownAction.classList.add('hidden');
        dropdownAction.setAttribute('aria-hidden', 'true');
    };
    const setFilter = () => {
        data.meta.page = 1;

        getRefreshData();
    };
    const setOrder = (field, order) => {
        data.meta.page = 1;

        if (data.meta.order[0] !== field) {
            order = 'asc';
        }

        data.meta.order = [field, order];
        getRefreshData();
    };
    const changePage = page => {
        if (page === 'siguiente') {
            if (data.meta.page < data.meta.total_pages) {
                page = data.meta.page + 1;
            } else {
                return;
            }
        }
        if (page === 'anterior') {
            if (data.meta.page > 1) {
                page = data.meta.page - 1;
            } else {
                return;
            }
        }

        data.meta.page = page;

        getRefreshData();
    };

    const getLimitPages = computed(() => {
        const limit = 3;
        const total_pages = data.meta.total_pages;
        const page = data.meta.page;
        const from = page - limit;
        const to = page + limit;

        if (from < 1) {
            if (total_pages < limit * 2) {
                const arr = Array.from(
                    { length: total_pages },
                    (_, i) => i + 1,
                );
                return arr;
            }
            const arr = Array.from({ length: limit * 2 }, (_, i) => i + 1);
            return arr;
        }

        if (to > total_pages) {
            if (total_pages < limit * 2) {
                const arr = Array.from(
                    { length: total_pages },
                    (_, i) => i + 1,
                );
                return arr;
            }
            const arr = Array.from(
                { length: limit * 2 },
                (_, i) => total_pages - i,
            );
            return arr.reverse();
        }

        const arr = Array.from({ length: limit * 2 }, (_, i) => from + i);
        return arr;
    });
</script>

<template>
    <div
        class="flex items-center justify-end flex-column flex-wrap md:flex-row gap-2 px-2 py-2">
        <slot name="buttons"></slot>
    </div>
    <div
        class="flex items-center justify-between flex-column flex-wrap md:flex-row space-y-4 md:space-y-0 py-2 px-2">
        <dropDown
            @accion="accion"
            key="per_page"
            title="Mostrar"
            from="per_page"
            :buttonValue="String(data.meta.per_page)"
            :list="showPerPages" />

        <dropDown
            @accion="accion"
            key="excel"
            from="excel"
            buttonValue="excel"
            :list="['Exportar Página', 'Exportar todo']" />

        <!-- input buscar -->
        <div class="relative">
            <div
                class="absolute inset-y-0 rtl:inset-r-0 end-0 flex items-center pe-3 cursor-pointer"
                @click="setFilter"
                title="Click para buscar">
                <svg
                    class="w-4 h-4 text-gray-500 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white"
                    aria-hidden="true"
                    fill="none"
                    viewBox="0 0 20 20"
                    xmlns="http://www.w3.org/2000/svg">
                    <path
                        d="m19 19-4-4m0-7A7 7 0 1 1 1 8a7 7 0 0 1 14 0Z"
                        stroke="currentColor"
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2" />
                </svg>
            </div>
            <input
                type="text"
                class="block p-2 ps-6 text-sm text-gray-900 border border-gray-300 rounded-lg w-80 bg-gray-50 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500"
                :id="idTable + '_table-search-users'"
                @keyup.enter="setFilter"
                placeholder="Ingrese y presione 'Enter' para buscar"
                v-model="data.meta.filter" />
        </div>
    </div>
    <div class="px-2">
        <table
            class="w-full text-sm text-left rtl:text-right text-gray-500 dark:text-gray-400">
            <caption
                class="py-2 px-2 text-lg font-semibold text-gray-900 bg-white dark:text-white dark:bg-gray-800 w-full">
                <div class="flex justify-between w-full">
                    <div>
                        {{ tablaTitle }}
                    </div>
                    <div>
                        <loader key="loadingData" v-if="loadingData"></loader>
                    </div>
                </div>
            </caption>
            <thead
                class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                <tr>
                    <th
                        :colspan="col.col"
                        scope="colspan"
                        v-for="col in data.colspan">
                        <div class="flex justify-center">{{ col.title }}</div>
                    </th>
                </tr>
            </thead>
            <thead
                class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                <tr>
                    <th
                        class="px-4 py-2"
                        scope="col"
                        v-for="col in data.columns">
                        {{ col.title }}
                        <button
                            class="inline-flex items-center justify-center w-6 h-6 ms-1 text-gray-500 dark:text-gray-400"
                            @click="
                                setOrder(
                                    col.field,
                                    data.meta.order[1] === 'asc'
                                        ? 'desc'
                                        : 'asc',
                                )
                            "
                            title="Ordenar"
                            v-if="
                                col.type !== 'actions' &&
                                col.type !== 'file' &&
                                col.type !== 'position'
                            ">
                            <svg
                                class="w-6 h-6"
                                aria-hidden="true"
                                fill="none"
                                viewBox="0 0 24 24"
                                xmlns="http://www.w3.org/2000/svg">
                                <path
                                    :d="
                                        data.meta.order[0] === col.field &&
                                        data.meta.order[1] === 'asc'
                                            ? 'M12 14l-4-4-4 4M12 10v8'
                                            : 'M12 10l4 4 4-4M12 14v-8'
                                    "
                                    :fill="
                                        data.meta.order[0] === col.field
                                            ? 'currentColor'
                                            : 'currentColor'
                                    "
                                    :stroke="
                                        data.meta.order[0] === col.field
                                            ? 'currentColor'
                                            : 'currentColor'
                                    "
                                    stroke-width="1" />
                            </svg>
                        </button>
                    </th>
                </tr>
            </thead>
            <tbody>
                <tr class="text-center" v-if="data.data.length === 0">
                    <td :colspan="data.columns.length">
                        <span class="text-xl" v-html="msg"></span>
                    </td>
                </tr>
                <tr
                    class="bg-white border-b dark:bg-gray-800 dark:border-gray-700"
                    :key="row.id"
                    v-for="(row, index) in data.data">
                    <td
                        class="px-4 py-4"
                        :key="col.field"
                        v-for="col in data.columns">
                        <!--status-->
                        <div v-if="col.type == 'status'">
                            <span
                                class="inline-flex items-center justify-center px-2 py-1 text-xs font-bold leading-none rounded-full"
                                :class="
                                    row[col.field] === '1' ||
                                    row[col.field] === 'activo'
                                        ? 'text-green-100 bg-green-600'
                                        : 'text-red-100 bg-red-600'
                                ">
                                {{
                                    row[col.field] === '1' ||
                                    row[col.field] === 'activo'
                                        ? 'Activo'
                                        : 'Inactivo'
                                }}
                            </span>
                        </div>
                        <!--svg-->
                        <div
                            class="flex justify-center"
                            v-else-if="col.type == 'svg'">
                            <svg
                                class="w-[20px] h-[20px] text-gray-800 dark:text-white"
                                aria-hidden="true"
                                fill="currentColor"
                                height="24"
                                v-html="removeScapeLocal(row[col.field])"
                                viewBox="0 0 24 24"
                                width="24"
                                xmlns="http://www.w3.org/2000/svg"></svg>
                        </div>
                        <!--actions-->
                        <div
                            class="flex justify-center gap-2"
                            v-else-if="col.type == 'actions'">
                            <div v-for="action in col.buttons">
                                <button
                                    :class="action.class"
                                    :key="action.id"
                                    :title="action.title"
                                    @click="
                                        accion({
                                            item: row,
                                            accion: action.action,
                                        })
                                    "
                                    v-if="
                                        action.condition_value ==
                                        row[action.condition]
                                    ">
                                    <i :class="action.icon"></i>
                                </button>
                            </div>
                        </div>
                        <!--position-->
                        <div
                            class="flex justify-between"
                            v-else-if="col.type == 'position'">
                            <span>{{ row[col.field] }}</span>
                            <div class="flex items-center justify-center gap-1">
                                <div v-for="action in col.buttons">
                                    <button
                                        :class="action.class"
                                        :key="action.id"
                                        :title="action.title"
                                        @click="
                                            accion({
                                                item: {
                                                    ...row,
                                                    direction: action.type,
                                                },
                                                accion: action.action,
                                            })
                                        "
                                        v-if="
                                            (index === 0 &&
                                            row[col.field] == 1 &&
                                            action.type === 'up'
                                                ? false
                                                : true) &&
                                            (index === data.meta.total - 1 &&
                                            action.type === 'down'
                                                ? false
                                                : true)
                                        ">
                                        <i :class="action.icon"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                        <!--others-->
                        <div v-else>{{ row[col.field] }}</div>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>

    <nav
        class="flex justify-between flex-column flex-wrap md:flex-row my-4 w-full px-2">
        <span class="text-sm font-normal text-gray-500 dark:text-gray-400 h-8">
            Monstrando
            <span class="font-semibold text-gray-900 dark:text-white">
                {{ data.meta.from }} al {{ data.meta.to }}
            </span>
            de
            <span class="font-semibold text-gray-900 dark:text-white">
                {{ data.meta.total }}
            </span>
            Resultados
        </span>

        <ul class="inline-flex -space-x-px rtl:space-x-reverse text-sm h-8">
            <li>
                <a
                    class="flex items-center justify-center px-3 h-8 ms-0 leading-tight text-gray-500 bg-white border border-gray-300 rounded-s-lg hover:bg-gray-100 hover:text-gray-700 dark:bg-gray-800 dark:border-gray-700 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white"
                    :class="
                        1 == data.meta.page
                            ? 'cursor-not-allowed'
                            : 'cursor-pointer'
                    "
                    @click="changePage('anterior')">
                    Anterior
                </a>
            </li>
            <li v-if="getLimitPages[0] > 1">
                <a
                    class="flex items-center justify-center px-3 h-8 text-blue-600 border border-gray-300 bg-blue-50 hover:bg-blue-100 hover:text-blue-700 dark:border-gray-700 dark:bg-gray-700 dark:text-white cursor-pointer"
                    @click="changePage(1)">
                    1
                </a>
            </li>

            <li v-if="getLimitPages[0] > 1">
                <a
                    class="flex items-center justify-center px-3 h-8 leading-tight text-gray-500 bg-white border border-gray-300 hover:bg-gray-100 hover:text-gray-700 dark:bg-gray-800 dark:border-gray-700 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white cursor-not-allowed">
                    ...
                </a>
            </li>

            <li v-for="page in getLimitPages">
                <a
                    class="flex items-center justify-center px-3 h-8 text-blue-600 border border-gray-300 bg-blue-50 hover:bg-blue-100 hover:text-blue-700 dark:border-gray-700 dark:bg-gray-700 dark:text-white cursor-pointer"
                    @click="changePage(page)"
                    aria-current="page"
                    v-if="data.meta.page == page">
                    {{ page }}
                </a>
                <a
                    class="flex items-center justify-center px-3 h-8 leading-tight text-gray-500 bg-white border border-gray-300 hover:bg-gray-100 hover:text-gray-700 dark:bg-gray-800 dark:border-gray-700 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white cursor-pointer"
                    @click="changePage(page)"
                    v-else>
                    {{ page }}
                </a>
            </li>

            <li v-if="data.meta.page < data.meta.total_pages">
                <a
                    class="flex items-center justify-center px-3 h-8 leading-tight text-gray-500 bg-white border border-gray-300 hover:bg-gray-100 hover:text-gray-700 dark:bg-gray-800 dark:border-gray-700 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white cursor-not-allowed">
                    ...
                </a>
            </li>
            <li
                v-if="
                    data.meta.page < data.meta.total_pages &&
                    getLimitPages[getLimitPages.length - 1] !=
                        data.meta.total_pages
                ">
                <a
                    class="flex items-center justify-center px-3 h-8 leading-tight text-gray-500 bg-white border border-gray-300 hover:bg-gray-100 hover:text-gray-700 dark:bg-gray-800 dark:border-gray-700 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white cursor-pointer"
                    @click="changePage(data.meta.total_pages)">
                    {{ data.meta.total_pages }}
                </a>
            </li>

            <li>
                <a
                    class="flex items-center justify-center px-3 h-8 leading-tight text-gray-500 bg-white border border-gray-300 rounded-e-lg hover:bg-gray-100 hover:text-gray-700 dark:bg-gray-800 dark:border-gray-700 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white"
                    :class="
                        data.meta.total_pages <= data.meta.page ||
                        data.meta.total_pages == undefined
                            ? 'cursor-not-allowed'
                            : 'cursor-pointer'
                    "
                    @click="changePage('siguiente')">
                    Siguiente
                </a>
            </li>
        </ul>
    </nav>
</template>
