
            
            import { useModel as _useModel, mergeModels as _mergeModels } from '/node_modules/vue/dist/vue.esm-browser.js'
import { ref } from "/node_modules/vue/dist/vue.esm-browser.js";
            import { computed } from '/node_modules/vue/dist/vue.esm-browser.js';

    
const buttonCheck_component = {
                

                __file: 'C:/Users/jjara/Desktop/proyectos/versaWYS-PHP/src/dashboard/js/components/buttonCheck.vue',
                __name: 'buttonCheck',
                name: 'buttonCheck',
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
    const active = computed(() => actives[props.type]);
    const id = computed(() => props.id);
    const label = computed(() => props.label);
    const disabled = computed(() => props.disabled);

    const types = {
        success: 'bg-green-650 focus:ring-green-400 hover:bg-green-700',
        danger: 'bg-red-500 focus:ring-red-400 hover:bg-red-700',
        warning: 'bg-yellow-500 focus:ring-yellow-400 hover:bg-yellow-700',
        primary: 'bg-blue-500 focus:ring-blue-400 hover:bg-blue-700',
        info: 'bg-gray-500 focus:ring-gray-400 hover:bg-gray-600',
    };
    const actives = {
        success: 'bg-green-700 hover:bg-green-700',
        danger: 'bg-red-700 hover:bg-red-700',
        warning: 'bg-yellow-700 hover:bg-yellow-700',
        primary: 'bg-blue-700 hover:bg-blue-700',
        info: 'bg-gray-800 hover:bg-gray-600',
    };

const __returned__ = { versaComponentKey, props, estado, type, active, id, label, disabled, types, actives, get ref() { return ref }, computed }
Object.defineProperty(__returned__, '__isScriptSetup', { enumerable: false, value: true })
return __returned__
}

}
            import { toDisplayString as _toDisplayString, normalizeClass as _normalizeClass, openBlock as _openBlock, createElementBlock as _createElementBlock } from "/node_modules/vue/dist/vue.esm-browser.js"

function render_buttonCheck_component(_ctx, _cache, $props, $setup, $data, $options) {
  return (_openBlock(), _createElementBlock("button", {
    class: _normalizeClass(["inline-flex justify-center px-4 py-2 text-sm font-medium text-white border border-transparent rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-offset-2 w-[140px]", $setup.estado ? $setup.active : $setup.type]),
    onClick: $event => ($setup.estado = !$setup.estado),
    key: $setup.versaComponentKey
  }, _toDisplayString($setup.label), 11 /* TEXT, CLASS, PROPS */, ["onClick"]))
}
        

            buttonCheck_component.render = render_buttonCheck_component;
            
            

            export default buttonCheck_component;        