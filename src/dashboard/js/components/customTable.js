import { $dom } from '@/dashboard/js/composables/dom.js';
import { createXlsxFromJson } from '@/dashboard/js/composables/useXlsx.js';
import { removeScape, versaFetch } from '@/dashboard/js/functions.js';
import { app } from '@/dashboard/js/vue-instancia.js';
import { html } from '@/vendor/code-tag/code-tag-esm';
import { computed, reactive, ref, watch, watchEffect } from 'vue';

import { loader } from '@/dashboard/js/components/loader.js';
/* eslint-disable */
const l = loader;
/* eslint-enable */

const customTableComponent = {
    emits: ['accion', 'update:totalRegisters'],
    name: 'customTable',
    props: {
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
    },
    model: {
        prop: 'totalRegisters',
        event: 'update:totalRegisters',
    },
    setup(props, { emit: $emit }) {
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
                new URLSearchParams(url.value).get('filter') ??
                data.meta.filter;
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
            $emit('update:totalRegisters', data.meta?.total ?? 0);
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

        return {
            showPerPages,
            getRefreshData,
            data,
            msg,
            idTable,
            exportExcelPage,
            exportExcelAll,
            loading,
            loadingData,
            removeScapeLocal,
        };
    },
    methods: {
        accion({ item, accion }) {
            const actions = {
                default: () => this.$emit('accion', { item, accion }),
            };
            const fn = actions[accion] || actions.default;
            if (typeof fn === 'function') {
                fn();
            }
        },
        getParams() {
            const url = new URL(this.url);
            const params = new URLSearchParams(url.search);

            params.set('page', this.data.meta.page);
            params.set('per_page', this.data.meta.per_page);
            params.set('filter', this.data.meta.filter);

            url.search = params.toString();

            // cambiar la url sin recargar la pagina
            window.history.pushState({}, '', url);
            this.getRefreshData();
        },
        setPerPage(per_page) {
            if (this.data.meta.per_page === per_page) return;
            this.data.meta.page = 1;
            this.data.meta.per_page = per_page;

            this.getRefreshData();

            const dropdownAction = $dom('#dropdownAction');
            if (!(dropdownAction instanceof HTMLElement)) return;
            dropdownAction.classList.remove('show');
            dropdownAction.classList.add('hidden');
            dropdownAction.setAttribute('aria-hidden', 'true');
        },
        setFilter() {
            this.data.meta.page = 1;

            this.getRefreshData();
        },
        setOrder(field, order) {
            this.data.meta.page = 1;

            if (this.data.meta.order[0] !== field) {
                order = 'asc';
            }

            this.data.meta.order = [field, order];
            this.getRefreshData();
        },
        changePage(page) {
            if (page === 'siguiente') {
                if (this.data.meta.page < this.data.meta.total_pages) {
                    page = parseInt(this.data.meta.page) + 1;
                } else {
                    return;
                }
            }
            if (page === 'anterior') {
                if (this.data.meta.page > 1) {
                    page = parseInt(this.data.meta.page) - 1;
                } else {
                    return;
                }
            }

            this.data.meta.page = page;

            this.getRefreshData();
        },
    },
    computed: {
        getLimitPages() {
            const limit = 3;
            const total_pages = this.data.meta.total_pages;
            const page = this.data.meta.page;
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
        },
    },
    template: html`
        <div
            class="flex items-center justify-end flex-column flex-wrap md:flex-row gap-2 px-2 py-2">
            <slot name="buttons"></slot>
        </div>
        <div
            class="flex items-center justify-between flex-column flex-wrap md:flex-row space-y-4 md:space-y-0 py-2 px-2">
            <!-- dropdown mostrar registros -->
            <div>
                <span class="text-gray-500 pe-1">Mostrar</span>
                <button
                    id="dropdownActionButton"
                    type="button"
                    class="inline-flex items-center text-gray-500 bg-white border border-gray-300 focus:outline-none hover:bg-gray-100 focus:ring-4 focus:ring-gray-200 font-medium rounded-lg text-sm px-3 py-1.5 dark:bg-gray-800 dark:text-gray-400 dark:border-gray-600 dark:hover:bg-gray-700 dark:hover:border-gray-600 dark:focus:ring-gray-700"
                    data-dropdown-delay="0"
                    data-dropdown-toggle="dropdownAction"
                    data-dropdown-trigger="click">
                    {{ data.meta.per_page }}
                    <svg
                        class="w-2.5 h-2.5 ms-2.5"
                        aria-hidden="true"
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
                <!-- Dropdown menu -->
                <div
                    id="dropdownAction"
                    class="z-10 hidden bg-white divide-y divide-gray-100 rounded-lg shadow w-44 dark:bg-gray-700 dark:divide-gray-600">
                    <ul
                        class="py-1 text-sm text-gray-700 dark:text-gray-200"
                        aria-labelledby="dropdownActionButton">
                        <li v-for="item in showPerPages">
                            <a
                                class="block px-4 py-2 hover:bg-gray-100 dark:hover:bg-gray-600 dark:hover:text-white"
                                @click="setPerPage(item)">
                                {{ item }}
                            </a>
                        </li>
                    </ul>
                </div>
            </div>

            <!-- dropdown exportar excel-->
            <div class="flex items-center justify-center dropdown">
                <button
                    id="dropdownButton"
                    type="button"
                    class="inline-flex items-center text-gray-500 bg-white border border-gray-300 focus:outline-none hover:bg-gray-100 focus:ring-4 focus:ring-gray-200 font-medium rounded-lg text-sm px-3 py-1.5 dark:bg-gray-800 dark:text-gray-400 dark:border-gray-600 dark:hover:bg-gray-700 dark:hover:border-gray-600 dark:focus:ring-gray-700"
                    :disabled="loading"
                    data-dropdown-delay="0"
                    data-dropdown-toggle="dropdown"
                    data-dropdown-trigger="click">
                    Excel
                    <svg
                        class="w-2.5 h-2.5 ms-2.5"
                        aria-hidden="true"
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
                <!-- Dropdown menu -->
                <div
                    id="dropdown"
                    class="z-10 hidden bg-white divide-y divide-gray-100 rounded-lg shadow w-44 dark:bg-gray-700 dark:divide-gray-600">
                    <ul
                        class="py-1 text-sm text-gray-700 dark:text-gray-200"
                        aria-labelledby="dropdownButton">
                        <li class="block px-4 py-2 hover:bg-gray-100 dark:hover:bg-gray-600 dark:hover:text-white cursor-pointer hover:rounded-lg" @click="exportExcelPage">
                            Exportar Pagina
                        </li>
                        <li class="block px-4 py-2 hover:bg-gray-100 dark:hover:bg-gray-600 dark:hover:text-white cursor-pointer hover:rounded-lg" @click="exportExcelAll">
                            Exportar Todo
                        </li>
                    </ul>
                </div>
                <loader key="loadingExcel" v-if="loading"></loader>
            </div>

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
                    :id="idTable+'_table-search-users'"
                    @keyup.enter="setFilter"
                    placeholder="Ingrese y presione 'Enter' para buscar"
                    v-model="data.meta.filter" />
            </div>
        </div>
        <div class="px-2">
            <table class="w-full text-sm text-left rtl:text-right text-gray-500 dark:text-gray-400">
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
                                @click="setOrder(col.field, data.meta.order[1] === 'asc' ? 'desc':'asc')" title="Ordenar"
                                v-if="col.type !== 'actions' && col.type !== 'file' && col.type !== 'position'">
                                <svg
                                    class="w-6 h-6"
                                    aria-hidden="true"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                    xmlns="http://www.w3.org/2000/svg">
                                    <path
                                        :d="data.meta.order[0] === col.field && data.meta.order[1] === 'asc' ? 'M12 14l-4-4-4 4M12 10v8':'M12 10l4 4 4-4M12 14v-8'"
                                        :fill="data.meta.order[0] === col.field ? 'currentColor':'currentColor'"
                                        :stroke="data.meta.order[0] === col.field ? 'currentColor':'currentColor'"
                                        stroke-width="1"
                                        />
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
                        v-for="(row,index) in data.data">
                        <td
                            class="px-4 py-4"
                            :key="col.field"
                            v-for="col in data.columns">
                            <!--status-->
                            <div v-if="col.type == 'status'">
                                <span
                                    class="inline-flex items-center justify-center px-2 py-1 text-xs font-bold leading-none rounded-full" :class="row[col.field] === '1' || row[col.field] === 'activo' ? 'text-green-100 bg-green-600':'text-red-100 bg-red-600'">
                                    {{ row[col.field] === '1' || row[col.field] === 'activo' ? 'Activo':'Inactivo' }}
                                </span>
                            </div>
                            <!--svg-->
                            <div class="flex justify-center" v-else-if="col.type == 'svg'">
                                <svg class="w-[20px] h-[20px] text-gray-800 dark:text-white" aria-hidden="true" fill="currentColor" height="24" v-html="removeScapeLocal(row[col.field])" viewBox="0 0 24 24" width="24" xmlns="http://www.w3.org/2000/svg">
                                </svg>
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
                                        @click="accion({item: row, accion: action.action})"
                                        v-if="action.condition_value == row[action.condition]">
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
                                            @click="accion({item: {...row, direction: action.type}, accion: action.action})"
                                            v-if="(index === 0 && row[col.field] == 1 && action.type === 'up' ? false:true ) &&
                                                (index ===  data.meta.total-1 && action.type === 'down' ? false:true ) ">
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
            <span
                class="text-sm font-normal text-gray-500 dark:text-gray-400 h-8">
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
                        :class="1 == data.meta.page ? 'cursor-not-allowed':'cursor-pointer'"
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

                <li
                    v-if="data.meta.page < data.meta.total_pages ">
                    <a class="flex items-center justify-center px-3 h-8 leading-tight text-gray-500 bg-white border border-gray-300 hover:bg-gray-100 hover:text-gray-700 dark:bg-gray-800 dark:border-gray-700 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white cursor-not-allowed">...</a>
                </li>
                <li
                    v-if="data.meta.page < data.meta.total_pages && getLimitPages[getLimitPages.length-1] != data.meta.total_pages">
                    <a
                        class="flex items-center justify-center px-3 h-8 leading-tight text-gray-500 bg-white border border-gray-300 hover:bg-gray-100 hover:text-gray-700 dark:bg-gray-800 dark:border-gray-700 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white cursor-pointer""
                        @click="changePage(data.meta.total_pages)">
                        {{ data.meta.total_pages }}
                    </a>
                </li>

                <li>
                    <a
                        class="flex items-center justify-center px-3 h-8 leading-tight text-gray-500 bg-white border border-gray-300 rounded-e-lg hover:bg-gray-100 hover:text-gray-700 dark:bg-gray-800 dark:border-gray-700 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white"
                        :class="data.meta.total_pages <= data.meta.page || data.meta.total_pages == undefined ? 'cursor-not-allowed':'cursor-pointer'"
                        @click="changePage('siguiente')">
                        Siguiente
                    </a>
                </li>
            </ul>
        </nav>
    `,
};

export const customTable = app.component('customTable', customTableComponent);
