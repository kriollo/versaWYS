(function () {
    let styleTag = document.createElement('style');
    styleTag.setAttribute('data-v-d93dkeid1x', '');
    styleTag.innerHTML = `
.v-enter-active[data-v-d93dkeid1x],
    .v-leave-active[data-v-d93dkeid1x] {
        transition: opacity 0.3s ease;
}
.v-enter-from[data-v-d93dkeid1x],
    .v-leave-to[data-v-d93dkeid1x] {
        opacity: 0;
}
`;
    document.head.appendChild(styleTag);
})();
import { defineComponent as _defineComponent } from '/node_modules/vue/dist/vue.esm-browser.js';
import { computed, ref } from '/node_modules/vue/dist/vue.esm-browser.js';
const simpleModal_component = /*@__PURE__*/ _defineComponent({
    __file: 'C:/Users/jjara/Desktop/proyectos/versaWYS-PHP/src/dashboard/js/components/simpleModal.vue',
    __name: 'simpleModal',
    name: 'simpleModal',
    components: {},
    props: {
        idModal: { type: String, required: true },
        showModal: { type: Boolean, required: true, default: true },
        size: { type: String, required: false, default: 'max-w-md' }
    },
    setup(__props, { expose: __expose }) {
        __expose();
        const versaComponentKey = ref(0);
        const props = __props;
        const showModal = computed(() => props.showModal);
        const idModal = computed(() => props.idModal);
        const size = computed(() => props.size);
        const modal = ref(undefined);
        const __returned__ = { versaComponentKey, props, showModal, idModal, size, modal };
        Object.defineProperty(__returned__, '__isScriptSetup', { enumerable: false, value: true });
        return __returned__;
    }
});
import { createCommentVNode as _createCommentVNode, renderSlot as _renderSlot, createElementVNode as _createElementVNode, normalizeClass as _normalizeClass, openBlock as _openBlock, createElementBlock as _createElementBlock } from "/node_modules/vue/dist/vue.esm-browser.js";
function render_simpleModal_component(_ctx, _cache, $props, $setup, $data, $options) {
    return ($setup.showModal)
        ? (_openBlock(), _createElementBlock("div", {
            id: $setup.idModal,
            ref: "modal",
            class: "hidden overflow-y-auto overflow-x-hidden fixed top-0 right-0 left-0 z-50 justify-center items-center w-full md:inset-0 h-[calc(100%-1rem)] max-h-full backdrop-blur drop-shadow-versaWYS",
            tabindex: "-1",
            key: $setup.versaComponentKey
        }, [
            _createElementVNode("div", {
                class: _normalizeClass(["relative p-4 w-full max-h-full", $setup.size])
            }, [
                _createCommentVNode(" Modal content "),
                _createElementVNode("div", { class: "relative bg-white rounded-lg shadow dark:bg-gray-700" }, [
                    _createCommentVNode(" Modal body "),
                    _createElementVNode("div", { class: "p-4 md:p-5 space-y-4" }, [
                        _renderSlot(_ctx.$slots, "modalBody", {}, undefined, true)
                    ])
                ])
            ], 2 /* CLASS */)
        ], 8 /* PROPS */, ["id"]))
        : _createCommentVNode("v-if", true);
}
simpleModal_component.render = render_simpleModal_component;
simpleModal_component.__scopeId = 'data-v-d93dkeid1x';
/**
 * @preserve
 * This Vue component is a modal dialog that can be shown or hidden based on the `showModal` prop.
 *
 * Props:
 * @property {string} idModal - (required): The ID of the modal.
 * @property {boolean} showModal - (required): Determines whether the modal is visible.
 * @property {('max-w-md'|'max-w-lg'|'max-w-2xl'|'max-w-4xl'|'max-w-7xl')} [size='max-w-md'] - (optional): The size of the modal.
 *
 * Emits:
 * @event {Object} accion - Emits an object with the action to be performed.
 */
export default simpleModal_component;
//# sourceMappingURL=simpleModal.vue.js.map