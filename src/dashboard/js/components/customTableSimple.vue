<docs lang="JSDoc">
/**
    * @preserve
    * @typedef {Object} Props
    * @property {String} id - Id de la tabla
    * @property {String} tablaTitle - Titulo de la tabla
    * @property {Array} columns - Columnas de la tabla
    * @property {Array} dataInput - Datos de la tabla
    * @property {String} fieldOrder - Campo de orden
    * @property {Number} perPage - Registros por página
    * @property {Boolean} showPerPage - Mostrar registros por página
    * @property {Boolean} showExportExcel - Mostrar exportar a excel
    * @property {Boolean} showSearch - Mostrar campo de búsqueda
    * @property {Object} itemSelected - Item seleccionado
    * @property {Boolean} smallLine - Linea pequeña
    */
</docs>

<script setup lang="ts">
    import { computed, reactive, ref, toRefs, watch, watchEffect } from 'vue';

    import dropDown from '@/dashboard/js/components/dropDown.vue';
    import loader from '@/dashboard/js/components/loader.vue';
    import { GLOBAL_CONSTANTS, PAGINATION } from '@/dashboard/js/constants';

    interface ItemSelected {
        id: string;
        fieldCompare: string;
    }

    interface Colspan {
        title: string;
        colspan: number;
    }

    interface Props {
        id?: string;
        tablaTitle?: string;
        columns: string[];
        dataInput: any[];
        fieldOrder?: string;
        perPage: number;
        showPerPage?: boolean;
        showExportExcel?: boolean;
        showSearch?: boolean;
        itemSelected?: ItemSelected;
        smallLine?: boolean;
        colspan?: Colspan[];
        reloadData?: boolean;
    }

    const props = withDefaults(defineProps<Props>(), {
        id: 'table',
        tablaTitle: '',
        fieldOrder: 'id',
        perPage: 25,
        showPerPage: true,
        showExportExcel: true,
        showSearch: true,
        smallLine: false,
        reloadData: false,
    });
    const emit = defineEmits(['accion', 'update:totalRegisters']);

    const loadingData = ref(false);

    const slots = defineSlots();

    const msg = ref('Cargando...');

    const {
        tablaTitle,
        id,
        fieldOrder,
        perPage,
        showPerPage,
        showExportExcel,
        showSearch,
        itemSelected,
        smallLine,
        columns,
        dataInput,
        colspan,
        reloadData,
    } = toRefs(props);

    const columnsValue = computed(() => columns.value ?? []);
    const dataInputValue = computed(() => dataInput.value ?? []);
    const fieldOrderValue = computed(() => fieldOrder.value ?? 'id');
    const perPageValue = computed(() => perPage.value ?? 25);

    const showPerPages = [...PAGINATION.PER_PAGE_OPTIONS];

    interface Data {
        data: any[];
        columns: string[];
        colspan: string[];
        meta: {
            total: number;
            per_page: number;
            page: number;
            total_pages: number;
            filter: string;
            from: number;
            to: number;
            order: string[];
        };
    }

    const data = reactive<Data>({
        data: [] as any[],
        columns: [] as string[],
        colspan: [] as string[],
        meta: {
            total: 0,
            per_page: perPageValue.value,
            page: PAGINATION.DEFAULT_PAGE,
            total_pages: 0,
            filter: '',
            from: 0,
            to: 0,
            order: [`${fieldOrderValue.value}`, 'asc'],
        },
    });

    const dataTemp = ref<any[]>([]);
    const refreshData = () => {
        data.columns = columnsValue.value;

        if ('' !== data.meta.filter) {
            const filter = data.meta.filter.toLowerCase();
            dataTemp.value = dataInputValue.value.filter(item =>
                Object.values(item).some((value: unknown) => value?.toString().toLowerCase().includes(filter)),
            );
        } else {
            dataTemp.value = dataInputValue.value;
        }
        // Paginación
        const start = (data.meta.page - PAGINATION.DEFAULT_PAGE) * data.meta.per_page;
        const end = data.meta.page * data.meta.per_page;
        data.data = dataTemp.value.slice(start, end);

        data.meta.total = dataTemp.value.length;
        data.meta.total_pages = Math.ceil(data.meta.total / data.meta.per_page);
        data.meta.from = start + PAGINATION.DEFAULT_PAGE;
        data.meta.to = end > data.meta.total ? data.meta.total : end;

        if (GLOBAL_CONSTANTS.ZERO === data.meta.total) {
            msg.value = 'No se encontraron registros';
        }
    };

    watch(
        () => dataInput.value,
        () => {
            refreshData();
        },
    );

    watch(
        () => props.columns,
        () => {
            refreshData();
        },
        { immediate: true },
    );

    watch(
        () => data.meta.per_page,
        () => {
            setPerPage(data.meta.per_page);
        },
    );

    watch(
        () => props.reloadData,
        () => {
            refreshData();
        },
    );

    watchEffect(() => {
        emit('update:totalRegisters', data.meta?.total ?? GLOBAL_CONSTANTS.ZERO);
    });

    const clearFiler = () => {
        data.meta.filter = '';
        refreshData();
    };
    const setPerPage = (per_page: number) => {
        data.meta.page = PAGINATION.DEFAULT_PAGE;
        data.meta.per_page = per_page;

        refreshData();
    };
    const setFilter = () => {
        data.meta.page = PAGINATION.DEFAULT_PAGE;
        refreshData();
    };
    const setOrder = (field: string, order: string) => {
        data.meta.page = PAGINATION.DEFAULT_PAGE;

        const newOrder = data.meta.order[PAGINATION.ARRAY_FIRST_INDEX] !== field ? 'asc' : order;

        data.meta.order = [field, newOrder];
        refreshData();
    };
    const changePage = (page: number | string) => {
        let goPage = GLOBAL_CONSTANTS.ZERO;
        if ('siguiente' === page) {
            if (data.meta.page < data.meta.total_pages) {
                goPage = data.meta.page + PAGINATION.DEFAULT_PAGE;
            } else {
                return;
            }
        }
        if ('anterior' === page) {
            if (PAGINATION.DEFAULT_PAGE < data.meta.page) {
                goPage = data.meta.page - PAGINATION.DEFAULT_PAGE;
            } else {
                return;
            }
        }

        data.meta.page = goPage;

        refreshData();
    };

    const getLimitPages = computed(() => {
        const limit = 3;
        const { total_pages, page } = data.meta;
        const from = page - limit;
        const to = page + limit;

        if (PAGINATION.DEFAULT_PAGE > from) {
            if (total_pages < limit * PAGINATION.PAGES_LIMIT_MULTIPLIER) {
                const arr = Array.from({ length: total_pages }, (_, i) => i + PAGINATION.DEFAULT_PAGE);
                return arr;
            }
            const arr = Array.from(
                { length: limit * PAGINATION.PAGES_LIMIT_MULTIPLIER },
                (_, i) => i + PAGINATION.DEFAULT_PAGE,
            );
            return arr;
        }

        if (to > total_pages) {
            if (total_pages < limit * PAGINATION.PAGES_LIMIT_MULTIPLIER) {
                const arr = Array.from({ length: total_pages }, (_, i) => i + PAGINATION.DEFAULT_PAGE);
                return arr;
            }
            const arr = Array.from({ length: limit * PAGINATION.PAGES_LIMIT_MULTIPLIER }, (_, i) => total_pages - i);
            return arr.toReversed();
        }

        const arr = Array.from({ length: limit * PAGINATION.PAGES_LIMIT_MULTIPLIER }, (_, i) => from + i);
        return arr;
    });

    //Detectar cambios en itemSelected y crear clase para linea de la tabla
    const classLineTable = (item: any): string => {
        if (itemSelected.value?.fieldCompare !== undefined && itemSelected.value.id === item.id) {
            return 'bg-brand-100 border border-brand dark:bg-brand-700 dark:border-brand-600 transition-colors';
        }
        return 'bg-white border-b border-gray-100 dark:bg-[#0a0a0a] dark:border-gray-800 hover:bg-gray-50 dark:hover:bg-gray-900/50 transition-colors';
    };
