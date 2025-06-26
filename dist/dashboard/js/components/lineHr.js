import { defineComponent as _defineComponent } from '/node_modules/vue/dist/vue.esm-browser.js';
import { ref } from "/node_modules/vue/dist/vue.esm-browser.js";
const lineHr_component = /*@__PURE__*/ _defineComponent({
    __file: 'C:/Users/jjara/Desktop/proyectos/versaWYS-PHP/src/dashboard/js/components/lineHr.vue',
    __name: 'lineHr',
    name: 'lineHr',
    components: {},
    setup(__props, { expose: __expose }) {
        __expose();
        const versaComponentKey = ref(0);
        const __returned__ = { versaComponentKey };
        Object.defineProperty(__returned__, '__isScriptSetup', { enumerable: false, value: true });
        return __returned__;
    }
});
import { openBlock as _openBlock, createElementBlock as _createElementBlock } from "/node_modules/vue/dist/vue.esm-browser.js";
function render_lineHr_component(_ctx, _cache, $props, $setup, $data, $options) {
    return (_openBlock(), _createElementBlock("hr", {
        class: "h-px my-4 bg-gray-200 border-0 dark:bg-gray-700",
        key: $setup.versaComponentKey
    }));
}
lineHr_component.render = render_lineHr_component;
export default lineHr_component;
//# sourceMappingURL=lineHr.vue.js.map