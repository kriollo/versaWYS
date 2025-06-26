import { useModel as _useModel, mergeModels as _mergeModels, defineComponent as _defineComponent } from '/node_modules/vue/dist/vue.esm-browser.js';
import { computed, nextTick, ref, watch } from '/node_modules/vue/dist/vue.esm-browser.js';
const inputEditable_component = /*@__PURE__*/ _defineComponent({
    __file: 'C:/Users/jjara/Desktop/proyectos/versaWYS-PHP/src/dashboard/js/components/inputEditable.vue',
    __name: 'inputEditable',
    name: 'inputEditable',
    components: {},
    props: /*@__PURE__*/ _mergeModels({
        id: {
            type: String,
            default: 'inputEditable',
        },
        field: {
            type: String,
        },
        type: {
            type: String,
            default: 'text',
        },
        placeholder: {
            type: String,
            default: 'Escribe algo...',
        },
        showCancel: {
            type: Boolean,
            default: false,
        },
    }, {
        "modelValue": {
            type: String,
            required: true,
        },
        "modelModifiers": {},
    }),
    emits: /*@__PURE__*/ _mergeModels(['accion'], ["update:modelValue"]),
    setup(__props, { expose: __expose, emit: __emit }) {
        __expose();
        const versaComponentKey = ref(0);
        const props = __props;
        const emit = __emit;
        const dataProps = _useModel(__props, 'modelValue');
        // const dataProps = computed(() => props.data);
        const idProps = computed(() => props.id);
        const newData = ref('');
        const field = computed(() => props.field);
        const type = computed(() => props.type);
        const placeholder = computed(() => props.placeholder);
        const showCancel = computed(() => props.showCancel);
        const txtInput = ref(null);
        newData.value = dataProps.value ?? '';
        watch(() => dataProps.value, () => {
            newData.value = dataProps.value ?? '';
        });
        nextTick(() => {
            txtInput.value?.focus();
        });
        const accion = (accion) => {
            const actions = {
                updateData: () => {
                    emit('accion', accion);
                },
                cancelUpdate: () => {
                    newData.value = dataProps.value ?? '';
                    emit('accion', accion);
                },
            };
            const selectedAction = actions[accion.accion] || actions['default'];
            if ('function' === typeof selectedAction) {
                selectedAction();
            }
        };
        const __returned__ = { versaComponentKey, props, emit, dataProps, idProps, newData, field, type, placeholder, showCancel, txtInput, accion };
        Object.defineProperty(__returned__, '__isScriptSetup', { enumerable: false, value: true });
        return __returned__;
    }
});
import { vModelDynamic as _vModelDynamic, createElementVNode as _createElementVNode, withDirectives as _withDirectives, openBlock as _openBlock, createElementBlock as _createElementBlock, createCommentVNode as _createCommentVNode } from "/node_modules/vue/dist/vue.esm-browser.js";
function render_inputEditable_component(_ctx, _cache, $props, $setup, $data, $options) {
    return (_openBlock(), _createElementBlock("div", {
        class: "flex w-full",
        key: $setup.versaComponentKey
    }, [
        _withDirectives(_createElementVNode("input", {
            autofocus: "",
            ref: "txtInput",
            type: $setup.type,
            id: 'txtInput_' + $setup.idProps + '_' + $setup.field,
            placeholder: $setup.placeholder,
            "onUpdate:modelValue": $event => (($setup.newData) = $event),
            class: "block p-2.5 w-full text-sm text-gray-900 bg-gray-50 rounded-l-lg border-s-gray-50 border-s-2 border border-gray-300 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:border-s-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:border-blue-500",
            style: { "overflow": "auto", "max-height": "100px" }
        }, null, 8 /* PROPS */, ["type", "id", "placeholder", "onUpdate:modelValue"]), [
            [_vModelDynamic, $setup.newData]
        ]),
        _createElementVNode("div", { class: "flex m-0 p-0" }, [
            _createElementVNode("button", {
                onClick: $event => ($setup.accion({
                    accion: 'updateData',
                    id: Number($setup.idProps),
                    newData: $setup.newData,
                    field: $setup.field,
                })),
                type: "button",
                title: "Guardar",
                class: "p-2.5 text-sm font-medium h-full text-white bg-green-700 rounded-none border border-green-700 hover:bg-green-800 focus:ring-4 focus:outline-none focus:ring-green-300 dark:bg-green-600 dark:hover:bg-green-700 dark:focus:ring-green-800"
            }, [
                _createElementVNode("i", { class: "bi bi-floppy" })
            ], 8 /* PROPS */, ["onClick"]),
            ($setup.showCancel)
                ? (_openBlock(), _createElementBlock("button", {
                    key: 0,
                    onClick: $event => ($setup.accion({
                        accion: 'cancelUpdate',
                        id: Number($setup.idProps),
                        field: $setup.field,
                    })),
                    type: "button",
                    title: "Cancelar",
                    class: "p-2.5 text-sm font-medium h-full text-white bg-orange-700 rounded-r-lg border border-orange-700 hover:bg-orange-800 focus:ring-4 focus:outline-none focus:ring-orange-300 dark:bg-orange-600 dark:hover:bg-orange-700 dark:focus:ring-orange-800"
                }, [
                    _createElementVNode("i", { class: "bi bi-x-lg" })
                ], 8 /* PROPS */, ["onClick"]))
                : _createCommentVNode("v-if", true)
        ])
    ]));
}
inputEditable_component.render = render_inputEditable_component;
export default inputEditable_component;
//# sourceMappingURL=inputEditable.vue.js.map