</script>

<template>
    <div class="flex flex-col space-y-6">
        <slot name="buttons"></slot>
        <div
            v-if="showPerPage || showExportExcel || showSearch"
            class="flex items-center justify-between flex-wrap gap-4 bg-white dark:bg-[#0a0a0a] p-4 rounded-2xl shadow-xs border border-gray-100 dark:border-gray-800">
            <dropDown
                v-if="showPerPage"
                key="per_page"
                v-model="data.meta.per_page"
                title="Mostrar"
                from="per_page"
                :list="showPerPages" />

            <!-- input buscar: Enter o la lupa aplican la búsqueda -->
            <div v-if="showSearch" class="relative w-full md:w-auto">
                <input
                    :id="id + '_table-search-users'"
                    v-model="data.meta.filter"
                    type="text"
                    :class="[
                        'block w-full md:w-80 p-3 pl-4 text-sm text-gray-900 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-brand focus:border-brand dark:bg-gray-900 dark:border-gray-800 dark:placeholder-gray-500 dark:text-white',
                        data.meta.filter ? 'pr-20' : 'pr-11',
                    ]"
                    placeholder="Buscar..."
                    aria-label="Buscar en la tabla"
                    @keyup.enter="setFilter" />
                <div class="absolute inset-y-0 right-0 flex items-center gap-0.5 pr-1.5">
                    <button
                        v-if="data.meta.filter"
                        type="button"
                        class="w-8 h-8 flex items-center justify-center rounded-lg text-gray-400 hover:text-red-500 hover:bg-red-50 dark:hover:bg-red-950/20 transition-colors"
                        title="Limpiar búsqueda"
                        aria-label="Limpiar búsqueda"
                        @click="clearFiler">
                        <i class="bi bi-x-lg text-sm" aria-hidden="true"></i>
                    </button>
                    <button
                        type="button"
                        class="w-8 h-8 flex items-center justify-center rounded-lg text-gray-500 hover:text-gray-900 hover:bg-gray-200 dark:hover:text-white dark:hover:bg-gray-800 transition-colors"
                        title="Buscar (Enter)"
                        aria-label="Buscar"
                        @click="setFilter">
                        <i class="bi bi-search text-sm" aria-hidden="true"></i>
                    </button>
                </div>
            </div>
        </div>
        <div
            class="relative isolate overflow-auto rounded-2xl border border-gray-100 dark:border-gray-800 shadow-xl bg-white dark:bg-[#0a0a0a] w-full">
            <table class="w-full text-sm text-left rtl:text-right text-gray-500 dark:text-gray-400 table-auto">
                <caption
                    v-if="tablaTitle !== ''"
                    class="p-5 text-lg font-bold text-left text-gray-900 bg-white dark:text-white dark:bg-[#0a0a0a] border-b border-gray-100 dark:border-gray-800">
                    <div class="flex justify-between items-center w-full">
                        <div class="flex items-center gap-2">
                            <div class="w-1 h-6 bg-brand rounded-full"></div>
                            {{ tablaTitle }}
                        </div>
                        <div>
                            <loader v-if="loadingData" key="loadingData"></loader>
                        </div>
                    </div>
                </caption>
                <thead
                    class="text-xs uppercase bg-gray-50 dark:bg-gray-900 text-gray-700 dark:text-gray-300 border-b border-gray-200 dark:border-gray-700">
                    <tr>
                        <th
                            v-for="(cols, index) in colspan"
                            :key="index + '_colspan'"
                            scope="colspan"
                            class="py-3 px-4 text-center font-bold tracking-wider"
                            :colspan="cols.colspan">
                            <div class="flex justify-center">
                                {{ cols.title }}
                            </div>
                        </th>
                    </tr>
                </thead>
                <thead
                    class="sticky top-0 z-20 text-xs text-white uppercase bg-black dark:bg-black/90 border-b-2 border-brand">
                    <tr>
                        <th
                            v-for="(col, index) in data.columns"
                            :key="index + '_col'"
                            :class="[smallLine ? 'py-2 px-3' : 'py-4 px-6', 'font-bold tracking-wider']"
                            scope="col">
                            {{ col }}
                        </th>
                    </tr>
                </thead>
                <tbody class="overflow-y-auto max-h-72">
                    <tr v-if="data.data.length === 0" class="text-center">
                        <td :colspan="data.columns.length" class="py-12">
                            <div class="flex flex-col items-center justify-center text-gray-400 dark:text-gray-600">
                                <i class="bi bi-inbox text-4xl mb-2 opacity-50" aria-hidden="true"></i>
                                <span class="text-base font-medium" v-html="msg"></span>
                            </div>
                        </td>
                    </tr>
                    <tr v-for="(row, index) in data.data" :key="index + '_row'" :class="classLineTable(row)">
                        <td
                            v-for="(col, key) in data.columns"
                            :key="key + '_col'"
                            :class="smallLine ? 'py-2 px-3' : 'py-4 px-6'">
                            <div v-if="col === 'acciones'" class="flex justify-end gap-2" v-html="row[key]"></div>
                            <div v-else>
                                {{ row[key] }}
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <nav class="flex flex-col md:flex-row justify-between items-center w-full px-2 gap-4">
            <span
                class="flex items-center gap-2 text-sm text-gray-500 dark:text-gray-400 bg-white dark:bg-[#0a0a0a] px-4 py-2 rounded-xl shadow-xs border border-gray-100 dark:border-gray-800">
                Mostrando
                <span class="font-bold text-gray-900 dark:text-white">{{ data.meta.from }} al {{ data.meta.to }}</span>
                de
                <span class="font-bold text-gray-900 dark:text-white">
                    {{ data.meta.total }}
                </span>
                Resultados
            </span>

            <ul
                class="inline-flex items-center -space-x-px rtl:space-x-reverse text-sm bg-white dark:bg-[#0a0a0a] rounded-xl shadow-xs border border-gray-100 dark:border-gray-800 p-1">
                <li>
                    <a
                        class="flex items-center justify-center px-3 py-2 leading-tight rounded-lg text-gray-500 hover:text-gray-900 hover:bg-gray-100 dark:text-gray-400 dark:hover:text-white dark:hover:bg-gray-800 transition-colors"
                        aria-label="Página anterior"
                        :class="1 == data.meta.page ? 'cursor-not-allowed opacity-50' : 'cursor-pointer'"
                        @click="changePage('anterior')">
                        <i class="bi bi-chevron-left" aria-hidden="true"></i>
                    </a>
                </li>
                <li v-if="getLimitPages[0] > 1">
                    <a
                        class="flex items-center justify-center w-8 h-8 mx-0.5 rounded-lg text-gray-500 hover:text-gray-900 hover:bg-gray-100 dark:text-gray-400 dark:hover:text-white dark:hover:bg-gray-800 cursor-pointer"
                        @click="changePage(1)">
                        1
                    </a>
                </li>

                <li v-if="getLimitPages[0] > 1">
                    <a class="flex items-center justify-center px-2 h-8 text-gray-400 cursor-not-allowed">...</a>
                </li>

                <li v-for="(page, index) in getLimitPages" :key="index + '_page'">
                    <a
                        v-if="data.meta.page == page"
                        class="flex items-center justify-center w-8 h-8 mx-0.5 text-black bg-brand font-bold rounded-lg shadow-xs cursor-pointer"
                        aria-current="page"
                        @click="changePage(page)">
                        {{ page }}
                    </a>
                    <a
                        v-else
                        class="flex items-center justify-center w-8 h-8 mx-0.5 rounded-lg text-gray-500 hover:text-gray-900 hover:bg-gray-100 dark:text-gray-400 dark:hover:text-white dark:hover:bg-gray-800 cursor-pointer"
                        @click="changePage(page)">
                        {{ page }}
                    </a>
                </li>

                <li v-if="data.meta.page < data.meta.total_pages">
                    <a class="flex items-center justify-center px-2 h-8 text-gray-400 cursor-not-allowed">...</a>
                </li>
                <li
                    v-if="
                        data.meta.page < data.meta.total_pages &&
                        getLimitPages[getLimitPages.length - 1] != data.meta.total_pages
                    ">
                    <a
                        class="flex items-center justify-center w-8 h-8 mx-0.5 rounded-lg text-gray-500 hover:text-gray-900 hover:bg-gray-100 dark:text-gray-400 dark:hover:text-white dark:hover:bg-gray-800 cursor-pointer"
                        @click="changePage(data.meta.total_pages)">
                        {{ data.meta.total_pages }}
                    </a>
                </li>

                <li>
                    <a
                        class="flex items-center justify-center px-3 py-2 leading-tight rounded-lg text-gray-500 hover:text-gray-900 hover:bg-gray-100 dark:text-gray-400 dark:hover:text-white dark:hover:bg-gray-800 transition-colors"
                        aria-label="Página siguiente"
                        :class="
                            data.meta.total_pages <= data.meta.page || data.meta.total_pages == undefined
                                ? 'cursor-not-allowed opacity-50'
                                : 'cursor-pointer'
                        "
                        @click="changePage('siguiente')">
                        <i class="bi bi-chevron-right" aria-hidden="true"></i>
                    </a>
                </li>
            </ul>
        </nav>
    </div>
</template>
