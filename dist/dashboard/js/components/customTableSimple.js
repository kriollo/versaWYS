import { useSlots as _useSlots, defineComponent as _defineComponent } from '/node_modules/vue/dist/vue.esm-browser.js';
import { computed, reactive, ref, toRefs, watch, watchEffect } from '/node_modules/vue/dist/vue.esm-browser.js';
import dropDown from '@/dashboard/js/components/dropDown.vue';
import loader from '@/dashboard/js/components/loader.vue';
import { GLOBAL_CONSTANTS, PAGINATION } from '@/dashboard/js/constants';
const customTableSimple_component = /*@__PURE__*/ _defineComponent({
    __file: 'C:/Users/jjara/Desktop/proyectos/versaWYS-PHP/src/dashboard/js/components/customTableSimple.vue',
    __name: 'customTableSimple',
    name: 'customTableSimple',
    components: {
        dropDown,
        loader
    },
    props: {
        id: { type: String, required: false, default: 'table' },
        tablaTitle: { type: String, required: false, default: '' },
        columns: { type: Array, required: true },
        dataInput: { type: Array, required: true },
        fieldOrder: { type: String, required: false, default: 'id' },
        perPage: { type: Number, required: true, default: 25 },
        showPerPage: { type: Boolean, required: false, default: true },
        showExportExcel: { type: Boolean, required: false, default: true },
        showSearch: { type: Boolean, required: false, default: true },
        itemSelected: { type: Object, required: false },
        smallLine: { type: Boolean, required: false, default: false },
        colspan: { type: Array, required: false },
        reloadData: { type: Boolean, required: false, default: false }
    },
    emits: ['accion', 'update:totalRegisters'],
    setup(__props, { expose: __expose, emit: __emit }) {
        __expose();
        const versaComponentKey = ref(0);
        const props = __props;
        const emit = __emit;
        const loadingData = ref(false);
        const slots = _useSlots();
        const msg = ref('Cargando...');
        const { tablaTitle, id, fieldOrder, perPage, showPerPage, showExportExcel, showSearch, itemSelected, smallLine, columns, dataInput, colspan, reloadData, } = toRefs(props);
        const showPerPages = [...PAGINATION.PER_PAGE_OPTIONS];
        const data = reactive({
            data: [],
            columns: [],
            colspan: [],
            meta: {
                total: 0,
                per_page: perPage.value,
                page: PAGINATION.DEFAULT_PAGE,
                total_pages: 0,
                filter: '',
                from: 0,
                to: 0,
                order: [`${fieldOrder.value}`, 'asc'],
            },
        });
        const dataTemp = ref([]);
        const refreshData = () => {
            data.columns = columns.value;
            if ('' !== data.meta.filter) {
                const filter = data.meta.filter.toLowerCase();
                dataTemp.value = dataInput.value.filter(item => Object.values(item).some((value) => value?.toString().toLowerCase().includes(filter)));
            }
            else {
                dataTemp.value = dataInput.value;
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
        watch(() => dataInput.value, () => {
            refreshData();
        });
        watch(columns.value, () => {
            refreshData();
        }, { immediate: true });
        watch(() => data.meta.per_page, () => {
            setPerPage(data.meta.per_page);
        });
        watch(() => props.reloadData, () => {
            refreshData();
        });
        watchEffect(() => {
            emit('update:totalRegisters', data.meta?.total ?? GLOBAL_CONSTANTS.ZERO);
        });
        const clearFiler = () => {
            data.meta.filter = '';
            refreshData();
        };
        const setPerPage = (per_page) => {
            data.meta.page = PAGINATION.DEFAULT_PAGE;
            data.meta.per_page = per_page;
            refreshData();
        };
        const setFilter = () => {
            data.meta.page = PAGINATION.DEFAULT_PAGE;
            refreshData();
        };
        const setOrder = (field, order) => {
            data.meta.page = PAGINATION.DEFAULT_PAGE;
            if (data.meta.order[PAGINATION.ARRAY_FIRST_INDEX] !== field) {
                order = 'asc';
            }
            data.meta.order = [field, order];
            refreshData();
        };
        const changePage = (page) => {
            let goPage = GLOBAL_CONSTANTS.ZERO;
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
            refreshData();
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
        //detectar cambios en itemSelected y crear clase para linea de la tabla
        const classLineTable = (item) => {
            if (itemSelected.value?.fieldCompare !== undefined &&
                itemSelected.value.id === item.id) {
                return 'bg-blue-100 border border-blue-500 dark:bg-blue-800 dark:border-blue-700';
            }
            return 'bg-white border-b dark:bg-gray-800 dark:border-gray-700';
        };
        const __returned__ = { versaComponentKey, props, emit, loadingData, slots, msg, tablaTitle, id, fieldOrder, perPage, showPerPage, showExportExcel, showSearch, itemSelected, smallLine, columns, dataInput, colspan, reloadData, showPerPages, data, dataTemp, refreshData, clearFiler, setPerPage, setFilter, setOrder, changePage, getLimitPages, classLineTable, dropDown, loader };
        Object.defineProperty(__returned__, '__isScriptSetup', { enumerable: false, value: true });
        return __returned__;
    }
});
import { renderSlot as _renderSlot, resolveComponent as _resolveComponent, openBlock as _openBlock, createBlock as _createBlock, createCommentVNode as _createCommentVNode, createElementVNode as _createElementVNode, createElementBlock as _createElementBlock, vModelText as _vModelText, withKeys as _withKeys, withDirectives as _withDirectives, toDisplayString as _toDisplayString, renderList as _renderList, Fragment as _Fragment, normalizeClass as _normalizeClass, createTextVNode as _createTextVNode } from "/node_modules/vue/dist/vue.esm-browser.js";
function render_customTableSimple_component(_ctx, _cache, $props, $setup, $data, $options) {
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
                class: "flex items-center justify-between flex-column flex-wrap md:flex-row space-y-4 md:space-y-0 py-2"
            }, [
                ($setup.showPerPage)
                    ? (_openBlock(), _createBlock(_component_dropDown, {
                        key: "per_page",
                        modelValue: $setup.data.meta.per_page,
                        "onUpdate:modelValue": $event => (($setup.data.meta.per_page) = $event),
                        title: "Mostrar",
                        from: "per_page",
                        list: $setup.showPerPages
                    }, null, 8 /* PROPS */, ["modelValue", "onUpdate:modelValue"]))
                    : _createCommentVNode("v-if", true),
                _createCommentVNode(" input buscar "),
                ($setup.showSearch)
                    ? (_openBlock(), _createElementBlock("div", {
                        key: 1,
                        class: "relative"
                    }, [
                        _createElementVNode("button", {
                            class: "absolute inset-y-0 rtl:inset-r-0 end-0 flex items-center pe-3 cursor-pointer",
                            title: "Click para buscar",
                            onClick: $setup.setFilter
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
                                title: "Limpiar filtro",
                                onClick: $setup.clearFiler
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
                            id: $setup.id + '_table-search-users',
                            "onUpdate:modelValue": $event => (($setup.data.meta.filter) = $event),
                            type: "text",
                            class: "block p-2 ps-2 text-sm text-gray-900 border border-gray-300 rounded-lg w-80 bg-gray-50 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500",
                            placeholder: "Ingrese y presione 'Enter' para buscar",
                            onKeyup: _withKeys($setup.setFilter, ["enter"])
                        }, null, 40 /* PROPS, NEED_HYDRATION */, ["id", "onUpdate:modelValue"]), [
                            [_vModelText, $setup.data.meta.filter]
                        ])
                    ]))
                    : _createCommentVNode("v-if", true)
            ]))
            : _createCommentVNode("v-if", true),
        _createElementVNode("div", { class: "overflow-x-auto" }, [
            _createElementVNode("table", { class: "w-full text-sm text-left rtl:text-right text-gray-500 dark:text-gray-400" }, [
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
                        (_openBlock(true), _createElementBlock(_Fragment, null, _renderList($setup.colspan, (cols, index) => {
                            return (_openBlock(), _createElementBlock("th", {
                                key: index + '_colspan',
                                scope: "colspan",
                                colspan: cols.colspan
                            }, [
                                _createElementVNode("div", { class: "flex justify-center" }, _toDisplayString(cols.title), 1 /* TEXT */)
                            ], 8 /* PROPS */, ["colspan"]));
                        }), 128 /* KEYED_FRAGMENT */))
                    ])
                ]),
                _createElementVNode("thead", { class: "text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400" }, [
                    _createElementVNode("tr", null, [
                        (_openBlock(true), _createElementBlock(_Fragment, null, _renderList($setup.data.columns, (col, index) => {
                            return (_openBlock(), _createElementBlock("th", {
                                key: index + '_col',
                                class: _normalizeClass($setup.smallLine ? 'py-1 px-2' : 'py-4 px-3'),
                                scope: "col"
                            }, _toDisplayString(col), 3 /* TEXT, CLASS */));
                        }), 128 /* KEYED_FRAGMENT */))
                    ])
                ]),
                _createElementVNode("tbody", { class: "overflow-y-auto max-h-72" }, [
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
                            key: index + '_row',
                            class: _normalizeClass($setup.classLineTable(row))
                        }, [
                            (_openBlock(true), _createElementBlock(_Fragment, null, _renderList($setup.data.columns, (col, key) => {
                                return (_openBlock(), _createElementBlock("td", {
                                    key: key + '_col',
                                    class: _normalizeClass($setup.smallLine ? 'py-1 px-2' : 'py-4 px-3')
                                }, [
                                    (col === 'acciones')
                                        ? (_openBlock(), _createElementBlock("div", {
                                            key: 0,
                                            class: "flex justify-end gap-2",
                                            innerHTML: row[key]
                                        }, null, 8 /* PROPS */, ["innerHTML"]))
                                        : (_openBlock(), _createElementBlock("div", { key: 1 }, _toDisplayString(row[key]), 1 /* TEXT */))
                                ], 2 /* CLASS */));
                            }), 128 /* KEYED_FRAGMENT */))
                        ], 2 /* CLASS */));
                    }), 128 /* KEYED_FRAGMENT */))
                ])
            ])
        ]),
        _createElementVNode("nav", { class: "grid justify-center md:content-center md:flex md:justify-between my-4 w-full px-2 overflow-auto" }, [
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
                (_openBlock(true), _createElementBlock(_Fragment, null, _renderList($setup.getLimitPages, (page, index) => {
                    return (_openBlock(), _createElementBlock("li", {
                        key: index + '_page'
                    }, [
                        ($setup.data.meta.page == page)
                            ? (_openBlock(), _createElementBlock("a", {
                                key: 0,
                                class: "flex items-center justify-center px-3 h-8 text-blue-600 border border-gray-300 bg-blue-50 hover:bg-blue-100 hover:text-blue-700 dark:border-gray-700 dark:bg-gray-700 dark:text-white cursor-pointer",
                                "aria-current": "page",
                                onClick: $event => ($setup.changePage(page))
                            }, _toDisplayString(page), 9 /* TEXT, PROPS */, ["onClick"]))
                            : (_openBlock(), _createElementBlock("a", {
                                key: 1,
                                class: "flex items-center justify-center px-3 h-8 leading-tight text-gray-500 bg-white border border-gray-300 hover:bg-gray-100 hover:text-gray-700 dark:bg-gray-800 dark:border-gray-700 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white cursor-pointer",
                                onClick: $event => ($setup.changePage(page))
                            }, _toDisplayString(page), 9 /* TEXT, PROPS */, ["onClick"]))
                    ]));
                }), 128 /* KEYED_FRAGMENT */)),
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
customTableSimple_component.render = render_customTableSimple_component;
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
export default customTableSimple_component;
//# sourceMappingURL=customTableSimple.vue.js.map