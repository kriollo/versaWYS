(function () {
    let styleTag = document.createElement('style');
    styleTag.setAttribute('data-v-b1s7k09hre', '');
    styleTag.innerHTML = `
.loader[data-v-b1s7k09hre] {
        aspect-ratio: 2;
        --_g: no-repeat radial-gradient(circle closest-side, #ccc 90%, #0000);
        background:
            var(--_g) 0% 50%,
            var(--_g) 50% 50%,
            var(--_g) 100% 50%;
        background-size: calc(100% / 3) 50%;
        animation: l3-b1s7k09hre 1s infinite linear;
}
@keyframes l3-b1s7k09hre {
20% {
            background-position:
                0% 0%,
                50% 50%,
                100% 50%;
}
40% {
            background-position:
                0% 100%,
                50% 0%,
                100% 50%;
}
60% {
            background-position:
                0% 50%,
                50% 100%,
                100% 0%;
}
80% {
            background-position:
                0% 50%,
                50% 50%,
                100% 100%;
}
}
`;
    document.head.appendChild(styleTag);
})();
import { defineComponent as _defineComponent } from '/node_modules/vue/dist/vue.esm-browser.js';
import { ref } from "/node_modules/vue/dist/vue.esm-browser.js";
const loaderDot_component = /*@__PURE__*/ _defineComponent({
    __file: 'C:/Users/jjara/Desktop/proyectos/versaWYS-PHP/src/dashboard/js/components/loaderDot.vue',
    __name: 'loaderDot',
    name: 'loaderDot',
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
function render_loaderDot_component(_ctx, _cache, $props, $setup, $data, $options) {
    return (_openBlock(), _createElementBlock("div", {
        class: "p-1 h-6 w-6 loader text-gray-500 dark:text-gray-400",
        key: $setup.versaComponentKey
    }));
}
loaderDot_component.render = render_loaderDot_component;
loaderDot_component.__scopeId = 'data-v-b1s7k09hre';
export default loaderDot_component;
//# sourceMappingURL=loaderDot.vue.js.map