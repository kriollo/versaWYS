(function () {
    let styleTag = document.createElement('style');
    styleTag.setAttribute('data-v-urgs6335nm', '');
    styleTag.innerHTML = `
    /* Agrega tu estilo aquí */
.clasePersonalizada[data-v-urgs6335nm] {
        color: red;
}
`;
    document.head.appendChild(styleTag);
})();
import { defineComponent as _defineComponent } from '/node_modules/vue/dist/vue.esm-browser.js';
import { ref } from '/node_modules/vue/dist/vue.esm-browser.js';
const dashUrlTest_component = /*@__PURE__*/ _defineComponent({
    __file: 'C:/Users/jjara/Desktop/proyectos/versaWYS-PHP/src/dashboard/js/urltest/dashUrlTest.vue',
    __name: 'dashUrlTest',
    name: 'dashUrlTest',
    components: {},
    setup(__props, { expose: __expose }) {
        __expose();
        const versaComponentKey = ref(0);
        const title = ref('Hello World');
        const count = ref(0);
        const __returned__ = { versaComponentKey, title, count };
        Object.defineProperty(__returned__, '__isScriptSetup', { enumerable: false, value: true });
        return __returned__;
    }
});
import { createElementVNode as _createElementVNode, toDisplayString as _toDisplayString, openBlock as _openBlock, createElementBlock as _createElementBlock, Fragment as _Fragment } from "/node_modules/vue/dist/vue.esm-browser.js";
function render_dashUrlTest_component(_ctx, _cache, $props, $setup, $data, $options) {
    return (_openBlock(), _createElementBlock(_Fragment, null, [
        (_openBlock(), _createElementBlock("div", {
            class: "grid justify-center items-center content-center p-6",
            key: $setup.versaComponentKey
        }, [
            _createElementVNode("h1", { class: "text-2xl font-bold clasePersonalizada" }, "urlTest"),
            _createElementVNode("p", { class: "clasePersonalizada" }, _toDisplayString($setup.title), 1 /* TEXT */)
        ])),
        _createElementVNode("div", { class: "grid justify-center items-center content-center p-6" }, [
            _createElementVNode("button", {
                class: "bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded",
                onClick: $event => ($setup.title = 'Hello VersaWYS')
            }, " Change Title ", 8 /* PROPS */, ["onClick"]),
            _createElementVNode("div", { class: "pt-6" }, [
                _createElementVNode("button", {
                    class: "bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded",
                    onClick: $event => ($setup.count++)
                }, " + ", 8 /* PROPS */, ["onClick"]),
                _createElementVNode("span", { class: "text-white font-bold py-2 px-4 rounded" }, _toDisplayString($setup.count), 1 /* TEXT */),
                _createElementVNode("button", {
                    class: "bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded",
                    onClick: $event => ($setup.count--)
                }, " - ", 8 /* PROPS */, ["onClick"])
            ])
        ])
    ], 64 /* STABLE_FRAGMENT */));
}
dashUrlTest_component.render = render_dashUrlTest_component;
dashUrlTest_component.__scopeId = 'data-v-urgs6335nm';
export default dashUrlTest_component;
//# sourceMappingURL=dashUrlTest.vue.js.map