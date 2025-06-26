import { useSlots as _useSlots, useModel as _useModel, mergeModels as _mergeModels, defineComponent as _defineComponent } from '/node_modules/vue/dist/vue.esm-browser.js';
import { computed, onMounted, reactive, ref, toRefs, watch, watchEffect, } from '/node_modules/vue/dist/vue.esm-browser.js';
import dropDown from '@/dashboard/js/components/dropDown.vue';
import loader from '@/dashboard/js/components/loader.vue';
import { createXlsxFromJson } from '@/dashboard/js/composables/useXlsx';
import { API_RESPONSE_CODES, GLOBAL_CONSTANTS, PAGINATION, } from '@/dashboard/js/constants';
import { removeScape, versaFetch } from '@/dashboard/js/functions';
const customTable_component = /*@__PURE__*/ _defineComponent({
    __file: 'C:/Users/jjara/Desktop/proyectos/versaWYS-PHP/src/dashboard/js/components/customTable.vue',
    __name: 'customTable',
    name: 'customTable',
    components: {
        dropDown,
        loader
    },
    props: /*@__PURE__*/ _mergeModels({
        id: { type: String, required: false, default: 'table' },
        tablaTitle: { type: String, required: false, default: '' },
        urlData: { type: String, required: true },
        refreshData: { type: Boolean, required: false, default: false },
        totalRegisters: { type: Number, required: false },
        externalFilters: { type: String, required: false, default: '' },
        fieldOrder: { type: String, required: false, default: 'id' },
        PerPage: { type: Number, required: false },
        showPerPage: { type: Boolean, required: false, default: true },
        showExportExcel: { type: Boolean, required: false, default: true },
        showSearch: { type: Boolean, required: false, default: true },
        itemSelected: { type: null, required: false },
        smallLine: { type: Boolean, required: false, default: false },
        multipleSelected: { type: Boolean, required: false, default: false },
        perPage: { type: Number, required: false, default: 25 }
    }, {
        "modelValue": {},
        "modelModifiers": {},
    }),
    emits: /*@__PURE__*/ _mergeModels(['accion', 'update:totalRegisters'], ["update:modelValue"]),
    setup(__props, { expose: __expose, emit: __emit }) {
        __expose();
        const versaComponentKey = ref(0);
        const props = __props;
        const tablaTitle = computed(() => props.tablaTitle);
        const emit = __emit;
        const slots = _useSlots();
        const msg = ref('Cargando...');
        const url = computed(() => props.urlData);
        const refresh = computed(() => props.refreshData);
        const externalFilters = computed(() => props.externalFilters);
        const idTable = computed(() => props.id);
        const fieldOrder = computed(() => props.fieldOrder);
        const perPage = computed(() => props.perPage);
        const { showPerPage, showExportExcel, showSearch, itemSelected, smallLine, multipleSelected, } = toRefs(props);
        const loading = ref(false);
        const loadingData = ref(false);
        const showPerPages = [...PAGINATION.PER_PAGE_OPTIONS];
        const data = reactive({
            data: [],
            columns: [],
            colspan: [],
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
        watch(() => data.meta.per_page, () => {
            setPerPage(data.meta.per_page);
        });
        const getRefreshData = async () => {
            loadingData.value = true;
            const page = new URLSearchParams(url.value).get('page') ?? data.meta.page;
            const per_page = new URLSearchParams(url.value).get('per_page') ??
                data.meta.per_page;
            const filter = new URLSearchParams(url.value).get('filter') ?? data.meta.filter;
            const order = new URLSearchParams(url.value).get('order') ?? data.meta.order;
            const response = (await versaFetch({
                url: `${url.value}?page=${page}&per_page=${per_page}&filter=${filter}&order=${order}&externalFilters=${externalFilters.value}`,
                method: 'GET',
            }));
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
            }
            else {
                msg.value = response.message;
            }
            loadingData.value = false;
        };
        watch(url, () => {
            getRefreshData();
        });
        watch(() => refresh.value, () => {
            getRefreshData();
        });
        onMounted(() => {
            getRefreshData();
        });
        const model = _useModel(__props, "modelValue");
        watchEffect(() => {
            model.value = data.meta.total;
        });
        const exportExcelPage = async () => {
            loading.value = true;
            const newDataExport = data.data.map((item) => {
                const newItem = {};
                data.columns.forEach((col) => {
                    if (col?.export) {
                        newItem[col.field] = item[col.field];
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
            const newData = response.data.map((item) => {
                const newItem = {};
                data.columns.forEach((col) => {
                    if (col?.export) {
                        newItem[col.field] = item[col.field];
                    }
                });
                return newItem;
            });
            await createXlsxFromJson(newData, idTable.value);
            loading.value = false;
        };
        const removeScapeLocal = (str) => removeScape(str);
        const modelExcel = ref('Excel');
        const accion = (accionData) => {
            const { item, from, accion } = accionData;
            const actions = {
                setButtonValue: () => {
                    if ('excel' === from) {
                        if ('Exportar Página' === item) {
                            exportExcelPage();
                        }
                        else {
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
        const setPerPage = (per_page) => {
            data.meta.page = 1;
            data.meta.per_page = per_page;
            getRefreshData();
        };
        const setFilter = () => {
            data.meta.page = 1;
            getRefreshData();
        };
        const setOrder = (field, order) => {
            data.meta.page = PAGINATION.DEFAULT_PAGE;
            if (data.meta.order[PAGINATION.ARRAY_FIRST_INDEX] !== field) {
                order = 'asc';
            }
            data.meta.order = [field, order];
            getRefreshData();
        };
        const changePage = (page) => {
            let goPage = 0;
            if ('siguiente' === page) {
                if (data.meta.page < data.meta.total_pages) {
                    goPage = data.meta.page + PAGINATION.DEFAULT_PAGE;
                }
                else {
                    return;
                }
            }
            if ('anterior' === page) {
                if (PAGINATION.DEFAULT_PAGE < data.meta.page) {
                    goPage = data.meta.page - PAGINATION.DEFAULT_PAGE;
                }
                else {
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
                    const arr = Array.from({ length: total_pages }, (_, i) => i + PAGINATION.DEFAULT_PAGE);
                    return arr;
                }
                const arr = Array.from({ length: limit * PAGINATION.PAGES_LIMIT_MULTIPLIER }, (_, i) => i + PAGINATION.DEFAULT_PAGE);
                return arr;
            }
            if (to > total_pages) {
                if (total_pages < limit * PAGINATION.PAGES_LIMIT_MULTIPLIER) {
                    const arr = Array.from({ length: total_pages }, (_, i) => i + PAGINATION.DEFAULT_PAGE);
                    return arr;
                }
                const arr = Array.from({ length: limit * PAGINATION.PAGES_LIMIT_MULTIPLIER }, (_, i) => total_pages - i);
                return arr.reverse();
            }
            const arr = Array.from({ length: limit * PAGINATION.PAGES_LIMIT_MULTIPLIER }, (_, i) => from + i);
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
                    if (itemSelected.value.itemsSelected.some((/** @type {any} */ itemSelectedValue) => itemSelectedValue ===
                        item[itemSelected.value?.fieldCompare])) {
                        return 'bg-blue-100 border border-blue-500 dark:bg-blue-800 dark:border-blue-700';
                    }
                }
                return 'bg-white border-b dark:bg-gray-800 dark:border-gray-700';
            }
            if (itemSelected.value?.fieldCompare !== undefined &&
                itemSelected.value[itemSelected.value?.fieldCompare] ===
                    item[itemSelected.value?.fieldCompare]) {
                return 'bg-blue-100 border border-blue-500 dark:bg-blue-800 dark:border-blue-700';
            }
            return 'bg-white border-b dark:bg-gray-800 dark:border-gray-700';
        };
        const __returned__ = { versaComponentKey, props, tablaTitle, emit, slots, msg, url, refresh, externalFilters, idTable, fieldOrder, perPage, showPerPage, showExportExcel, showSearch, itemSelected, smallLine, multipleSelected, loading, loadingData, showPerPages, data, getRefreshData, model, exportExcelPage, exportExcelAll, removeScapeLocal, modelExcel, accion, clearFiler, setPerPage, setFilter, setOrder, changePage, getLimitPages, computedActionClass, classLineTable, dropDown, loader };
        Object.defineProperty(__returned__, '__isScriptSetup', { enumerable: false, value: true });
        return __returned__;
    }
});
import { renderSlot as _renderSlot, createCommentVNode as _createCommentVNode, resolveComponent as _resolveComponent, openBlock as _openBlock, createBlock as _createBlock, createElementVNode as _createElementVNode, createElementBlock as _createElementBlock, withKeys as _withKeys, vModelText as _vModelText, withDirectives as _withDirectives, toDisplayString as _toDisplayString, renderList as _renderList, Fragment as _Fragment, createTextVNode as _createTextVNode, normalizeClass as _normalizeClass } from "/node_modules/vue/dist/vue.esm-browser.js";
function render_customTable_component(_ctx, _cache, $props, $setup, $data, $options) {
    const _component_dropDown = _resolveComponent("dropDown");
    const _component_loader = _resolveComponent("loader");
    return (_openBlock(), _createElementBlock("div", {
        class: "flex flex-col",
        key: $setup.versaComponentKey
    }, [
        _renderSlot(_ctx.$slots, "buttons"),
        ($setup.showPerPage || $setup.showExportExcel || $setup.showSearch)
            ? (_openBlock(), _createElementBlock("div", {
                key: 0,
                class: "flex items-center justify-between flex-column flex-wrap md:flex-row md:space-y-0 py-2 px-2"
            }, [
                _createCommentVNode(" Dropdown por Pagina "),
                ($setup.showPerPage)
                    ? (_openBlock(), _createBlock(_component_dropDown, {
                        modelValue: $setup.data.meta.per_page,
                        "onUpdate:modelValue": $event => (($setup.data.meta.per_page) = $event),
                        key: "per_page",
                        title: "Mostrar",
                        from: "per_page",
                        list: $setup.showPerPages
                    }, null, 8 /* PROPS */, ["modelValue", "onUpdate:modelValue"]))
                    : _createCommentVNode("v-if", true),
                _createCommentVNode(" Dropdown Exportar Excel "),
                ($setup.showExportExcel)
                    ? (_openBlock(), _createBlock(_component_dropDown, {
                        "onUpdate:model": $setup.accion,
                        modelValue: $setup.modelExcel,
                        "onUpdate:modelValue": $event => (($setup.modelExcel) = $event),
                        key: "excel",
                        from: "excel",
                        list: ['Exportar Página', 'Exportar todo']
                    }, null, 8 /* PROPS */, ["modelValue", "onUpdate:modelValue"]))
                    : _createCommentVNode("v-if", true),
                _createCommentVNode(" input buscar "),
                ($setup.showSearch)
                    ? (_openBlock(), _createElementBlock("div", {
                        key: 2,
                        class: "relative"
                    }, [
                        _createElementVNode("button", {
                            class: "absolute inset-y-0 rtl:inset-r-0 end-0 flex items-center pe-3 cursor-pointer",
                            onClick: $setup.setFilter,
                            title: "Click para buscar"
                        }, [
                            (_openBlock(), _createElementBlock("svg", {
                                class: "w-4 h-4 text-gray-500 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white",
                                fill: "none",
                                viewBox: "0 0 20 20",
                                xmlns: "http://www.w3.org/2000/svg"
                            }, [
                                _createElementVNode("path", {
                                    d: "m19 19-4-4m0-7A7 7 0 1 1 1 8a7 7 0 0 1 14 0Z",
                                    stroke: "currentColor",
                                    "stroke-linecap": "round",
                                    "stroke-linejoin": "round",
                                    "stroke-width": "2"
                                })
                            ]))
                        ]),
                        ($setup.data.meta.filter)
                            ? (_openBlock(), _createElementBlock("button", {
                                key: 0,
                                class: "absolute inset-y-0 rtl:inset-r-0 end-5 flex items-center pe-3 cursor-pointer",
                                onClick: $setup.clearFiler,
                                title: "Limpiar filtro"
                            }, [
                                (_openBlock(), _createElementBlock("svg", {
                                    class: "w-4 h-4 text-gray-500 dark:text-gray-400 hover:text-orange-900 dark:hover:text-orange-900",
                                    fill: "none",
                                    viewBox: "0 0 20 20",
                                    xmlns: "http://www.w3.org/2000/svg"
                                }, [
                                    _createElementVNode("path", {
                                        d: "M6 18L18 6M6 6l12 12",
                                        stroke: "currentColor",
                                        "stroke-linecap": "round",
                                        "stroke-linejoin": "round",
                                        "stroke-width": "2"
                                    })
                                ]))
                            ]))
                            : _createCommentVNode("v-if", true),
                        _withDirectives(_createElementVNode("input", {
                            type: "text",
                            class: "block p-2 ps-2 text-sm text-gray-900 border border-gray-300 rounded-lg w-80 bg-gray-50 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500",
                            id: $setup.idTable + '_table-search-users',
                            onKeyup: _withKeys($setup.setFilter, ["enter"]),
                            placeholder: "Ingrese y presione 'Enter' para buscar",
                            "onUpdate:modelValue": $event => (($setup.data.meta.filter) = $event)
                        }, null, 40 /* PROPS, NEED_HYDRATION */, ["id", "onUpdate:modelValue"]), [
                            [_vModelText, $setup.data.meta.filter]
                        ])
                    ]))
                    : _createCommentVNode("v-if", true)
            ]))
            : _createCommentVNode("v-if", true),
        _createElementVNode("div", { class: "overflow-x-auto" }, [
            _createElementVNode("table", {
                id: 'table_' + $setup.idTable,
                class: "w-full text-sm text-left rtl:text-right text-gray-500 dark:text-gray-400"
            }, [
                ($setup.tablaTitle !== '')
                    ? (_openBlock(), _createElementBlock("caption", {
                        key: 0,
                        class: "py-2 px-2 text-lg font-semibold text-gray-900 bg-white dark:text-white dark:bg-gray-800 w-full"
                    }, [
                        _createElementVNode("div", { class: "flex justify-between w-full" }, [
                            _createElementVNode("div", null, _toDisplayString($setup.tablaTitle), 1 /* TEXT */),
                            _createElementVNode("div", null, [
                                ($setup.loadingData)
                                    ? (_openBlock(), _createBlock(_component_loader, { key: "loadingData" }))
                                    : _createCommentVNode("v-if", true)
                            ])
                        ])
                    ]))
                    : _createCommentVNode("v-if", true),
                _createElementVNode("thead", { class: "text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400" }, [
                    _createElementVNode("tr", null, [
                        (_openBlock(true), _createElementBlock(_Fragment, null, _renderList($setup.data.colspan, (col) => {
                            return (_openBlock(), _createElementBlock("th", {
                                colspan: col.col,
                                scope: "colspan"
                            }, [
                                _createElementVNode("div", { class: "flex justify-center" }, _toDisplayString(col.title), 1 /* TEXT */)
                            ], 8 /* PROPS */, ["colspan"]));
                        }), 256 /* UNKEYED_FRAGMENT */))
                    ])
                ]),
                _createElementVNode("thead", { class: "text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400" }, [
                    _createElementVNode("tr", null, [
                        _createCommentVNode("TODO: descartar columnas atributo visible: false"),
                        (_openBlock(true), _createElementBlock(_Fragment, null, _renderList($setup.data.columns, (col) => {
                            return (_openBlock(), _createElementBlock("th", {
                                class: _normalizeClass($setup.smallLine ? 'py-1 px-2' : 'py-4 px-3'),
                                scope: "col"
                            }, [
                                _createTextVNode(_toDisplayString(col.title) + " ", 1 /* TEXT */),
                                (col.type !== 'actions' &&
                                    col.type !== 'file' &&
                                    col.type !== 'position')
                                    ? (_openBlock(), _createElementBlock("button", {
                                        key: 0,
                                        class: "inline-flex items-center justify-center w-6 h-6 ms-1 text-gray-500 dark:text-gray-400",
                                        onClick: $event => ($setup.setOrder(col.field, $setup.data.meta.order[1] === 'asc'
                                            ? 'desc'
                                            : 'asc')),
                                        title: "Ordenar"
                                    }, [
                                        (_openBlock(), _createElementBlock("svg", {
                                            class: "w-6 h-6",
                                            fill: "none",
                                            viewBox: "0 0 24 24",
                                            xmlns: "http://www.w3.org/2000/svg"
                                        }, [
                                            _createElementVNode("path", {
                                                d: $setup.data.meta.order[0] === col.field &&
                                                    $setup.data.meta.order[1] === 'asc'
                                                    ? 'M12 14l-4-4-4 4M12 10v8'
                                                    : 'M12 10l4 4 4-4M12 14v-8',
                                                fill: $setup.data.meta.order[0] === col.field
                                                    ? 'currentColor'
                                                    : 'currentColor',
                                                stroke: $setup.data.meta.order[0] === col.field
                                                    ? 'currentColor'
                                                    : 'currentColor',
                                                "stroke-width": "1"
                                            }, null, 8 /* PROPS */, ["d", "fill", "stroke"])
                                        ]))
                                    ], 8 /* PROPS */, ["onClick"]))
                                    : _createCommentVNode("v-if", true)
                            ], 2 /* CLASS */));
                        }), 256 /* UNKEYED_FRAGMENT */))
                    ])
                ]),
                _createElementVNode("tbody", null, [
                    ($setup.data.data.length === 0)
                        ? (_openBlock(), _createElementBlock("tr", {
                            key: 0,
                            class: "text-center"
                        }, [
                            _createElementVNode("td", {
                                colspan: $setup.data.columns.length
                            }, [
                                _createElementVNode("span", {
                                    class: "text-xl",
                                    innerHTML: $setup.msg
                                }, null, 8 /* PROPS */, ["innerHTML"])
                            ], 8 /* PROPS */, ["colspan"])
                        ]))
                        : _createCommentVNode("v-if", true),
                    (_openBlock(true), _createElementBlock(_Fragment, null, _renderList($setup.data.data, (row, index) => {
                        return (_openBlock(), _createElementBlock("tr", {
                            class: _normalizeClass($setup.classLineTable(row)),
                            key: row.id
                        }, [
                            (_openBlock(true), _createElementBlock(_Fragment, null, _renderList($setup.data.columns, (col) => {
                                return (_openBlock(), _createElementBlock("td", {
                                    class: _normalizeClass($setup.smallLine ? 'py-1 px-2' : 'py-4 px-3'),
                                    key: col.field
                                }, [
                                    _createCommentVNode("status"),
                                    (col.type === 'status')
                                        ? (_openBlock(), _createElementBlock("div", { key: 0 }, [
                                            _createElementVNode("span", {
                                                class: _normalizeClass(["inline-flex items-center justify-center px-2 py-1 text-xs font-bold leading-none rounded-full",
                                                    row[col.field] === '1' ||
                                                        row[col.field] === 'activo'
                                                        ? 'text-green-100 bg-green-600'
                                                        : 'text-red-100 bg-red-600'
                                                ])
                                            }, _toDisplayString(row[col.field] === '1' ||
                                                row[col.field] === 'activo'
                                                ? 'Activo'
                                                : 'Inactivo'), 3 /* TEXT, CLASS */)
                                        ]))
                                        : (col.type === 'affirmative')
                                            ? (_openBlock(), _createElementBlock(_Fragment, { key: 1 }, [
                                                _createCommentVNode("affirmative"),
                                                _createElementVNode("div", { class: "flex justify-center" }, [
                                                    _createElementVNode("span", {
                                                        class: _normalizeClass(["inline-flex items-center justify-center px-2 py-1 text-xs font-bold leading-none rounded-full",
                                                            row[col.field] === '1' ||
                                                                row[col.field] === 'si'
                                                                ? 'text-primary-100 bg-primary-600'
                                                                : 'text-warning-100 bg-warning-600'
                                                        ])
                                                    }, _toDisplayString(row[col.field] === '1' ||
                                                        row[col.field] === 'si'
                                                        ? 'Si'
                                                        : 'No'), 3 /* TEXT, CLASS */)
                                                ])
                                            ], 2112 /* STABLE_FRAGMENT, DEV_ROOT_FRAGMENT */))
                                            : (col.type == 'svg')
                                                ? (_openBlock(), _createElementBlock(_Fragment, { key: 2 }, [
                                                    _createCommentVNode("svg"),
                                                    _createElementVNode("div", { class: "flex justify-center" }, [
                                                        (_openBlock(), _createElementBlock("svg", {
                                                            class: "w-[20px] h-[20px] text-gray-800 dark:text-white",
                                                            fill: row['fill'] === '1'
                                                                ? 'currentColor'
                                                                : 'none',
                                                            height: "24",
                                                            innerHTML: $setup.removeScapeLocal(row[col.field]),
                                                            viewBox: "0 0 24 24",
                                                            width: "24",
                                                            xmlns: "http://www.w3.org/2000/svg"
                                                        }, null, 8 /* PROPS */, ["fill", "innerHTML"]))
                                                    ])
                                                ], 2112 /* STABLE_FRAGMENT, DEV_ROOT_FRAGMENT */))
                                                : (col.type == 'actions')
                                                    ? (_openBlock(), _createElementBlock(_Fragment, { key: 3 }, [
                                                        _createCommentVNode("actions"),
                                                        _createElementVNode("div", { class: "flex justify-center gap-2" }, [
                                                            (_openBlock(true), _createElementBlock(_Fragment, null, _renderList(col.buttons, (action) => {
                                                                return (_openBlock(), _createElementBlock("div", null, [
                                                                    (action.condition &&
                                                                        action.condition_value ==
                                                                            row[action.condition])
                                                                        ? (_openBlock(), _createElementBlock("button", {
                                                                            class: _normalizeClass($setup.computedActionClass(action, row)),
                                                                            key: action.id,
                                                                            title: action.title,
                                                                            onClick: $event => ($setup.accion({
                                                                                item: row,
                                                                                accion: action.action,
                                                                            }))
                                                                        }, [
                                                                            _createElementVNode("i", {
                                                                                class: _normalizeClass(action.icon)
                                                                            }, null, 2 /* CLASS */)
                                                                        ], 10 /* CLASS, PROPS */, ["title", "onClick"]))
                                                                        : _createCommentVNode("v-if", true)
                                                                ]));
                                                            }), 256 /* UNKEYED_FRAGMENT */))
                                                        ])
                                                    ], 2112 /* STABLE_FRAGMENT, DEV_ROOT_FRAGMENT */))
                                                    : (col.type == 'position')
                                                        ? (_openBlock(), _createElementBlock(_Fragment, { key: 4 }, [
                                                            _createCommentVNode("position"),
                                                            _createElementVNode("div", { class: "flex justify-between" }, [
                                                                _createElementVNode("span", null, _toDisplayString(row[col.field]), 1 /* TEXT */),
                                                                _createElementVNode("div", { class: "flex items-center justify-center gap-1" }, [
                                                                    (_openBlock(true), _createElementBlock(_Fragment, null, _renderList(col.buttons, (action) => {
                                                                        return (_openBlock(), _createElementBlock("div", null, [
                                                                            ((index === 0 &&
                                                                                row[col.field] == 1 &&
                                                                                action.type === 'up'
                                                                                ? false
                                                                                : true) &&
                                                                                (index ===
                                                                                    $setup.data.meta.total - 1 &&
                                                                                    action.type === 'down'
                                                                                    ? false
                                                                                    : true))
                                                                                ? (_openBlock(), _createElementBlock("button", {
                                                                                    class: _normalizeClass(action.class),
                                                                                    key: action.id,
                                                                                    title: action.title,
                                                                                    onClick: $event => ($setup.accion({
                                                                                        item: {
                                                                                            ...row,
                                                                                            direction: action.type,
                                                                                        },
                                                                                        accion: action.action,
                                                                                    }))
                                                                                }, [
                                                                                    _createElementVNode("i", {
                                                                                        class: _normalizeClass(action.icon)
                                                                                    }, null, 2 /* CLASS */)
                                                                                ], 10 /* CLASS, PROPS */, ["title", "onClick"]))
                                                                                : _createCommentVNode("v-if", true)
                                                                        ]));
                                                                    }), 256 /* UNKEYED_FRAGMENT */))
                                                                ])
                                                            ])
                                                        ], 2112 /* STABLE_FRAGMENT, DEV_ROOT_FRAGMENT */))
                                                        : (_openBlock(), _createElementBlock(_Fragment, { key: 5 }, [
                                                            _createCommentVNode("others"),
                                                            _createElementVNode("div", {
                                                                "data-row": col.field
                                                            }, _toDisplayString(row[col.field]), 9 /* TEXT, PROPS */, ["data-row"])
                                                        ], 2112 /* STABLE_FRAGMENT, DEV_ROOT_FRAGMENT */))
                                ], 2 /* CLASS */));
                            }), 128 /* KEYED_FRAGMENT */))
                        ], 2 /* CLASS */));
                    }), 128 /* KEYED_FRAGMENT */))
                ])
            ], 8 /* PROPS */, ["id"])
        ]),
        _createElementVNode("nav", { class: "grid justify-center lg:content-center lg:flex lg:justify-between my-4 w-full px-2 overflow-auto" }, [
            _createElementVNode("span", { class: "text-sm font-normal text-gray-500 dark:text-gray-400 h-8 flex items-center gap-1" }, [
                _createTextVNode(" Monstrando "),
                _createElementVNode("span", { class: "font-semibold text-gray-900 dark:text-white" }, _toDisplayString($setup.data.meta.from) + " al " + _toDisplayString($setup.data.meta.to), 1 /* TEXT */),
                _createTextVNode(" de "),
                _createElementVNode("span", { class: "font-semibold text-gray-900 dark:text-white" }, _toDisplayString($setup.data.meta.total), 1 /* TEXT */),
                _createTextVNode(" Resultados ")
            ]),
            _createElementVNode("ul", { class: "inline-flex -space-x-px rtl:space-x-reverse text-sm h-8" }, [
                _createElementVNode("li", null, [
                    _createElementVNode("a", {
                        class: _normalizeClass(["flex items-center justify-center px-3 h-8 ms-0 leading-tight text-gray-500 bg-white border border-gray-300 rounded-s-lg hover:bg-gray-100 hover:text-gray-700 dark:bg-gray-800 dark:border-gray-700 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white",
                            1 == $setup.data.meta.page
                                ? 'cursor-not-allowed'
                                : 'cursor-pointer'
                        ]),
                        onClick: $event => ($setup.changePage('anterior'))
                    }, " Anterior ", 10 /* CLASS, PROPS */, ["onClick"])
                ]),
                ($setup.getLimitPages[0] > 1)
                    ? (_openBlock(), _createElementBlock("li", { key: 0 }, [
                        _createElementVNode("a", {
                            class: "flex items-center justify-center px-3 h-8 text-blue-600 border border-gray-300 bg-blue-50 hover:bg-blue-100 hover:text-blue-700 dark:border-gray-700 dark:bg-gray-700 dark:text-white cursor-pointer",
                            onClick: $event => ($setup.changePage(1))
                        }, " 1 ", 8 /* PROPS */, ["onClick"])
                    ]))
                    : _createCommentVNode("v-if", true),
                ($setup.getLimitPages[0] > 1)
                    ? (_openBlock(), _createElementBlock("li", { key: 1 }, [
                        _createElementVNode("a", { class: "flex items-center justify-center px-3 h-8 leading-tight text-gray-500 bg-white border border-gray-300 hover:bg-gray-100 hover:text-gray-700 dark:bg-gray-800 dark:border-gray-700 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white cursor-not-allowed" }, " ... ")
                    ]))
                    : _createCommentVNode("v-if", true),
                (_openBlock(true), _createElementBlock(_Fragment, null, _renderList($setup.getLimitPages, (page) => {
                    return (_openBlock(), _createElementBlock("li", null, [
                        ($setup.data.meta.page == page)
                            ? (_openBlock(), _createElementBlock("a", {
                                key: 0,
                                class: "flex items-center justify-center px-3 h-8 text-blue-600 border border-gray-300 bg-blue-50 hover:bg-blue-100 hover:text-blue-700 dark:border-gray-700 dark:bg-gray-700 dark:text-white cursor-pointer",
                                onClick: $event => ($setup.changePage(page)),
                                "aria-current": "page"
                            }, _toDisplayString(page), 9 /* TEXT, PROPS */, ["onClick"]))
                            : (_openBlock(), _createElementBlock("a", {
                                key: 1,
                                class: "flex items-center justify-center px-3 h-8 leading-tight text-gray-500 bg-white border border-gray-300 hover:bg-gray-100 hover:text-gray-700 dark:bg-gray-800 dark:border-gray-700 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white cursor-pointer",
                                onClick: $event => ($setup.changePage(page))
                            }, _toDisplayString(page), 9 /* TEXT, PROPS */, ["onClick"]))
                    ]));
                }), 256 /* UNKEYED_FRAGMENT */)),
                ($setup.data.meta.page < $setup.data.meta.total_pages)
                    ? (_openBlock(), _createElementBlock("li", { key: 2 }, [
                        _createElementVNode("a", { class: "flex items-center justify-center px-3 h-8 leading-tight text-gray-500 bg-white border border-gray-300 hover:bg-gray-100 hover:text-gray-700 dark:bg-gray-800 dark:border-gray-700 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white cursor-not-allowed" }, " ... ")
                    ]))
                    : _createCommentVNode("v-if", true),
                ($setup.data.meta.page < $setup.data.meta.total_pages &&
                    $setup.getLimitPages[$setup.getLimitPages.length - 1] !=
                        $setup.data.meta.total_pages)
                    ? (_openBlock(), _createElementBlock("li", { key: 3 }, [
                        _createElementVNode("a", {
                            class: "flex items-center justify-center px-3 h-8 leading-tight text-gray-500 bg-white border border-gray-300 hover:bg-gray-100 hover:text-gray-700 dark:bg-gray-800 dark:border-gray-700 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white cursor-pointer",
                            onClick: $event => ($setup.changePage($setup.data.meta.total_pages))
                        }, _toDisplayString($setup.data.meta.total_pages), 9 /* TEXT, PROPS */, ["onClick"])
                    ]))
                    : _createCommentVNode("v-if", true),
                _createElementVNode("li", null, [
                    _createElementVNode("a", {
                        class: _normalizeClass(["flex items-center justify-center px-3 h-8 leading-tight text-gray-500 bg-white border border-gray-300 rounded-e-lg hover:bg-gray-100 hover:text-gray-700 dark:bg-gray-800 dark:border-gray-700 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white",
                            $setup.data.meta.total_pages <= $setup.data.meta.page ||
                                $setup.data.meta.total_pages == undefined
                                ? 'cursor-not-allowed'
                                : 'cursor-pointer'
                        ]),
                        onClick: $event => ($setup.changePage('siguiente'))
                    }, " Siguiente ", 10 /* CLASS, PROPS */, ["onClick"])
                ])
            ])
        ])
    ]));
}
customTable_component.render = render_customTable_component;
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
export default customTable_component;
//# sourceMappingURL=customTable.vue.js.map