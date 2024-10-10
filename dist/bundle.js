// sfc-script:C:\Nextcloud\htdocs\versaWYS-PHP\src\dashboard\js\components\modal.vue?type=script
import { computed, ref, watch } from 'vue';
var modal_default = {
    __name: 'modal',
    props: {
        idModal: {
            type: String,
            required: true,
        },
        showModal: {
            type: Boolean,
            required: true,
        },
        size: {
            type: String,
            default: 'max-w-md',
        },
    },
    emits: ['accion'],
    setup(__props, { expose: __expose, emit: __emit }) {
        __expose();
        const props = __props;
        const emit = __emit;
        const showModal = computed(() => props.showModal);
        const idModal = computed(() => props.idModal);
        const size = computed(() => props.size);
        const modal = ref(null);
        watch(showModal, val => {
            if (val) {
                modal.value.classList.remove('hidden');
                modal.value.classList.add('flex');
                modal.value.removeAttribute('inert');
            } else {
                modal.value.classList.remove('flex');
                modal.value.classList.add('hidden');
                modal.value.setAttribute('inert', '');
            }
        });
        const closeModal = () => {
            emit('accion', {
                accion: 'closeModal',
            });
        };
        const __returned__ = {
            props,
            emit,
            showModal,
            idModal,
            size,
            modal,
            closeModal,
            computed,
            ref,
            watch,
        };
        Object.defineProperty(__returned__, '__isScriptSetup', {
            enumerable: false,
            value: true,
        });
        return __returned__;
    },
};

// sfc-template:C:\Nextcloud\htdocs\versaWYS-PHP\src\dashboard\js\components\modal.vue?type=template
import {
    createCommentVNode as _createCommentVNode,
    createElementBlock as _createElementBlock,
    createElementVNode as _createElementVNode,
    normalizeClass as _normalizeClass,
    openBlock as _openBlock,
    renderSlot as _renderSlot,
} from 'vue';
var _hoisted_1 = ['id'];
var _hoisted_2 = {
    class: 'relative bg-white rounded-lg shadow dark:bg-gray-700',
};
var _hoisted_3 = {
    class: 'p-4 md:p-5 border-b rounded-t dark:border-gray-600',
};
var _hoisted_4 = { class: 'p-4 md:p-5 space-y-4' };
var _hoisted_5 = {
    class: 'flex justify-between gap-2 items-center p-4 md:p-5 border-t border-gray-200 rounded-b dark:border-gray-600',
};
function render(_ctx, _cache, $props, $setup, $data, $options) {
    return (
        _openBlock(),
        _createElementBlock(
            'div',
            {
                class: 'hidden overflow-y-auto overflow-x-hidden fixed top-0 right-0 left-0 z-50 justify-center items-center w-full md:inset-0 h-[calc(100%-1rem)] max-h-full backdrop-blur drop-shadow-versaWYS',
                id: $setup.idModal,
                'aria-hidden': 'true',
                ref: 'modal',
                tabindex: '-1',
            },
            [
                _createElementVNode(
                    'div',
                    {
                        class: _normalizeClass([
                            'relative p-4 w-full max-h-full',
                            $setup.size,
                        ]),
                    },
                    [
                        _createCommentVNode(' Modal content '),
                        _createElementVNode('div', _hoisted_2, [
                            _createCommentVNode(' Modal header '),
                            _createElementVNode('div', _hoisted_3, [
                                _renderSlot(_ctx.$slots, 'modalTitle'),
                            ]),
                            _createCommentVNode(' Modal body '),
                            _createElementVNode('div', _hoisted_4, [
                                _renderSlot(_ctx.$slots, 'modalBody'),
                            ]),
                            _createCommentVNode(' Modal footer '),
                            _createElementVNode('div', _hoisted_5, [
                                _renderSlot(_ctx.$slots, 'modalFooter'),
                            ]),
                        ]),
                    ],
                    2,
                    /* CLASS */
                ),
            ],
            8,
            _hoisted_1,
        )
    );
}

// src/dashboard/js/components/modal.vue
modal_default.render = render;
modal_default.__file = 'src\\dashboard\\js\\components\\modal.vue';
var modal_default2 = modal_default;
export { modal_default2 as default };
