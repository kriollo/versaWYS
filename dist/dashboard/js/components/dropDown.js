import { useModel as _useModel, mergeModels as _mergeModels, defineComponent as _defineComponent } from '/node_modules/vue/dist/vue.esm-browser.js';
import { ref } from "/node_modules/vue/dist/vue.esm-browser.js";
import { computed, onMounted } from '/node_modules/vue/dist/vue.esm-browser.js';
import { $dom } from '@/dashboard/js/composables/dom';
import { TIMEOUTS } from '@/dashboard/js/constants';
const dropDown_component = /*@__PURE__*/ _defineComponent({
    __file: 'C:/Users/jjara/Desktop/proyectos/versaWYS-PHP/src/dashboard/js/components/dropDown.vue',
    __name: 'dropDown',
    name: 'dropDown',
    components: {},
    props: /*@__PURE__*/ _mergeModels({
        title: {
            type: String,
            default: '',
            required: false,
        },
        from: {
            type: String,
            required: true,
        },
        list: {
            type: Array,
            required: true,
        },
    }, {
        "modelValue": {},
        "modelModifiers": {},
    }),
    emits: /*@__PURE__*/ _mergeModels(['accion', 'update:model'], ["update:modelValue"]),
    setup(__props, { expose: __expose, emit: __emit }) {
        __expose();
        const versaComponentKey = ref(0);
        const model = _useModel(__props, "modelValue");
        const emit = __emit;
        const props = __props;
        const list = computed(() => props.list);
        const from = computed(() => props.from);
        const title = computed(() => props.title);
        const setButtonValue = (value) => {
            model.value = value;
            emit('update:model', {
                accion: 'setButtonValue',
                from: from.value,
                item: value,
            });
            const $dropdown = $dom(`#dropdownButton${from.value}`);
            const $dropdownElement = $dom(`#dropdownList${from.value}`);
            if (!($dropdown instanceof HTMLButtonElement) || !($dropdownElement instanceof HTMLDivElement)) {
                return;
            }
            dropdownToggle($dropdown, $dropdownElement);
        };
        const dropdownToggle = ($dropdown, $dropdownElement) => {
            $dropdownElement.classList.toggle('block');
            if ($dropdownElement.classList.contains('hidden')) {
                $dropdownElement.classList.remove('hidden');
                setTimeout(() => {
                    $dropdownElement.classList.remove('opacity-0');
                    $dropdownElement.classList.add('opacity-100');
                }, TIMEOUTS.PROGRESS_UPDATE);
            }
            else {
                $dropdownElement.classList.remove('opacity-100');
                $dropdownElement.classList.add('opacity-0');
                setTimeout(() => {
                    $dropdownElement.classList.add('hidden');
                }, TIMEOUTS.DROPDOWN_TRANSITION); // Duración de la transición
            }
            if ($dropdownElement.classList.contains('block')) {
                $dropdownElement.style.top = `${$dropdown.offsetTop + $dropdown.offsetHeight + TIMEOUTS.DROPDOWN_OFFSET}px`;
                $dropdownElement.style.left = `${$dropdown.offsetLeft}px`;
                $dropdownElement.style.width = 'auto';
                $dropdownElement.style.whiteSpace = 'nowrap';
            }
        };
        onMounted(() => {
            const $dropdown = $dom(`#dropdownButton${from.value}`);
            if (!($dropdown instanceof HTMLButtonElement)) {
                return;
            }
            const dropdownToggleComponent = $dropdown.getAttribute('data-dropdown-toggle-component');
            if (!dropdownToggleComponent) {
                return;
            }
            const $dropdownElement = $dom(`#${dropdownToggleComponent}`);
            if (!($dropdownElement instanceof HTMLDivElement)) {
                return;
            }
            $dropdownElement.style.position = 'absolute';
            $dropdownElement.style.top = `${$dropdown.offsetTop + $dropdown.offsetHeight + TIMEOUTS.DROPDOWN_OFFSET}px`;
            $dropdownElement.style.left = `${$dropdown.offsetLeft}px`;
            $dropdownElement.style.width = 'auto';
            $dropdownElement.style.whiteSpace = 'nowrap';
            const documentClickListener = (e) => {
                if ($dropdownElement.classList.contains('block') && !$dropdown.contains(e.target)) {
                    dropdownToggle($dropdown, $dropdownElement);
                    document.removeEventListener('click', documentClickListener);
                }
            };
            $dropdown.addEventListener('click', () => {
                dropdownToggle($dropdown, $dropdownElement);
                document.addEventListener('click', documentClickListener);
            });
        });
        const __returned__ = { versaComponentKey, model, emit, props, list, from, title, setButtonValue, dropdownToggle };
        Object.defineProperty(__returned__, '__isScriptSetup', { enumerable: false, value: true });
        return __returned__;
    }
});
import { toDisplayString as _toDisplayString, createElementVNode as _createElementVNode, openBlock as _openBlock, createElementBlock as _createElementBlock, createTextVNode as _createTextVNode, createCommentVNode as _createCommentVNode, renderList as _renderList, Fragment as _Fragment } from "/node_modules/vue/dist/vue.esm-browser.js";
function render_dropDown_component(_ctx, _cache, $props, $setup, $data, $options) {
    return (_openBlock(), _createElementBlock("div", { key: $setup.versaComponentKey }, [
        _createElementVNode("span", { class: "text-gray-500 pe-1" }, _toDisplayString($setup.title), 1 /* TEXT */),
        _createElementVNode("button", {
            id: 'dropdownButton' + $setup.from,
            "data-dropdown-toggle-component": 'dropdownList' + $setup.from,
            class: "inline-flex items-center text-gray-500 bg-white border border-gray-300 focus:outline-none hover:bg-gray-100 focus:ring-4 focus:ring-gray-200 font-medium rounded-lg text-sm px-3 py-1.5 dark:bg-gray-800 dark:text-gray-400 dark:border-gray-600 dark:hover:bg-gray-700 dark:hover:border-gray-600 dark:focus:ring-gray-700",
            type: "button"
        }, [
            _createTextVNode(_toDisplayString($setup.model) + " ", 1 /* TEXT */),
            (_openBlock(), _createElementBlock("svg", {
                class: "w-2.5 h-2.5 ms-2.5",
                fill: "none",
                viewBox: "0 0 10 6",
                xmlns: "http://www.w3.org/2000/svg"
            }, [
                _createElementVNode("path", {
                    d: "m1 1 4 4 4-4",
                    stroke: "currentColor",
                    "stroke-linecap": "round",
                    "stroke-linejoin": "round",
                    "stroke-width": "2"
                })
            ]))
        ], 8 /* PROPS */, ["id", "data-dropdown-toggle-component"]),
        _createCommentVNode(" Dropdown menu "),
        _createElementVNode("div", {
            id: 'dropdownList' + $setup.from,
            class: "z-10 bg-white rounded-lg shadow dark:bg-gray-700 hidden transition-opacity duration-300 ease-in-out opacity-0"
        }, [
            _createElementVNode("ul", {
                class: "text-sm text-gray-700 dark:text-gray-200 grid justify-center cursor-pointer",
                "aria-labelledby": "dropdownPerPage"
            }, [
                (_openBlock(true), _createElementBlock(_Fragment, null, _renderList($setup.list, (item, index) => {
                    return (_openBlock(), _createElementBlock("li", null, [
                        _createElementVNode("a", {
                            class: "block px-4 py-2 hover:bg-gray-100 dark:hover:bg-gray-600 dark:hover:text-white hover:rounded-lg",
                            onClick: $event => ($setup.setButtonValue(item))
                        }, _toDisplayString(item), 9 /* TEXT, PROPS */, ["onClick"])
                    ]));
                }), 256 /* UNKEYED_FRAGMENT */))
            ])
        ], 8 /* PROPS */, ["id"])
    ]));
}
dropDown_component.render = render_dropDown_component;
export default dropDown_component;
//# sourceMappingURL=dropDown.vue.js.map