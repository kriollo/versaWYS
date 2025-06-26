import { useModel as _useModel, mergeModels as _mergeModels, defineComponent as _defineComponent } from '/node_modules/vue/dist/vue.esm-browser.js';
import { ref } from "/node_modules/vue/dist/vue.esm-browser.js";
import { computed, toRefs } from '/node_modules/vue/dist/vue.esm-browser.js';
var CheckTypes;
(function (CheckTypes) {
    CheckTypes["success"] = "text-green-600 dark:text-green-400 focus:ring-green-500 dark:focus:ring-green-500";
    CheckTypes["danger"] = "text-red-600 dark:text-red-400 focus:ring-red-500 dark:focus:ring-red-500";
    CheckTypes["warning"] = "text-yellow-600 dark:text-yellow-400 focus:ring-yellow-500 dark:focus:ring-yellow-500";
    CheckTypes["info"] = "text-gray-600 dark:text-gray-400 focus:ring-gray-500 dark:focus:ring-gray-500";
    CheckTypes["primary"] = "text-blue-600 dark:text-blue-400 focus:ring-blue-500 dark:focus:ring-blue-500";
})(CheckTypes || (CheckTypes = {}));
const check_component = /*@__PURE__*/ _defineComponent({
    __file: 'C:/Users/jjara/Desktop/proyectos/versaWYS-PHP/src/dashboard/js/components/check.vue',
    __name: 'check',
    name: 'check',
    components: {},
    props: /*@__PURE__*/ _mergeModels({
        id: { type: String, required: true },
        label: { type: String, required: false, default: '' },
        type: { type: String, required: false, default: 'success' },
        disabled: { type: Boolean, required: false, default: false }
    }, {
        "modelValue": {
            type: Boolean,
            required: true,
        },
        "modelModifiers": {},
    }),
    emits: ["update:modelValue"],
    setup(__props, { expose: __expose }) {
        __expose();
        const versaComponentKey = ref(0);
        const props = __props;
        const { id, label, type, disabled } = toRefs(props);
        const estado = _useModel(__props, 'modelValue');
        const checkType = computed(() => CheckTypes[type.value]);
        const __returned__ = { versaComponentKey, props, id, label, type, disabled, CheckTypes, estado, checkType };
        Object.defineProperty(__returned__, '__isScriptSetup', { enumerable: false, value: true });
        return __returned__;
    }
});
import { vModelCheckbox as _vModelCheckbox, normalizeClass as _normalizeClass, createElementVNode as _createElementVNode, withDirectives as _withDirectives, toDisplayString as _toDisplayString, openBlock as _openBlock, createElementBlock as _createElementBlock } from "/node_modules/vue/dist/vue.esm-browser.js";
function render_check_component(_ctx, _cache, $props, $setup, $data, $options) {
    return (_openBlock(), _createElementBlock("div", {
        class: "flex gap-2",
        key: $setup.versaComponentKey
    }, [
        _withDirectives(_createElementVNode("input", {
            id: $setup.id,
            type: "checkbox",
            class: _normalizeClass(["h-5 w-5", $setup.checkType]),
            "onUpdate:modelValue": $event => (($setup.estado) = $event),
            disabled: $setup.disabled
        }, null, 10 /* CLASS, PROPS */, ["id", "onUpdate:modelValue", "disabled"]), [
            [_vModelCheckbox, $setup.estado]
        ]),
        _createElementVNode("label", {
            class: "block text-sm font-medium text-gray-900 dark:text-white",
            for: $setup.id
        }, _toDisplayString($setup.label), 9 /* TEXT, PROPS */, ["for"])
    ]));
}
check_component.render = render_check_component;
/**
 * @preserve
    * This Vue component is a checkbox that can be checked or unchecked.
    *
    * Props:
    * @property {string} id - (required): The ID of the checkbox.
    * @property {string} [label=''] - (optional): The label of the checkbox.
    * @property {('success'|'danger'|'warning'|'info')} [type='success'] - (optional): The type of the checkbox.
    * @property {boolean} [disabled=false] - (optional): Determines whether the checkbox is disabled.
    *
    * Emits:
    * @event {Boolean} v-model - Emits a boolean value to determine whether the checkbox is checked or unchecked.
 */
export default check_component;
//# sourceMappingURL=check.vue.js.map