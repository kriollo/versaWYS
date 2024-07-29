// @ts-ignore
import Swal from 'sweetalert2';
// @ts-ignore
const SwalStyles = await import(
    // @ts-ignore
    '@/vendor/sweetalert2/sweetalert2.dark.min.css',
    { with: { type: 'css' } }
);
document.adoptedStyleSheets = [...document.adoptedStyleSheets, SwalStyles.default];

const errorMap = new Map([
    [400, 'El Servidor no pudo procesar la solicitud'],
    [401, 'No está autorizado para acceder a este recurso'],
    [403, 'No tiene permisos para realizar esta acción'],
    [404, 'Recurso no encontrado'],
    [500, 'Error interno del servidor'],
    [503, 'Servicio no disponible'],
    [422, 'No se pudo procesar la solicitud'],
    [429, 'Demasiadas solicitudes, intente de nuevo más tarde'],
    [504, 'El tiempo de espera para el servicio ha sido excedido'],
    [302, 'La solicitud fue redirigida'],
]);

/**
 * Verifica si existe una cookie llamada 'debug'.
 * @returns {boolean} True si la cookie existe, de lo contrario False.
 */
export const existeCookieBuild = () => {
    const cookie = document.cookie.split(';').find(cookie => cookie.trim().startsWith('debug'));
    return cookie !== undefined;
};

const validateResponeStatus = async status => {
    let result = true;

    if (errorMap.has(status)) {
        Swal.fire({
            title: 'Error!',
            text: errorMap.get(status),
            icon: 'error',
            confirmButtonText: 'Aceptar',
        });
        //result = false;
    }

    return result;
};

/**
 * Performs a fetch request with the provided parameters.
 * @param {Object} params - The fetch parameters.
 * @param {string} params.url - The URL to fetch.
 * @param {string} params.method - The HTTP method to use.
 * @param {Object|HeadersInit} [params.headers = {}] - The headers to include in the request.
 * @param {FormData|Object|string} [params.data] - The data to send in the request body.
 * @param {('omit'|'same-origin'|'include')} [params.credentials='same-origin'] - The credentials mode for the request.
 * @returns {Promise} - A promise that resolves to the response data.
 * @throws {Error} - If the response status is not valid or an error occurs during the request.
 */
export const versaFetch = async params => {
    const { url, method, headers, data, credentials = 'same-origin' } = params;

    const init = {
        method: method,
        headers: headers || {},
        credentials: credentials,
    };

    if (
        typeof data === 'object' &&
        !(data instanceof FormData) &&
        (headers === null || headers === undefined)
    ) {
        // traspasar data a formdata
        const formData = new FormData();
        for (const key in data) {
            formData.append(key, data[key]);
        }
        init.body = formData;
    } else if (data) {
        init.body = data;
    }

    try {
        const response = await fetch(url, init);
        const contentType = response.headers.get('Content-Type');
        const isJson = contentType?.includes('application/json');
        const body = isJson ? await response.json() : await response.text();

        //await validateResponeStatus(response.status);
        // if (isJson) {
        //     throw new Error(JSON.stringify(body));
        // } else if (contentType?.includes('text/html') || contentType === null) {
        //     const message = errorMap.get(response.status);
        //     throw new Error(message);
        // }

        return body;
    } catch (e) {
        //devolver json para que se pueda utilizar con wait res.json()

        return JSON.stringify({
            success: 0,
            message: e.message,
        });
    }
};

/**
 * Returns the current date in the format "YYYY-MM-DD".
 * @returns {string} The current date in the format "YYYY-MM-DD".
 */
export const getDateToday = () => {
    const fecha = new Date();
    const año = fecha.getFullYear();
    const mes = String(fecha.getMonth() + 1).padStart(2, '0');
    const dia = String(fecha.getDate()).padStart(2, '0');
    return `${año}-${mes}-${dia}`;
};

/**
 * Returns the current date and time in the format "YYYY-MM-DD HH:MM:SS".
 * @returns {string} The current date and time.
 */
export const getDateTimeToday = () => {
    const fecha = new Date();
    const año = fecha.getFullYear();
    const mes = String(fecha.getMonth() + 1).padStart(2, '0');
    const dia = String(fecha.getDate()).padStart(2, '0');
    const hora = String(fecha.getHours()).padStart(2, '0');
    const minuto = String(fecha.getMinutes()).padStart(2, '0');
    const segundo = String(fecha.getSeconds()).padStart(2, '0');
    return `${año}-${mes}-${dia} ${hora}:${minuto}:${segundo}`;
};

/**
 * Returns the current time in the format "HH:MM:SS".
 * @returns {string} The current time.
 */
export const getTime = () => {
    const fecha = new Date();
    const hora = String(fecha.getHours()).padStart(2, '0');
    const minuto = String(fecha.getMinutes()).padStart(2, '0');
    const segundo = String(fecha.getSeconds()).padStart(2, '0');
    return `${hora}:${minuto}:${segundo}`;
};

/**
 * Displays a custom alert using the Swal library.
 *
 * @param {Object} Params - The parameters for the alert.
 * @param {string} [Params.title='¡Éxito!'] - The title of the alert.
 * @param {string} [Params.message=''] - The message of the alert.
 * @param {string} [Params.html=''] - The HTML content of the alert.
 * @param {string} [Params.type='success'] - The type of the alert icon.
 * @param {boolean} [Params.AutoClose=true] - Determines if the alert should automatically close after a certain time.
 * @param {function} [Params.callback] - The callback function to be executed when the alert is closed.
 */
export const versaAlert = Params => {
    const {
        title = '¡Éxito!',
        message = '',
        html = '',
        type = 'success',
        AutoClose = true,
        callback,
    } = Params;

    Swal.fire({
        title: title,
        text: message,
        html: html,
        icon: type,
        confirmButtonText: 'Aceptar',
        allowOutsideClick: true,
        allowEscapeKey: true,
        allowEnterKey: true,
        timer: AutoClose ? 3000 : null,
    }).then(result => {
        if (result) {
            if (callback) {
                callback();
            }
        }
    });
};

export const log = console.log.bind(console);

/**
 * Selects the first element that matches the specified selector within the given context.
 *
 * @param {string} selector - The CSS selector to match the element.
 * @param {Document|Element} [context=document] - The context within which to search for the element.
 * @returns {Element|null} - The first matching element, or null if no element is found.
 */
export const $ = (selector, context = document) => {
    return context.querySelector(selector);
};

/**
 * Returns a list of elements that match the given selector within the specified context.
 *
 * @param {string} selector - The CSS selector to match elements against.
 * @param {Document|Element} [context=document] - The context within which to search for elements. Defaults to the document.
 * @returns {NodeList} - A list of elements that match the selector.
 */
export const $$ = (selector, context = document) => {
    return context.querySelectorAll(selector);
};
