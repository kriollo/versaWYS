import { $dom } from '@/dashboard/js/composables/dom';
import { handleError, handleHMRError } from '@/dashboard/js/vueLoader/devUtils';

import {
    isValidModuleName,
    sanitizeModulePath,
} from '@/dashboard/js/functions';
import { createApp, provide, ref } from 'vue';

interface PublicProvider {
    debug: typeof debug;
    csrf_token: string;
    current_user: Record<string, any>;
}

export const debug = ref($dom('#debug') ? true : false);
export let app: ReturnType<typeof createApp>;
const public_provider: PublicProvider = {
    debug,
    csrf_token: '',
    current_user: {},
};

const $contenedor = $dom('#main-content') as HTMLElement;

const url = new URL(import.meta.url);
const urlParams = url.search;
const searchParams = new URLSearchParams(urlParams);
let module = searchParams.get('m');

async function loadModule() {
    try {
        if (!$contenedor) {
            throw new Error(
                'No se encontró el contenedor para montar la aplicación Vue.',
            );
        }
        if (!module) {
            throw new Error('No se ha especificado un módulo para cargar.');
        }
        module = sanitizeModulePath(module);
        module = module.startsWith('/') ? module.slice(1) : module;
        // Validar el parámetro del módulo
        if (!isValidModuleName(module)) {
            throw new Error(
                'El parámetro del módulo contiene caracteres no permitidos.',
            );
        }

        const component = module.split('/').pop();
        // Importar dinámicamente el módulo
        const moduleResponse = await import(`@/${module}.js?v=${Date.now()}`);
        if (moduleResponse) {
            // Montar el módulo en el contenedor
            const app = createApp({
                components: { [component]: moduleResponse.default },
                setup() {
                    // Configuración de la aplicación según el modo de depuración
                    if (debug.value) {
                        console.log('Debug mode is enabled');
                    }
                    const componentKey = ref(Date.now());
                    for (const key in public_provider) {
                        provide(key, public_provider[key]);
                    }

                    return {
                        componentKey,
                    };
                },
                name: 'App',
                template: `<${component} :key="componentKey" />`,
            });

            // Configuración de la aplicación según el modo de depuración
            if (debug) {
                app.config.warnHandler = function (msg, vm, trace) {
                    // console.warn(msg, vm, trace);
                    handleHMRError(msg, trace);
                };
                app.config.errorHandler = function (err, vm, info) {
                    // console.error(err, vm, info);
                    handleHMRError(
                        err instanceof Error
                            ? err.stack || 'Unknown stack'
                            : 'Unknown error',
                        info,
                    );
                };
                app.config.compilerOptions.comments = true;
            } else {
                app.config.compilerOptions.comments = false;
            }
            app.config.performance = true;
            app.config.compilerOptions.whitespace = 'condense';

            app.mount($contenedor, true);

            // --- FRAGMENTO CLAVE PARA HMR Y ACCESO GLOBAL ---
            if (typeof window !== 'undefined') {
                // Exponemos la instancia de la app para HMR y depuración
                (window as any).__VUE_APP__ = app;
                // Exponemos el proxy raíz, útil para acceder a métodos, $forceUpdate, etc.
                (window as any).__VUE_APP_PROXY__ = app._instance?.proxy;
            }
        }
    } catch (e) {
        handleError(e, module, $contenedor);
    }
}

loadModule();
