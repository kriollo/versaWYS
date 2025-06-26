
            
            import { ref } from "/node_modules/vue/dist/vue.esm-browser.js";
            import { toRefs } from '/node_modules/vue/dist/vue.esm-browser.js';

    
const card_component = {
                

                __file: 'C:/Users/jjara/Desktop/proyectos/versaWYS-PHP/src/dashboard/js/components/card.vue',
                __name: 'card',
                name: 'card',
                components: {
                    
                },
            
                
  props: {
        size: {
            type: String,
            default: 'max-w-md',
        },
    },
  setup(__props, { expose: __expose }) {
  __expose();

            const versaComponentKey = ref(0);
            
    const props = __props;

    const { size } = toRefs(props);

const __returned__ = { versaComponentKey, props, size, get ref() { return ref }, toRefs }
Object.defineProperty(__returned__, '__isScriptSetup', { enumerable: false, value: true })
return __returned__
}

}
            import { renderSlot as _renderSlot, createCommentVNode as _createCommentVNode, createElementVNode as _createElementVNode, normalizeClass as _normalizeClass, openBlock as _openBlock, createElementBlock as _createElementBlock } from "/node_modules/vue/dist/vue.esm-browser.js"

function render_card_component(_ctx, _cache, $props, $setup, $data, $options) {
  return (_openBlock(), _createElementBlock("div", {
    class: _normalizeClass(["bg-white dark:bg-gray-800 shadow-sm dark:shadow-gray-700", $setup.size]),
    key: $setup.versaComponentKey
  }, [
    _createElementVNode("div", null, [
      _renderSlot(_ctx.$slots, "title"),
      _createCommentVNode(" Schedule Grid "),
      _renderSlot(_ctx.$slots, "body"),
      _createCommentVNode(" Action Button "),
      _renderSlot(_ctx.$slots, "footer")
    ])
  ], 2 /* CLASS */))
}
        

            card_component.render = render_card_component;
            
            

            export default card_component;        