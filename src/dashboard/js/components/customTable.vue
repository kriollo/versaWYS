<docs lang="JSDoc">
/**
 * @preserve
 * @typedef {Object} Props
 * @property {String} id - Id de la tabla
 * @property {String} tablaTitle - Titulo de la tabla
 * @property {String} urlData - Url de la data
 * @property {Boolean} refreshData - Refrescar data
 * @property {Number} totalRegisters - Total de registros
 * @property {String} externalFilters - Filtros externos
 * @property {String} fieldOrder - Campo de orden
 */
</docs>

<script setup lang="ts">
    import {
        computed,
        onMounted,
        reactive,
        ref,
        toRefs,
        watch,
        watchEffect,
    } from 'vue';

    import dropDown from '@/dashboard/js/components/dropDown.vue';
    import loader from '@/dashboard/js/components/loader.vue';
    import { createXlsxFromJson } from '@/dashboard/js/composables/useXlsx';
    import {
        API_RESPONSE_CODES,
        GLOBAL_CONSTANTS,
        PAGINATION,
    } from '@/dashboard/js/constants';
    import { removeScape, versaFetch } from '@/dashboard/js/functions';
    import type { AccionData, actionsType } from '@/dashboard/types/versaTypes';

    // Tipos para las columnas de la tabla
    interface ActionButton {
        id: string;
        title: string;
        action: string;
        icon: string;
        class: string;
        condition?: string;
        condition_value?: string;
        type?: 'up' | 'down'; // Para posición
    }

    interface TableColumn {
        field: string;
        title: string;
        type:
            | 'status'
            | 'affirmative'
            | 'svg'
            | 'actions'
            | 'position'
            | 'file'
            | 'text';
        buttons?: ActionButton[];
        visible?: boolean;
        export?: boolean; // Para exportación Excel
    }

    interface ColspanColumn {
        col: number;
        title: string;
    }

    // Tipo para las filas de datos
    interface RowData {
        id: string | number;
        [key: string]: any;
    }

    interface Props {
        id?: string;
        tablaTitle?: string;
        urlData: string;
        refreshData?: boolean;
        totalRegisters?: number;
        externalFilters?: string;
        fieldOrder?: string;
        PerPage?: number;
        showPerPage?: boolean;
        showExportExcel?: boolean;
        showSearch?: boolean;
        itemSelected?: any;
        smallLine?: boolean;
        multipleSelected?: boolean;
        perPage?: number;
    }

    const props = withDefaults(defineProps<Props>(), {
        id: 'table',
        tablaTitle: '',
        refreshData: false,
        externalFilters: '',
        fieldOrder: 'id',
        perPage: 25,
        showPerPage: true,
        showExportExcel: true,
        showSearch: true,
        smallLine: false,
        multipleSelected: false,
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
    const perPage = computed(() => props.perPage);

    const {
        showPerPage,
        showExportExcel,
        showSearch,
        itemSelected,
        smallLine,
        multipleSelected,
    } = toRefs(props);

    const loading = ref(false);
    const loadingData = ref(false);

    const showPerPages = [...PAGINATION.PER_PAGE_OPTIONS];
    const data = reactive({
        data: [] as RowData[],
        columns: [] as TableColumn[],
        colspan: [] as ColspanColumn[],
        meta: {
            total: 0,
            per_page: perPage.value,
            page: 1,
            total_pages: 0,
            filter: '',
            from: 0,
            to: 0,
            order: [`${fieldOrder.value}`, 'asc'],
        },
    });

    watch(
        () => data.meta.per_page,
        () => {
            setPerPage(data.meta.per_page);
        },
    );
    interface ResponseData {
        success: number;
        data: RowData[];
        columns: TableColumn[];
        colspan: ColspanColumn[];
        meta: {
            total: number;
            total_pages: number;
            from: number;
            to: number;
            filter: string;
        };
        message: string;
    }

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

        const response = (await versaFetch({
            url: `${url.value}?page=${page}&per_page=${per_page}&filter=${filter}&order=${order}&externalFilters=${externalFilters.value}`,
            method: 'GET',
        })) as ResponseData;

        data.data = [];
        data.columns = [];
        if (API_RESPONSE_CODES.SUCCESS === response.success) {
            data.data = response.data;
            data.columns = response.columns;
            data.colspan = response.colspan ?? [];

            data.meta.total = response.meta.total;
            data.meta.total_pages = response.meta.total_pages;
            data.meta.from = response.meta.from;
            data.meta.to = response.meta.to;
            data.meta.filter = response.meta.filter;

            if (data.data.length === GLOBAL_CONSTANTS.ZERO) {
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
    );

    onMounted(() => {
        getRefreshData();
    });

    const model = defineModel();

    watchEffect(() => {
        model.value = data.meta.total;
    });

    const exportExcelPage = async () => {
        loading.value = true;
        const newDataExport = data.data.map((item: any) => {
            const newItem: { [key: string]: any } = {};
            data.columns.forEach((col: TableColumn) => {
                if ((col as any)?.export) {
                    newItem[col.field] = (item as any)[col.field];
                }
            });
            return newItem;
        });

        await createXlsxFromJson(newDataExport, idTable.value);
        loading.value = false;
    };

    const exportExcelAll = async () => {
        loading.value = true;
        const response = await versaFetch({
            url: `${url.value}?page=1&per_page=${data.meta.total}&filter=${data.meta.filter}&order=${data.meta.order}&externalFilters=${externalFilters.value}`,
            method: 'GET',
        });
        const newData = response.data.map((item: any) => {
            const newItem: { [key: string]: any } = {};
            data.columns.forEach((col: TableColumn) => {
                if ((col as any)?.export) {
                    newItem[col.field] = (item as any)[col.field];
                }
            });
            return newItem;
        });

        await createXlsxFromJson(newData, idTable.value);
        loading.value = false;
    };

    const removeScapeLocal = (str: string) => removeScape(str);

    const modelExcel = ref('Excel');
    const accion = (accionData: AccionData) => {
        const { item, from, accion } = accionData;
        const actions: actionsType = {
            setButtonValue: () => {
                if ('excel' === from) {
                    if ('Exportar Página' === item) {
                        exportExcelPage();
                    } else {
                        exportExcelAll();
                    }
                    modelExcel.value = 'Excel';
                }
            },
            default: () => emit('accion', { item, accion }),
        };
        const fn = actions[accion] || actions.default;
        if ('function' === typeof fn) {
            fn();
        }
    };

    const clearFiler = () => {
        data.meta.filter = '';
        getRefreshData();
    };
    const setPerPage = (per_page: number) => {
        data.meta.page = 1;
        data.meta.per_page = per_page;

        getRefreshData();
    };
    const setFilter = () => {
        data.meta.page = 1;

        getRefreshData();
    };
    const setOrder = (field: string, order: string) => {
        data.meta.page = PAGINATION.DEFAULT_PAGE;

        if (data.meta.order[PAGINATION.ARRAY_FIRST_INDEX] !== field) {
            order = 'asc';
        }

        data.meta.order = [field, order];
        getRefreshData();
    };
    const changePage = (page: number | string) => {
        let goPage = 0;
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

        getRefreshData();
    };
    const getLimitPages = computed(() => {
        const limit = 3;
        const total_pages = data.meta.total_pages;
        const page = data.meta.page;
        const from = page - limit;
        const to = page + limit;

        if (PAGINATION.DEFAULT_PAGE > from) {
            if (total_pages < limit * PAGINATION.PAGES_LIMIT_MULTIPLIER) {
                const arr = Array.from(
                    { length: total_pages },
                    (_, i) => i + PAGINATION.DEFAULT_PAGE,
                );
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
                const arr = Array.from(
                    { length: total_pages },
                    (_, i) => i + PAGINATION.DEFAULT_PAGE,
                );
                return arr;
            }
            const arr = Array.from(
                { length: limit * PAGINATION.PAGES_LIMIT_MULTIPLIER },
                (_, i) => total_pages - i,
            );
            return arr.reverse();
        }

        const arr = Array.from(
            { length: limit * PAGINATION.PAGES_LIMIT_MULTIPLIER },
            (_, i) => from + i,
        );
        return arr;
    });

    const computedActionClass = (action, row) => {
        if ('object' !== typeof action.class) {
            return action.class;
        }

        return action.class.condition_value ===
            row[action.class.condition_field]
            ? action.class.active
            : action.class.inactive;
    };

    //detectar cambios en itemSelected y crear clase para linea de la tabla
    const classLineTable = item => {
        if (multipleSelected.value) {
            if (itemSelected.value?.fieldCompare !== undefined) {
                if (
                    itemSelected.value.itemsSelected.some(
                        (/** @type {any} */ itemSelectedValue) =>
                            itemSelectedValue ===
                            item[itemSelected.value?.fieldCompare],
                    )
                ) {
                    return 'bg-blue-100 border border-blue-500 dark:bg-blue-800 dark:border-blue-700';
                }
            }
            return 'bg-white border-b dark:bg-gray-800 dark:border-gray-700';
        }
        if (
            itemSelected.value?.fieldCompare !== undefined &&
            itemSelected.value[itemSelected.value?.fieldCompare] ===
                item[itemSelected.value?.fieldCompare]
        ) {
            return 'bg-blue-100 border border-blue-500 dark:bg-blue-800 dark:border-blue-700';
        }
        return 'bg-white border-b dark:bg-gray-800 dark:border-gray-700';
    };
</script>

<template>
    <div class="flex flex-col">
        <slot name="buttons"></slot>
        <div
            v-if="showPerPage || showExportExcel || showSearch"
            class="flex items-center justify-between flex-column flex-wrap md:flex-row md:space-y-0 py-2 px-2">
            <!-- Dropdown por Pagina -->
            <dropDown
                v-if="showPerPage"
                v-model="data.meta.per_page"
                key="per_page"
                title="Mostrar"
                from="per_page"
                :list="showPerPages" />

            <!-- Dropdown Exportar Excel -->
            <dropDown
                v-if="showExportExcel"
                @update:model="accion"
                v-model="modelExcel"
                key="excel"
                from="excel"
                :list="['Exportar Página', 'Exportar todo']" />

            <!-- input buscar -->
            <div class="relative" v-if="showSearch">
                <button
                    class="absolute inset-y-0 rtl:inset-r-0 end-0 flex items-center pe-3 cursor-pointer"
                    @click="setFilter"
                    title="Click para buscar">
                    <svg
                        class="w-4 h-4 text-gray-500 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white"
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
                </button>

                <button
                    v-if="data.meta.filter"
                    class="absolute inset-y-0 rtl:inset-r-0 end-5 flex items-center pe-3 cursor-pointer"
                    @click="clearFiler"
                    title="Limpiar filtro">
                    <svg
                        class="w-4 h-4 text-gray-500 dark:text-gray-400 hover:text-orange-900 dark:hover:text-orange-900"
                        fill="none"
                        viewBox="0 0 20 20"
                        xmlns="http://www.w3.org/2000/svg">
                        <path
                            d="M6 18L18 6M6 6l12 12"
                            stroke="currentColor"
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2" />
                    </svg>
                </button>
                <input
                    type="text"
                    class="block p-2 ps-2 text-sm text-gray-900 border border-gray-300 rounded-lg w-80 bg-gray-50 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500"
                    :id="idTable + '_table-search-users'"
                    @keyup.enter="setFilter"
                    placeholder="Ingrese y presione 'Enter' para buscar"
                    v-model="data.meta.filter" />
            </div>
        </div>
        <div class="overflow-x-auto">
            <table
                :id="'table_' + idTable"
                class="w-full text-sm text-left rtl:text-right text-gray-500 dark:text-gray-400">
                <caption
                    v-if="tablaTitle !== ''"
                    class="py-2 px-2 text-lg font-semibold text-gray-900 bg-white dark:text-white dark:bg-gray-800 w-full">
                    <div class="flex justify-between w-full">
                        <div>
                            {{ tablaTitle }}
                        </div>
                        <div>
                            <loader
                                key="loadingData"
                                v-if="loadingData"></loader>
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
                            <div class="flex justify-center">
                                {{ col.title }}
                            </div>
                        </th>
                    </tr>
                </thead>
                <thead
                    class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                    <tr>
                        <!--TODO: descartar columnas atributo visible: false-->
                        <th
                            :class="smallLine ? 'py-1 px-2' : 'py-4 px-3'"
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
                        :class="classLineTable(row)"
                        :key="row.id"
                        v-for="(row, index) in data.data">
                        <td
                            :class="smallLine ? 'py-1 px-2' : 'py-4 px-3'"
                            :key="col.field"
                            v-for="col in data.columns">
                            <!--status-->
                            <div v-if="col.type === 'status'">
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
                            <!--affirmative-->
                            <div
                                v-else-if="col.type === 'affirmative'"
                                class="flex justify-center">
                                <span
                                    class="inline-flex items-center justify-center px-2 py-1 text-xs font-bold leading-none rounded-full"
                                    :class="
                                        row[col.field] === '1' ||
                                        row[col.field] === 'si'
                                            ? 'text-primary-100 bg-primary-600'
                                            : 'text-warning-100 bg-warning-600'
                                    ">
                                    {{
                                        row[col.field] === '1' ||
                                        row[col.field] === 'si'
                                            ? 'Si'
                                            : 'No'
                                    }}
                                </span>
                            </div>
                            <!--svg-->
                            <div
                                class="flex justify-center"
                                v-else-if="col.type == 'svg'">
                                <svg
                                    class="w-[20px] h-[20px] text-gray-800 dark:text-white"
                                    :fill="
                                        row['fill'] === '1'
                                            ? 'currentColor'
                                            : 'none'
                                    "
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
                                        :class="
                                            computedActionClass(action, row)
                                        "
                                        :key="action.id"
                                        :title="action.title"
                                        @click="
                                            accion({
                                                item: row,
                                                accion: action.action,
                                            })
                                        "
                                        v-if="
                                            action.condition &&
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
                                <div
                                    class="flex items-center justify-center gap-1">
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
                                                (index ===
                                                    data.meta.total - 1 &&
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
                            <div :data-row="col.field" v-else>
                                {{ row[col.field] }}
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <nav
            class="grid justify-center lg:content-center lg:flex lg:justify-between my-4 w-full px-2 overflow-auto">
            <span
                class="text-sm font-normal text-gray-500 dark:text-gray-400 h-8 flex items-center gap-1">
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
    </div>
</template>
