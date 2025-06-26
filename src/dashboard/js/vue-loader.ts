import { html } from 'code-tag';
import { createApp, provide, ref } from 'vue';

import { $dom } from '@/dashboard/js/composables/dom';
import { GLOBAL_CONSTANTS } from '@/dashboard/js/constants';
import { isValidModuleName, sanitizeModulePath } from '@/dashboard/js/functions';
import { handleError, handleHMRError } from '@/dashboard/js/vueLoader/devUtils';

const debug = ref(!!$dom('#debug')); // Default to false if the element is not found

const loadModule = async (): Promise<void> => {
    const $contenedor = $dom('#main-content') as HTMLElement;
    const $csrfToken = $dom('#csrf_token') as HTMLInputElement;
    const $current_user = $dom('#current_user') as HTMLInputElement;

    const url = new URL(import.meta.url);
    const urlParams = url.search;
    const searchParams = new URLSearchParams(urlParams);
    let module = searchParams.get('m');
    let validatedModule = 'unknown';
    try {
        interface PublicProvider {
            debug: typeof debug;
            csrf_token: string;
            current_user: { [key: string]: unknown };
        }

        const public_provider: PublicProvider = {
            debug,
            csrf_token: $csrfToken.value || '',
            current_user: JSON.parse($current_user.value) || {},
        };

        if (!$contenedor) {
            throw new Error('No se encontró el contenedor para montar la aplicación Vue.');
        }
        if (!module || '' === module.trim() || 'undefined' === module || 'null' === module) {
            throw new Error('No se ha especificado un módulo para cargar.');
        }
        validatedModule = module;
        validatedModule = sanitizeModulePath(validatedModule);
        validatedModule = validatedModule.startsWith('/')
            ? validatedModule.slice(GLOBAL_CONSTANTS.ONE)
            : validatedModule;
        // Validar el parámetro del módulo
        if (!isValidModuleName(validatedModule)) {
            throw new Error('El parámetro del módulo contiene caracteres no permitidos.');
        }

        const component = validatedModule.split('/').pop() as string;
        if (!component) {
            throw new Error('No se ha especificado un componente para cargar.');
        } // Importar dinámicamente el módulo
        const moduleResponse = await import(`@/${validatedModule}.js?v=${Date.now()}`);
        if (moduleResponse) {
            // Montar el módulo en el contenedor
            const app = createApp({
                components: { [component]: moduleResponse.default },
                setup(): unknown {
                    // Configuración de la aplicación según el modo de depuración
                    if (debug.value) {
                        console.log('Debug mode is enabled');
                    }
                    const componentKey = ref(Date.now());
                    for (const key in public_provider) {
                        if (Object.hasOwn(public_provider, key)) {
                            provide(key, public_provider[key as keyof PublicProvider]);
                        }
                    }

                    return {
                        componentKey,
                    };
                },
                name: 'App',
                template: html`
                    <${component} :key="componentKey" />
                `,
            });

            // Configuración de la aplicación según el modo de depuración
            if (debug) {
                app.config.warnHandler = function warnHandler(msg, vm, trace): void {
                    // console.warn(msg, vm, trace);
                    handleHMRError(msg, trace);
                };
                app.config.errorHandler = function errorHandler(err, vm, info): void {
                    // console.error(err, vm, info);
                    handleHMRError(err instanceof Error ? err.stack || 'Unknown stack' : 'Unknown error', info);
                };
                app.config.compilerOptions.comments = true;
            } else {
                app.config.compilerOptions.comments = false;
            }
            app.config.performance = true;
            app.config.compilerOptions.whitespace = 'condense';

            app.mount($contenedor, true);
        }
    } catch (error) {
        handleError(error, validatedModule, $contenedor);
    }
};

loadModule();
