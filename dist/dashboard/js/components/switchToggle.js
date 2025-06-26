
            
            import { useModel as _useModel, mergeModels as _mergeModels } from '/node_modules/vue/dist/vue.esm-browser.js'
import { ref } from "/node_modules/vue/dist/vue.esm-browser.js";
            import { computed } from '/node_modules/vue/dist/vue.esm-browser.js';

    // Props del componente
    
const switchToggle_component = {
                

                __file: 'C:/Users/jjara/Desktop/proyectos/versaWYS-PHP/src/dashboard/js/components/switchToggle.vue',
                __name: 'switchToggle',
                name: 'switchToggle',
                components: {
                    
                },
            
                
  props: /*@__PURE__*/_mergeModels({
        id: {
            type: String,
            default: 'estado',
        },
        label: {
            type: String,
            default: '',
        },
        type: {
            type: String,
            default: 'success',
        },
        disabled: {
            type: Boolean,
            default: false,
        },
    }, {
    "modelValue": {},
    "modelModifiers": {},
  }),
  emits: ["update:modelValue"],
  setup(__props, { expose: __expose }) {
  __expose();

            const versaComponentKey = ref(0);
            
    const props = __props;

    const estado = _useModel(__props, "modelValue");
    const type = computed(() => types[props.type]);
    const id = computed(() => props.id);
    const label = computed(() => props.label);
    const disabled = computed(() => props.disabled);

    const types = {
        success: 'bg-green-600',
        primary: 'bg-blue-600',
        danger: 'bg-red-600',
        orange: 'bg-orange-600',
        purple: 'bg-purple-600',
    };

const __returned__ = { versaComponentKey, props, estado, type, id, label, disabled, types, get ref() { return ref }, computed }
Object.defineProperty(__returned__, '__isScriptSetup', { enumerable: false, value: true })
return __returned__
}

}
            import { createCommentVNode as _createCommentVNode, toDisplayString as _toDisplayString, createElementVNode as _createElementVNode, vModelCheckbox as _vModelCheckbox, withDirectives as _withDirectives, normalizeClass as _normalizeClass, openBlock as _openBlock, createElementBlock as _createElementBlock } from "/node_modules/vue/dist/vue.esm-browser.js"

function render_switchToggle_component(_ctx, _cache, $props, $setup, $data, $options) {
  return (_openBlock(), _createElementBlock("div", {
    class: "flex flex-col items-center gap-2",
    key: $setup.versaComponentKey
  }, [
    _createCommentVNode(" Label "),
    _createElementVNode("label", {
      for: $setup.id,
      class: "text-sm font-medium text-gray-900 dark:text-white"
    }, _toDisplayString($setup.label), 9 /* TEXT, PROPS */, ["for"]),
    _createCommentVNode(" Toggle Container "),
    _createElementVNode("div", {
      class: _normalizeClass([
                'relative inline-flex items-center h-6 w-11 cursor-pointer rounded-full transition',
                $setup.disabled ? 'opacity-50 cursor-not-allowed' : 'cursor-pointer',
            ]),
      "aria-disabled": $setup.disabled,
      onClick: $event => (!$setup.disabled && ($setup.estado = !$setup.estado))
    }, [
      _withDirectives(_createElementVNode("input", {
        id: $setup.id,
        type: "checkbox",
        class: "hidden",
        "onUpdate:modelValue": $event => (($setup.estado) = $event)
      }, null, 8 /* PROPS */, ["id", "onUpdate:modelValue"]), [
        [_vModelCheckbox, $setup.estado]
      ]),
      _createCommentVNode(" Fondo del switch "),
      _createElementVNode("span", {
        class: _normalizeClass([
                    'absolute inset-0 rounded-full transition',
                    $setup.estado ? $setup.type : 'bg-gray-200 dark:bg-gray-600',
                ])
      }, null, 2 /* CLASS */),
      _createCommentVNode(" Círculo del toggle "),
      _createElementVNode("span", {
        class: _normalizeClass([
                    'inline-block h-4 w-4 transform rounded-full bg-white transition',
                    $setup.estado ? 'translate-x-5' : 'translate-x-1',
                ])
      }, null, 2 /* CLASS */)
    ], 10 /* CLASS, PROPS */, ["aria-disabled", "onClick"])
  ]))
}
        

            switchToggle_component.render = render_switchToggle_component;
            
            

            export default switchToggle_component;        