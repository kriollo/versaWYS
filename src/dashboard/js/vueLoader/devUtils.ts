import { $dom } from '@/dashboard/js/composables/dom';
import { html } from 'code-tag';

/**
 * Manejar errores durante la carga del módulo.
 * @param {any} error - El error ocurrido.
 * @param {string} module - El módulo que causó el error.
 */
export function handleError(
    error: any,
    module: string,
    container: HTMLElement,
): void {
    const errorMessage =
        error instanceof Error ? error.message : 'Error desconocido';
    const moduleInfo = module ? `Módulo: ${module}` : 'Módulo no especificado';
    const safeMessage = `${moduleInfo}<br>${errorMessage}`
        .replace(/</g, '<')
        .replace(/>/g, '>');
    let finalContent: HTMLElement;
    if (!container) {
        finalContent = $dom('body') as HTMLElement;
    } else {
        finalContent = container;
    }

    // Mostrar mensaje de error en el contenedor
    finalContent.textContent = ''; // Limpiar contenido previo

    finalContent.insertAdjacentHTML(
        'beforeend',
        html`
            <div
                style="padding: 1rem; background-color: #f44336; color: #fff;"
                id="error-container">
                <h2>Error crítico</h2>
                <p style="text-align: left;">${safeMessage}</p>
                <button
                    style="padding: 0.5rem 1rem; background-color: #fff; color: #333; border: 1px solid #333; cursor: pointer;"
                    onclick="window.location.reload()">
                    Reintentar
                </button>
            </div>
        `,
    );

    console.error('[Module Loader]', error);
}
export function handleHMRError(errorMessage: string, trace: any): void {
    const safeMessage = `${errorMessage}`
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;');
    // Escapa también la variable 'trace'
    const safeTrace = `${trace}`.replace(/</g, '&lt;').replace(/>/g, '&gt;');

    let ErrorContainer = $dom('#error-container');
    if (!ErrorContainer) {
        ErrorContainer = document.createElement('div');
        ErrorContainer.id = 'versa-hmr-error-overlay';
        ErrorContainer.classList.add(
            'fixed',
            'top-0',
            'left-0',
            'right-0',
            'z-50',
        );
        document.body.appendChild(ErrorContainer);

        ErrorContainer.insertAdjacentHTML(
            'beforeend',
            html`
                <div
                    class="p-4 bg-orange-400 dark:bg-orange-500 text-orange-900 dark:text-orange-100">
                    <h2 class="text-xl font-bold">VERSA ERROR</h2>
                </div>
                <div id="error-container"></div>
            `,
        );
    }
    const error = $dom('#error-container');
    if (ErrorContainer.hasAttribute('hidden')) error.innerHTML = '';

    // Mostrar mensaje de error en el contenedor (o área de notificación)
    error.insertAdjacentHTML(
        `beforeend`,
        html`
            <div class="p-4 bg-orange-300 text-orange-900">
                <pre class="whitespace-pre-wrap">${safeMessage}</pre>
                <pre><code>${safeTrace}</code></pre>
            </div>
        `,
    );
}
