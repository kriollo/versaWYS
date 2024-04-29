'use strict';
import { html } from '@/vendor/code-tag/code-tag-esm.js';
// @ts-ignore
import { computed, reactive, ref, watch, watchEffect } from 'vue';
import { $, versaFetch } from '../functions.js';

export const customTable = {
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

        const showPerPages = [1, 5, 10, 25, 50, 100];
        const resumeData = reactive({
            total: 0,
            per_page: 15,
            page: 1,
            total_pages: 0,
            filter: '',
            from: 0,
            to: 0,
            order: ['Id', 'asc'],
        });
        const data = reactive({
            data: [],
            columns: [],
        });

        const getRefreshData = () => {
            const page =
                new URLSearchParams(url.value).get('page') ?? resumeData.page;
            const per_page =
                new URLSearchParams(url.value).get('per_page') ??
                resumeData.per_page;
            const filter =
                new URLSearchParams(url.value).get('filter') ??
                resumeData.filter;
            const order =
                new URLSearchParams(url.value).get('order') ?? resumeData.order;

            versaFetch({
                url: `${url.value}?page=${page}&per_page=${per_page}&filter=${filter}&order=${order}&externalFilters=${externalFilters.value}`,
                method: 'GET',
            }).then(response => {
                data.data = [];
                data.columns = [];
                if (response.success === 1) {
                    data.data = response.data;
                    data.columns = response.columns;

                    resumeData.total = response.total;
                    resumeData.total_pages = response.total_pages;
                    resumeData.from = response.from;
                    resumeData.to = response.to;
                    resumeData.filter = response.filter;

                    if (data.data.length === 0) {
                        msg.value = 'No hay registros para mostrar';
                    }
                } else {
                    msg.value = response.message;
                }
            });
        };

        watch(url, () => {
            getRefreshData();
        });

        watch(
            () => refresh.value,
            () => {
                getRefreshData();
            },
            { immediate: true }
        );

        watchEffect(() => {
            $emit('update:totalRegisters', data.total ?? 0);
        });

        return {
            showPerPages,
            resumeData,
            getRefreshData,
            data,
            msg,
            idTable,
        };
    },
    methods: {
        accion({ item, accion }) {
            this.$emit('accion', { item, accion });
        },
        getParams() {
            const url = new URL(this.url);
            const params = new URLSearchParams(url.search);

            params.set('page', this.resumeData.page);
            params.set('per_page', this.resumeData.per_page);
            params.set('filter', this.resumeData.filter);

            url.search = params.toString();

            // cambiar la url sin recargar la pagina
            window.history.pushState({}, '', url);
            this.getRefreshData();
        },
        setPerPage(per_page) {
            if (this.resumeData.per_page === per_page) return;
            this.resumeData.page = 1;
            this.resumeData.per_page = per_page;

            this.getRefreshData();

            const dropdownAction = $('#dropdownAction');
            if (!(dropdownAction instanceof HTMLElement)) return;
            dropdownAction.classList.remove('show');
            dropdownAction.classList.add('hidden');
            dropdownAction.setAttribute('aria-hidden', 'true');
        },
        setFilter() {
            this.resumeData.page = 1;

            this.getRefreshData();
        },
        changePage(page) {
            if (page === 'siguiente') {
                if (this.resumeData.page < this.resumeData.total_pages) {
                    page = parseInt(this.resumeData.page) + 1;
                } else {
                    return;
                }
            }
            if (page === 'anterior') {
                if (this.resumeData.page > 1) {
                    page = parseInt(this.resumeData.page) - 1;
                } else {
                    return;
                }
            }

            this.resumeData.page = page;

            this.getRefreshData();
        },
    },
    computed: {
        getLimitPages() {
            const limit = 3;
            const total_pages = this.resumeData.total_pages;
            const page = this.resumeData.page;
            const from = page - limit;
            const to = page + limit;

            if (from < 1) {
                if (total_pages < limit * 2) {
                    const arr = Array.from(
                        { length: total_pages },
                        (_, i) => i + 1
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
                        (_, i) => i + 1
                    );
                    return arr;
                }
                const arr = Array.from(
                    { length: limit * 2 },
                    (_, i) => total_pages - i
                );
                return arr.reverse();
            }

            const arr = Array.from({ length: limit * 2 }, (_, i) => from + i);
            return arr;
        },
    },
    template: html`
        <div
            class="flex items-center justify-end flex-column flex-wrap md:flex-row space-y-4 md:space-y-0">
            <slot name="buttons"></slot>
        </div>
        <div
            class="flex items-center justify-between flex-column flex-wrap md:flex-row space-y-4 md:space-y-0 py-2">
            <div>
                <span class="text-gray-500 pe-1">Mostrar</span>
                <button
                    id="dropdownActionButton"
                    data-dropdown-toggle="dropdownAction"
                    data-dropdown-delay="0"
                    data-dropdown-trigger="click"
                    class="inline-flex items-center text-gray-500 bg-white border border-gray-300 focus:outline-none hover:bg-gray-100 focus:ring-4 focus:ring-gray-200 font-medium rounded-lg text-sm px-3 py-1.5 dark:bg-gray-800 dark:text-gray-400 dark:border-gray-600 dark:hover:bg-gray-700 dark:hover:border-gray-600 dark:focus:ring-gray-700"
                    type="button">
                    {{ resumeData.per_page }}
                    <svg
                        class="w-2.5 h-2.5 ms-2.5"
                        aria-hidden="true"
                        xmlns="http://www.w3.org/2000/svg"
                        fill="none"
                        viewBox="0 0 10 6">
                        <path
                            stroke="currentColor"
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="m1 1 4 4 4-4" />
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
                                @click="setPerPage(item)"
                                class="block px-4 py-2 hover:bg-gray-100 dark:hover:bg-gray-600 dark:hover:text-white">
                                {{ item }}
                            </a>
                        </li>
                    </ul>
                </div>
            </div>

            <div class="relative">
                <div
                    class="absolute inset-y-0 rtl:inset-r-0 end-0 flex items-center pe-3 pointer-events-none">
                    <svg
                        class="w-4 h-4 text-gray-500 dark:text-gray-400"
                        aria-hidden="true"
                        xmlns="http://www.w3.org/2000/svg"
                        fill="none"
                        viewBox="0 0 20 20">
                        <path
                            stroke="currentColor"
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="m19 19-4-4m0-7A7 7 0 1 1 1 8a7 7 0 0 1 14 0Z" />
                    </svg>
                </div>
                <input
                    type="text"
                    :id="idTable+'_table-search-users'"
                    @keyup.enter="setFilter"
                    v-model="resumeData.filter"
                    class="block p-2 ps-6 text-sm text-gray-900 border border-gray-300 rounded-lg w-80 bg-gray-50 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500"
                    placeholder="Ingrese y presione 'Enter' para buscar" />
            </div>
        </div>

        <table
            class="w-full text-sm text-left rtl:text-right text-gray-500 dark:text-gray-400">
            <caption
                v-html="tablaTitle"
                class="py-2 text-lg font-semibold text-left rtl:text-right text-gray-900 bg-white dark:text-white dark:bg-gray-800"></caption>
            <thead
                class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                <tr>
                    <th
                        scope="col"
                        class="px-6 py-3"
                        v-for="col in data.columns">
                        {{ col.title }}
                    </th>
                </tr>
            </thead>
            <tbody>
                <tr v-if="data.data.length === 0" class="text-center">
                    <td :colspan="data.columns.length"><span class="text-xl" v-html="msg"></span></td>
                </tr>
                <tr
                    v-for="row in data.data"
                    :key="row.id"
                    class="bg-white border-b dark:bg-gray-800 dark:border-gray-700">
                    <td
                        v-for="col in data.columns"
                        :key="col.field"
                        class="px-6 py-4">
                        <div v-if="col.type == 'status'">
                            <span
                                v-if="row[col.field] == '1'"
                                class="inline-flex items-center justify-center px-2 py-1 text-xs font-bold leading-none text-green-100 bg-green-600 rounded-full">
                                Activo
                            </span>
                            <span
                                v-else-if="row[col.field] == '0'"
                                class="inline-flex items-center justify-center px-2 py-1 text-xs font-bold leading-none text-red-100 bg-red-600 rounded-full">
                                Inactivo
                            </span>
                        </div>
                        <div
                            v-else-if="col.type == 'actions'"
                            class="flex justify-end gap-2">
                            <div v-for="action in col.buttons">
                                <button
                                    v-if="action.condition_value == row[action.condition]"
                                    :key="action.id"
                                    :class="action.class"
                                    @click="accion({item: row, accion: action.action})"
                                    :title="action.title">
                                    <i :class="action.icon"></i>
                                </button>
                            </div>
                        </div>
                        <div v-else>{{ row[col.field] }}</div>
                    </td>
                </tr>
            </tbody>
        </table>

        <nav
            class="flex justify-between flex-column flex-wrap md:flex-row my-4 w-full">
            <span
                class="text-sm font-normal text-gray-500 dark:text-gray-400 h-8">
                Monstrando
                <span class="font-semibold text-gray-900 dark:text-white">
                    {{ resumeData.from }} al {{ resumeData.to }}
                </span>
                de
                <span class="font-semibold text-gray-900 dark:text-white">
                    {{ resumeData.total }}
                </span>
                Resultados
            </span>

            <ul class="inline-flex -space-x-px rtl:space-x-reverse text-sm h-8">
                <li>
                    <a
                        @click="changePage('anterior')"
                        class="flex items-center justify-center px-3 h-8 ms-0 leading-tight text-gray-500 bg-white border border-gray-300 rounded-s-lg hover:bg-gray-100 hover:text-gray-700 dark:bg-gray-800 dark:border-gray-700 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white"
                        :class="1 == resumeData.page ? 'cursor-not-allowed':'cursor-pointer'">
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
                        v-if="resumeData.page == page"
                        @click="changePage(page)"
                        aria-current="page"
                        class="flex items-center justify-center px-3 h-8 text-blue-600 border border-gray-300 bg-blue-50 hover:bg-blue-100 hover:text-blue-700 dark:border-gray-700 dark:bg-gray-700 dark:text-white cursor-pointer">
                        {{ page }}
                    </a>
                    <a
                        v-else
                        @click="changePage(page)"
                        class="flex items-center justify-center px-3 h-8 leading-tight text-gray-500 bg-white border border-gray-300 hover:bg-gray-100 hover:text-gray-700 dark:bg-gray-800 dark:border-gray-700 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white cursor-pointer">
                        {{ page }}
                    </a>
                </li>

                <li
                    v-if="resumeData.page < resumeData.total_pages ">
                    <a class="flex items-center justify-center px-3 h-8 leading-tight text-gray-500 bg-white border border-gray-300 hover:bg-gray-100 hover:text-gray-700 dark:bg-gray-800 dark:border-gray-700 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white cursor-not-allowed">...</a>
                </li>
                <li
                    v-if="resumeData.page < resumeData.total_pages && getLimitPages[getLimitPages.length-1] != resumeData.total_pages">
                    <a
                        class="flex items-center justify-center px-3 h-8 leading-tight text-gray-500 bg-white border border-gray-300 hover:bg-gray-100 hover:text-gray-700 dark:bg-gray-800 dark:border-gray-700 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white cursor-pointer""
                        @click="changePage(resumeData.total_pages)">
                        {{ resumeData.total_pages }}
                    </a>
                </li>

                <li>
                    <a
                        @click="changePage('siguiente')"
                        class="flex items-center justify-center px-3 h-8 leading-tight text-gray-500 bg-white border border-gray-300 rounded-e-lg hover:bg-gray-100 hover:text-gray-700 dark:bg-gray-800 dark:border-gray-700 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white"
                        :class="resumeData.total_pages <= resumeData.page || resumeData.total_pages == undefined ? 'cursor-not-allowed':'cursor-pointer'">
                        Siguiente
                    </a>
                </li>
            </ul>
        </nav>
    `,
};
