import { $dom } from '@/dashboard/js/composables/dom';
import { getFechaUnix } from '@/dashboard/js/functions';
import { app, debug } from '@/dashboard/js/vue-instancia';
import { provide } from 'vue';

const public_provider = {
    debug,
    csrf_token: '',
    current_user: {},
};

document.addEventListener('DOMContentLoaded', () => {
    // Obtener la URL del módulo actual
    const scriptUrl = new URL(import.meta.url);
    const module = scriptUrl.searchParams.get('m');

    const inputToken = $dom('#csrf_token');
    public_provider.csrf_token =
        inputToken instanceof HTMLInputElement ? inputToken.value : '';

    const current_user = $dom('#current_user');
    public_provider.current_user =
        current_user instanceof HTMLInputElement
            ? JSON.parse(current_user.value)
            : {};

    if (!module) {
        console.error('No se ha especificado un módulo para cargar.');
        return;
    }

    // Validar el parámetro del módulo
    if (!/^[a-zA-Z0-9/_-]+$/.test(module)) {
        console.error(
            'El parámetro del módulo contiene caracteres no permitidos.',
        );
        return;
    }

    const component = module.split('/').pop();
    const $contenedor = '#main-content';

    // Importar dinámicamente el módulo
    import(`@/${module}.js?${getFechaUnix()}`)
        .then(() => {
            // Limpiar el contenedor antes de montar el nuevo componente
            const container = $dom($contenedor);
            if (container) {
                container.innerHTML = '';
            }

            // Registrar y montar el componente
            app.component('vue-loader-components', {
                setup() {
                    for (const key in public_provider) {
                        provide(key, public_provider[key]);
                    }
                },
                template: `<${component}/>`,
            });
        })
        .catch(error => {
            console.error('Error al cargar el módulo:', error);
        })
        .finally(() => {
            app.mount($contenedor);
        });
});
