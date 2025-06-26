import { useModel as _useModel, mergeModels as _mergeModels, defineComponent as _defineComponent } from '/node_modules/vue/dist/vue.esm-browser.js';
import { ref } from "/node_modules/vue/dist/vue.esm-browser.js";
import { computed, toRefs } from '/node_modules/vue/dist/vue.esm-browser.js';
var Optiontypes;
(function (Optiontypes) {
    Optiontypes["success"] = "text-green-600 dark:text-green-400 focus:ring-green-500 dark:focus:ring-green-500";
    Optiontypes["danger"] = "text-red-600 dark:text-red-400 focus:ring-red-500 dark:focus:ring-red-500";
    Optiontypes["warning"] = "text-yellow-600 dark:text-yellow-400 focus:ring-yellow-500 dark:focus:ring-yellow-500";
    Optiontypes["info"] = "text-blue-600 dark:text-blue-400 focus:ring-blue-500 dark:focus:ring-blue-500";
})(Optiontypes || (Optiontypes = {}));
const radio_component = /*@__PURE__*/ _defineComponent({
    __file: 'C:/Users/jjara/Desktop/proyectos/versaWYS-PHP/src/dashboard/js/components/radio.vue',
    __name: 'radio',
    name: 'radio',
    components: {},
    props: /*@__PURE__*/ _mergeModels({
        id: { type: String, required: true, default: 'radio' },
        label: { type: String, required: false, default: '' },
        options: { type: Array, required: true },
        direction: { type: String, required: false, default: 'row' },
        type: { type: String, required: false, default: 'success' },
        disabled: { type: Boolean, required: false, default: false }
    }, {
        "modelValue": {},
        "modelModifiers": {},
    }),
    emits: ["update:modelValue"],
    setup(__props, { expose: __expose }) {
        __expose();
        const versaComponentKey = ref(0);
        const props = __props;
        const { id, label, direction, type, disabled } = toRefs(props);
        const options = computed(() => props.options);
        const value = _useModel(__props, "modelValue");
        const typeOption = computed(() => Optiontypes[type.value]);
        const __returned__ = { versaComponentKey, props, id, label, direction, type, disabled, Optiontypes, options, value, typeOption };
        Object.defineProperty(__returned__, '__isScriptSetup', { enumerable: false, value: true });
        return __returned__;
    }
});
import { toDisplayString as _toDisplayString, createElementVNode as _createElementVNode, renderList as _renderList, Fragment as _Fragment, openBlock as _openBlock, createElementBlock as _createElementBlock, vModelRadio as _vModelRadio, normalizeClass as _normalizeClass, withDirectives as _withDirectives } from "/node_modules/vue/dist/vue.esm-browser.js";
function render_radio_component(_ctx, _cache, $props, $setup, $data, $options) {
    return (_openBlock(), _createElementBlock("div", {
        class: "flex flex-col",
        key: $setup.versaComponentKey
    }, [
        _createElementVNode("label", {
            class: "block text-sm font-medium text-gray-700 dark:text-white",
            for: $setup.id + '_' + $setup.options[0]?.id
        }, _toDisplayString($setup.label), 9 /* TEXT, PROPS */, ["for"]),
        _createElementVNode("div", {
            class: _normalizeClass(["gap-2", $setup.direction === 'row' ? 'flex' : 'flex flex-col'])
        }, [
            (_openBlock(true), _createElementBlock(_Fragment, null, _renderList($setup.options, (option, index) => {
                return (_openBlock(), _createElementBlock("div", {
                    key: index + '_radio',
                    class: "flex gap-2"
                }, [
                    _withDirectives(_createElementVNode("input", {
                        id: $setup.id + '_' + option.id,
                        "onUpdate:modelValue": $event => (($setup.value) = $event),
                        name: $setup.id,
                        type: "radio",
                        class: _normalizeClass(["h-5 w-5", $setup.typeOption]),
                        value: option?.value,
                        disabled: $setup.disabled
                    }, null, 10 /* CLASS, PROPS */, ["id", "onUpdate:modelValue", "name", "value", "disabled"]), [
                        [_vModelRadio, $setup.value]
                    ]),
                    _createElementVNode("label", {
                        class: "block text-sm font-medium text-gray-900 dark:text-white",
                        for: $setup.id + '_' + option?.id
                    }, _toDisplayString(option?.name), 9 /* TEXT, PROPS */, ["for"])
                ]));
            }), 128 /* KEYED_FRAGMENT */))
        ], 2 /* CLASS */)
    ]));
}
radio_component.render = render_radio_component;
export default radio_component;
//# sourceMappingURL=radio.vue.js.map