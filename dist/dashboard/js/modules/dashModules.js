import { defineComponent as _defineComponent } from '/node_modules/vue/dist/vue.esm-browser.js';
import { ref } from "/node_modules/vue/dist/vue.esm-browser.js";
import { reactive } from '/node_modules/vue/dist/vue.esm-browser.js';
import breadcrumb from '@/dashboard/js/components/breadcrumb.vue';
import lineHr from '@/dashboard/js/components/lineHr.vue';
import { ShowModalFormInjection, } from '@/dashboard/js/modules/InjectKeys';
import modulesForm from '@/dashboard/js/modules/modulesForm.vue';
import modulesList from '@/dashboard/js/modules/modulesList.vue';
const dashModules_component = /*@__PURE__*/ _defineComponent({
    __file: 'C:/Users/jjara/Desktop/proyectos/versaWYS-PHP/src/dashboard/js/modules/dashModules.vue',
    __name: 'dashModules',
    name: 'dashModules',
    components: {
        breadcrumb,
        lineHr,
        modulesForm,
        modulesList
    },
    setup(__props, { expose: __expose }) {
        __expose();
        const versaComponentKey = ref(0);
        const showModalForm = reactive({
            showModalForm: false,
            itemSelected: null,
            action: '',
        });
        ShowModalFormInjection.provide(showModalForm);
        const breadCrumb = [
            {
                type: 'link',
                title: 'Home',
                icon: '<svg class="w-3 h-3 me-2.5" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg"><path d="m19.707 9.293-2-2-7-7a1 1 0 0 0-1.414 0l-7 7-2 2a1 1 0 0 0 1.414 1.414L2 10.414V18a2 2 0 0 0 2 2h3a1 1 0 0 0 1-1v-4a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1v4a1 1 0 0 0 1 1h3a2 2 0 0 0 2-2v-7.586l.293.293a1 1 0 0 0 1.414-1.414Z"/></svg>',
                link: '/admin/dashboard',
            },
            {
                type: 'link',
                title: 'Módulos',
                icon: '<svg class="w-3 h-3 text-gray-400 mx-1" fill="none" viewBox="0 0 6 10" xmlns="http://www.w3.org/2000/svg"><path d="m1 9 4-4-4-4" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"/></svg>',
                link: '/admin/modulesRoutes',
            },
            {
                type: 'text',
                title: 'Mantenedor de Módulos',
                icon: '<svg class="w-3 h-3 text-gray-400 mx-1" fill="none" viewBox="0 0 6 10" xmlns="http://www.w3.org/2000/svg"><path d="m1 9 4-4-4-4" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"/></svg>',
                link: '',
            },
        ];
        const __returned__ = { versaComponentKey, showModalForm, breadCrumb, breadcrumb, lineHr, modulesForm, modulesList };
        Object.defineProperty(__returned__, '__isScriptSetup', { enumerable: false, value: true });
        return __returned__;
    }
});
import { resolveComponent as _resolveComponent, createVNode as _createVNode, createElementVNode as _createElementVNode, openBlock as _openBlock, createElementBlock as _createElementBlock } from "/node_modules/vue/dist/vue.esm-browser.js";
function render_dashModules_component(_ctx, _cache, $props, $setup, $data, $options) {
    const _component_breadcrumb = _resolveComponent("breadcrumb");
    const _component_lineHr = _resolveComponent("lineHr");
    const _component_modulesForm = _resolveComponent("modulesForm");
    const _component_modulesList = _resolveComponent("modulesList");
    return (_openBlock(), _createElementBlock("div", {
        class: "w-full h-full flex flex-col",
        key: $setup.versaComponentKey
    }, [
        _createVNode(_component_breadcrumb, {
            title: "Módulos",
            iconSVG: "<svg\r\n                    aria-hidden=\"true\"\r\n                    class=\"w-6 h-6 text-gray-800 dark:text-white\"\r\n                    fill=\"currentColor\"\r\n                    height=\"24\"\r\n                    viewBox=\"0 0 24 24\"\r\n                    width=\"24\"\r\n                    xmlns=\"http://www.w3.org/2000/svg\">\r\n                    <path\r\n                        clip-rule=\"evenodd\"\r\n                        d=\"M4.857 3A1.857 1.857 0 0 0 3 4.857v4.286C3 10.169 3.831 11 4.857 11h4.286A1.857 1.857 0 0 0 11 9.143V4.857A1.857 1.857 0 0 0 9.143 3H4.857Zm10 0A1.857 1.857 0 0 0 13 4.857v4.286c0 1.026.831 1.857 1.857 1.857h4.286A1.857 1.857 0 0 0 21 9.143V4.857A1.857 1.857 0 0 0 19.143 3h-4.286Zm-10 10A1.857 1.857 0 0 0 3 14.857v4.286C3 20.169 3.831 21 4.857 21h4.286A1.857 1.857 0 0 0 11 19.143v-4.286A1.857 1.857 0 0 0 9.143 13H4.857Zm10 0A1.857 1.857 0 0 0 13 14.857v4.286c0 1.026.831 1.857 1.857 1.857h4.286A1.857 1.857 0 0 0 21 19.143v-4.286A1.857 1.857 0 0 0 19.143 13h-4.286Z\"\r\n                        fill-rule=\"evenodd\" />\r\n                </svg>",
            items: $setup.breadCrumb
        }),
        _createElementVNode("div", { class: "flex-1 relative shadow-md sm:rounded-lg mx-4 overflow-y-auto" }, [
            _createVNode(_component_lineHr),
            _createVNode(_component_modulesForm),
            _createVNode(_component_modulesList)
        ])
    ]));
}
dashModules_component.render = render_dashModules_component;
export default dashModules_component;
//# sourceMappingURL=dashModules.vue.js.map