import { $dom } from '@/dashboard/js/composables/dom';
import { getFechaUnix } from '@/dashboard/js/functions';
import { app, debug } from '@/dashboard/js/vue-instancia';
import { provide } from 'vue';

document.addEventListener('DOMContentLoaded', () => {
    // Obtener la URL del módulo actual
    const scriptUrl = new URL(import.meta.url);
    const module = scriptUrl.searchParams.get('m');

    const inputToken = $dom('#csrf_token');
    if (!(inputToken instanceof HTMLInputElement))
        throw new Error('Token no encontrado');
    const csrf_token = inputToken ? inputToken.value : '';

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
    const $contenedor = '.content-wrapper';

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
                    provide('debug', debug);
                    provide('csrf_token', csrf_token);
                },
                template: `<${component}/>`,
            });
        })
        .catch(error => {
            console.error('Error al cargar el módulo:', error);
        })
        .finally(() => {
            app.mount('.content-wrapper');
        });
});
