import { defineComponent as _defineComponent } from '/node_modules/vue/dist/vue.esm-browser.js';
import { ref } from "/node_modules/vue/dist/vue.esm-browser.js";
const loader_component = /*@__PURE__*/ _defineComponent({
    __file: 'C:/Users/jjara/Desktop/proyectos/versaWYS-PHP/src/dashboard/js/components/loader.vue',
    __name: 'loader',
    name: 'loader',
    components: {},
    setup(__props, { expose: __expose }) {
        __expose();
        const versaComponentKey = ref(0);
        const __returned__ = { versaComponentKey };
        Object.defineProperty(__returned__, '__isScriptSetup', { enumerable: false, value: true });
        return __returned__;
    }
});
import { createElementVNode as _createElementVNode, openBlock as _openBlock, createElementBlock as _createElementBlock } from "/node_modules/vue/dist/vue.esm-browser.js";
function render_loader_component(_ctx, _cache, $props, $setup, $data, $options) {
    return (_openBlock(), _createElementBlock("svg", {
        class: "animate-spin p-1 h-6 w-6 text-white",
        fill: "none",
        viewBox: "0 0 24 24",
        xmlns: "http://www.w3.org/2000/svg",
        key: $setup.versaComponentKey
    }, [
        _createElementVNode("circle", {
            class: "opacity-25 stroke-blue-900 dark:stroke-blue-100",
            cx: "12",
            cy: "12",
            r: "10",
            stroke: "currentColor",
            "stroke-width": "4"
        }),
        _createElementVNode("path", {
            class: "opacity-75 stroke-blue-900 dark:stroke-blue-100",
            d: "M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z",
            fill: "currentColor"
        })
    ]));
}
loader_component.render = render_loader_component;
export default loader_component;
//# sourceMappingURL=loader.vue.js.map