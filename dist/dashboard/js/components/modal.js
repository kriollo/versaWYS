(function () {
    let styleTag = document.createElement('style');
    styleTag.setAttribute('data-v-bx64wulzk2', '');
    styleTag.innerHTML = `
.v-enter-active[data-v-bx64wulzk2],
    .v-leave-active[data-v-bx64wulzk2] {
        transition: opacity 0.3s ease;
}
.v-enter-from[data-v-bx64wulzk2],
    .v-leave-to[data-v-bx64wulzk2] {
        opacity: 0;
}
`;
    document.head.appendChild(styleTag);
})();
import { defineComponent as _defineComponent } from '/node_modules/vue/dist/vue.esm-browser.js';
import { computed, ref } from '/node_modules/vue/dist/vue.esm-browser.js';
import { GLOBAL_CONSTANTS } from '@/dashboard/js/constants';
const modal_component = /*@__PURE__*/ _defineComponent({
    __file: 'C:/Users/jjara/Desktop/proyectos/versaWYS-PHP/src/dashboard/js/components/modal.vue',
    __name: 'modal',
    name: 'modal',
    components: {},
    props: {
        idModal: { type: String, required: true },
        showModal: { type: Boolean, required: true, default: true },
        size: { type: String, required: false, default: 'max-w-md' },
        showFooter: { type: Boolean, required: false, default: true }
    },
    emits: ['accion'],
    setup(__props, { expose: __expose, emit: __emit }) {
        __expose();
        const versaComponentKey = ref(0);
        const props = __props;
        const emit = __emit;
        const componentKey = ref(GLOBAL_CONSTANTS.ZERO);
        const showModal = computed(() => props.showModal);
        const idModal = computed(() => props.idModal);
        const size = computed(() => props.size);
        const showFooter = computed(() => props.showFooter);
        const modal = ref(undefined);
        const __returned__ = { versaComponentKey, props, emit, componentKey, showModal, idModal, size, showFooter, modal };
        Object.defineProperty(__returned__, '__isScriptSetup', { enumerable: false, value: true });
        return __returned__;
    }
});
import { createCommentVNode as _createCommentVNode, renderSlot as _renderSlot, createElementVNode as _createElementVNode, openBlock as _openBlock, createElementBlock as _createElementBlock, normalizeClass as _normalizeClass, Transition as _Transition, withCtx as _withCtx, createBlock as _createBlock } from "/node_modules/vue/dist/vue.esm-browser.js";
function render_modal_component(_ctx, _cache, $props, $setup, $data, $options) {
    return (_openBlock(), _createBlock(_Transition, {
        mode: "in-out",
        key: $setup.versaComponentKey
    }, {
        default: _withCtx(() => [
            ($setup.showModal)
                ? (_openBlock(), _createElementBlock("div", {
                    key: 0,
                    class: "flex overflow-y-auto overflow-x-hidden fixed top-0 right-0 left-0 z-50 justify-center items-center w-full md:inset-0 h-[calc(100%-1rem)] max-h-full backdrop-blur drop-shadow-versaWYS",
                    id: $setup.idModal,
                    ref: "modal",
                    tabindex: "-1"
                }, [
                    _createElementVNode("div", {
                        class: _normalizeClass(["relative p-4 w-full max-h-full", $setup.size])
                    }, [
                        _createCommentVNode(" Modal content "),
                        _createElementVNode("div", { class: "relative bg-white rounded-lg shadow dark:bg-gray-700" }, [
                            _createCommentVNode(" Modal header "),
                            _createElementVNode("div", { class: "p-4 md:p-3 border-b rounded-t dark:border-gray-600" }, [
                                _renderSlot(_ctx.$slots, "modalTitle", {}, undefined, true)
                            ]),
                            _createCommentVNode(" Modal body "),
                            _createElementVNode("div", { class: "p-4 md:p-5 space-y-4" }, [
                                _renderSlot(_ctx.$slots, "modalBody", {}, undefined, true)
                            ]),
                            _createCommentVNode(" Modal footer "),
                            ($setup.showFooter)
                                ? (_openBlock(), _createElementBlock("div", {
                                    key: 0,
                                    class: "flex justify-between gap-2 items-center p-4 md:p-5 border-t border-gray-200 rounded-b dark:border-gray-600"
                                }, [
                                    _renderSlot(_ctx.$slots, "modalFooter", {}, undefined, true)
                                ]))
                                : _createCommentVNode("v-if", true)
                        ])
                    ], 2 /* CLASS */)
                ], 8 /* PROPS */, ["id"]))
                : _createCommentVNode("v-if", true)
        ]),
        _: 3 /* FORWARDED */
    }));
}
modal_component.render = render_modal_component;
modal_component.__scopeId = 'data-v-bx64wulzk2';
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
export default modal_component;
//# sourceMappingURL=modal.vue.js.map