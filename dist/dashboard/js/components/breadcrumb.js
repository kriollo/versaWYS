import { defineComponent as _defineComponent } from '/node_modules/vue/dist/vue.esm-browser.js';
import { ref } from "/node_modules/vue/dist/vue.esm-browser.js";
import { toRefs } from '/node_modules/vue/dist/vue.esm-browser.js';
const breadcrumb_component = /*@__PURE__*/ _defineComponent({
    __file: 'C:/Users/jjara/Desktop/proyectos/versaWYS-PHP/src/dashboard/js/components/breadcrumb.vue',
    __name: 'breadcrumb',
    name: 'breadcrumb',
    components: {},
    props: {
        title: { type: String, required: true, default: 'Modulo' },
        iconSVG: { type: String, required: true, default: '<svg class="w-[32px] h-[32px] text-gray-800 dark:text-white" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewbox="0 0 24 24" strokewidth="{1.5}"><path fill-rule="evenodd" d="M8 4a4 4 0 1 0 0 8 4 4 0 0 0 0-8Zm-2 9a4 4 0 0 0-4 4v1a2 2 0 0 0 2 2h8a2 2 0 0 0 2-2v-1a4 4 0 0 0-4-4H6Zm7.25-2.095c.478-.86.75-1.85.75-2.905a5.973 5.973 0 0 0-.75-2.906 4 4 0 1 1 0 5.811ZM15.466 20c.34-.588.535-1.271.535-2v-1a5.978 5.978 0 0 0-1.528-4H18a4 4 0 0 1 4 4v1a2 2 0 0 1-2 2h-4.535Z" clip-rule="evenodd"/></svg>' },
        items: { type: Array, required: true }
    },
    setup(__props, { expose: __expose }) {
        __expose();
        const versaComponentKey = ref(0);
        const props = __props;
        const { items } = toRefs(props);
        const __returned__ = { versaComponentKey, props, items };
        Object.defineProperty(__returned__, '__isScriptSetup', { enumerable: false, value: true });
        return __returned__;
    }
});
import { createElementVNode as _createElementVNode, toDisplayString as _toDisplayString, renderList as _renderList, Fragment as _Fragment, openBlock as _openBlock, createElementBlock as _createElementBlock, createCommentVNode as _createCommentVNode } from "/node_modules/vue/dist/vue.esm-browser.js";
function render_breadcrumb_component(_ctx, _cache, $props, $setup, $data, $options) {
    return (_openBlock(), _createElementBlock("div", {
        class: "mx-4 my-4 lg:flex lg:justify-between max-sm:flex-col max-sm:flex-wrap",
        key: $setup.versaComponentKey
    }, [
        _createElementVNode("div", { class: "flex gap-2 items-center" }, [
            _createElementVNode("div", {
                innerHTML: $setup.props.iconSVG
            }, null, 8 /* PROPS */, ["innerHTML"]),
            _createElementVNode("h1", { class: "text-2xl font-semibold text-gray-900 dark:text-white" }, _toDisplayString($setup.props.title), 1 /* TEXT */)
        ]),
        _createElementVNode("nav", {
            class: "flex",
            "aria-label": "Breadcrumb"
        }, [
            _createElementVNode("ol", { class: "inline-flex items-center space-x-1 md:space-x-2 rtl:space-x-reverse" }, [
                (_openBlock(true), _createElementBlock(_Fragment, null, _renderList($setup.items, (item, index) => {
                    return (_openBlock(), _createElementBlock("li", { key: index }, [
                        (item.type === 'link')
                            ? (_openBlock(), _createElementBlock("div", {
                                key: 0,
                                class: "flex items-center"
                            }, [
                                _createElementVNode("div", {
                                    innerHTML: item.icon
                                }, null, 8 /* PROPS */, ["innerHTML"]),
                                (item.link !== '')
                                    ? (_openBlock(), _createElementBlock("a", {
                                        key: 0,
                                        href: item.link
                                    }, [
                                        _createElementVNode("span", { class: "inline-flex items-center text-sm font-medium text-gray-700 hover:text-blue-600 dark:text-gray-400 dark:hover:text-white" }, _toDisplayString(item.title), 1 /* TEXT */)
                                    ], 8 /* PROPS */, ["href"]))
                                    : _createCommentVNode("v-if", true)
                            ]))
                            : _createCommentVNode("v-if", true),
                        (item.type === 'text')
                            ? (_openBlock(), _createElementBlock("div", {
                                key: 1,
                                class: "flex items-center"
                            }, [
                                _createElementVNode("div", {
                                    innerHTML: item.icon
                                }, null, 8 /* PROPS */, ["innerHTML"]),
                                _createElementVNode("span", { class: "inline-flex items-center text-sm font-medium text-gray-700 dark:text-gray-400" }, _toDisplayString(item.title), 1 /* TEXT */)
                            ]))
                            : _createCommentVNode("v-if", true)
                    ]));
                }), 128 /* KEYED_FRAGMENT */))
            ])
        ])
    ]));
}
breadcrumb_component.render = render_breadcrumb_component;
export default breadcrumb_component;
//# sourceMappingURL=breadcrumb.vue.js.map