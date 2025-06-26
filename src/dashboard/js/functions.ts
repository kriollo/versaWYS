import { html } from 'code-tag';
import Swal from 'sweetalert2';

import {
    DATE_FORMAT,
    HTTP_STATUS,
    MYSQL_BOOLEAN,
    PAGINATION,
    TIME_CONVERSIONS,
    TIMEOUTS,
} from '@/dashboard/js/constants';
import type { VersaFetchResponse, VersaParamsFetch } from '@/dashboard/types/versaTypes.d';

// Tipos para la conversión de datos
type ConversionType = 'boolean' | 'number' | 'date' | 'string';

interface FieldConversion {
    key: string;
    type: ConversionType;
}

/**
 * Convierte los tipos de datos de un objeto según la configuración especificada
 * @param data - El objeto con los datos crudos (ej. de MySQL)
 * @param conversions - Array con las conversiones a aplicar [{ key: 'campo', type: 'boolean' }]
 * @returns El objeto con los tipos convertidos
 */
export const convertDataTypes = <T = any>(data: any, conversions: FieldConversion[]): T => {
    if (!data || typeof data !== 'object') {
        return data as T;
    }

    // Si es un array, aplicar conversión a cada elemento
    if (Array.isArray(data)) {
        return data.map(item => convertDataTypes(item, conversions)) as T;
    }

    // Clonar el objeto para no mutar el original
    const result = { ...data }; // Aplicar cada conversión
    conversions.forEach(({ key, type }) => {
        if (key in result) {
            const value = result[key];
            switch (type) {
                case 'boolean':
                    if (value === MYSQL_BOOLEAN.TRUE_STRING || value === MYSQL_BOOLEAN.TRUE_NUMBER) {
                        result[key] = true;
                    } else if (value === MYSQL_BOOLEAN.FALSE_STRING || value === MYSQL_BOOLEAN.FALSE_NUMBER) {
                        result[key] = false;
                    } else if (typeof value === 'string') {
                        result[key] = value.toLowerCase() === 'true';
                    }
                    break;

                case 'number': {
                    const numValue = typeof value === 'string' ? Number.parseFloat(value) : Number(value);
                    result[key] = Number.isNaN(numValue) ? value : numValue;
                    break;
                }

                case 'date':
                    if (value && typeof value === 'string') {
                        const dateValue = new Date(value);
                        result[key] = Number.isNaN(dateValue.getTime()) ? value : dateValue;
                    }
                    break;

                case 'string':
                    result[key] = String(value);
                    break;

                default:
                    // No hacer nada si el tipo no es reconocido
                    break;
            }
        }
    });

    return result as T;
};

const loadSwallCss = () => {
    const link = document.createElement('link');
    link.rel = 'stylesheet';
    link.href = 'P@/vendor/sweetalert2/sweetalert2.dark.min.css';
    document.head.append(link);
};
loadSwallCss();

const errorMap = new Map<number, string>([
    // [400, 'El Servidor no pudo procesar la solicitud'],
    // [401, 'No está autorizado para acceder a este recurso'],
    // [403, 'No tiene permisos para realizar esta acción'],
    [HTTP_STATUS.NOT_FOUND, 'Recurso no encontrado'],
    [HTTP_STATUS.INTERNAL_SERVER_ERROR, 'Error interno del servidor'],
    // [503, 'Servicio no disponible'],
    // [422, 'No se pudo procesar la solicitud'],
    // [429, 'Demasiadas solicitudes, intente de nuevo más tarde'],
    // [504, 'El tiempo de espera para el servicio ha sido excedido'],
    // [302, 'La solicitud fue redirigida'],
]);

/**
 * @preserve
 * Verifica si existe una cookie llamada 'debug'.
 * @returns {boolean} True si la cookie existe, de lo contrario False.
 */
export const existeCookieBuild = () => {
    const cookie = document.cookie.split(';').find(cookie => cookie.trim().startsWith('debug'));
    return cookie !== undefined;
};

const _validateResponeStatus = (/** @type {number} */ status) => {
    if (errorMap.has(status)) {
        Swal.fire({
            title: 'Error!',
            text: errorMap.get(status),
            icon: 'error',
            confirmButtonText: 'Aceptar',
        });
        return false;
    }

    return true;
};

/**
 * @preserve
 * Performs a fetch request with the provided parameters.
 * @param {VersaParamsFetch} params - The parameters for the fetch request.
 * @property {string} url - The URL to which the request will be made.
 * @property {'GET' | 'POST' | 'PUT' | 'DELETE' | 'PATCH'} method - The HTTP method to use for the request.
 * @property {Record<string, string> | HeadersInit} [headers] - The headers to include in the request.
 * @property {FormData | Record<string, any> | string} [data] - The data to include in the request.
 * @property {'omit' | 'same-origin' | 'include'} [credentials='same-origin'] - The credentials policy to use for the request.
 * @returns {Promise<VersaFetchResponse>} The response from the fetch request.
 */
export const versaFetch = async (params: VersaParamsFetch): Promise<VersaFetchResponse> => {
    const { url, method, headers, data, credentials = 'same-origin' } = params;
    const init: RequestInit = {
        method: method,
        headers: headers || {},
        credentials: credentials,
        body: null,
    };

    if (typeof data === 'object' && !(data instanceof FormData) && (headers === null || headers === undefined)) {
        // traspasar data a formdata
        const formData = new FormData();
        for (const key in data) {
            if (Object.hasOwn(data, key)) {
                formData.append(key, data[key]);
            }
        }
        init.body = formData;
    } else if (data) {
        init.body = data as BodyInit;
    }

    try {
        const response = await fetch(url, init);
        const contentType = response.headers.get('Content-Type');
        const isJson = contentType?.includes('application/json');
        const body = isJson ? await response.json() : await response.text();

        if (errorMap.has(response.status)) {
            if (isJson) {
                throw new Error(JSON.stringify(body));
            } else if (contentType?.includes('text/html') || contentType === null) {
                const message = errorMap.get(response.status);
                throw new Error(JSON.stringify({ success: 0, message: message }));
            }
        }

        return body;
    } catch (error: Error | any) {
        //devolver json para que se pueda utilizar con wait res.json()
        return JSON.parse(error.message);
    }
};

/**
 * @preserve
 * Returns the current date in the format "YYYY-MM-DD".
 * @returns {string} The current date in the format "YYYY-MM-DD".
 */
export const getDateToday = () => {
    const fecha = new Date();
    const año = fecha.getFullYear();
    const mes = String(fecha.getMonth() + DATE_FORMAT.MONTH_OFFSET).padStart(
        DATE_FORMAT.PAD_LENGTH,
        DATE_FORMAT.PAD_CHAR,
    );
    const dia = String(fecha.getDate()).padStart(DATE_FORMAT.PAD_LENGTH, DATE_FORMAT.PAD_CHAR);
    return `${año}-${mes}-${dia}`;
};

/**
 * @preserve
 * Gets the current year and month in the format 'YYYY-MM'.
 *
 * @returns {string} The formatted date string representing the current year and month.
 */
export const getAnnoMes = () => {
    const fecha = new Date();
    const año = fecha.getFullYear();
    const mes = String(fecha.getMonth() + DATE_FORMAT.MONTH_OFFSET).padStart(
        DATE_FORMAT.PAD_LENGTH,
        DATE_FORMAT.PAD_CHAR,
    );
    const fechaFormateada = `${año}-${mes}`;
    return fechaFormateada;
};

/**
 * @preserve
 * Gets the current year.
 *
 * @returns {number} The current year.
 */
export const getAnno = () => {
    const fecha = new Date();
    const año = fecha.getFullYear();
    return año;
};

/**
 * @preserve
 * Returns the current date and time in the format "YYYY-MM-DD HH:MM:SS".
 * @returns {string} The current date and time.
 */
export const getDateTimeToday = () => {
    const fecha = new Date();
    const año = fecha.getFullYear();
    const mes = String(fecha.getMonth() + DATE_FORMAT.MONTH_OFFSET).padStart(
        DATE_FORMAT.PAD_LENGTH,
        DATE_FORMAT.PAD_CHAR,
    );
    const dia = String(fecha.getDate()).padStart(DATE_FORMAT.PAD_LENGTH, DATE_FORMAT.PAD_CHAR);
    const hora = String(fecha.getHours()).padStart(DATE_FORMAT.PAD_LENGTH, DATE_FORMAT.PAD_CHAR);
    const minuto = String(fecha.getMinutes()).padStart(DATE_FORMAT.PAD_LENGTH, DATE_FORMAT.PAD_CHAR);
    const segundo = String(fecha.getSeconds()).padStart(DATE_FORMAT.PAD_LENGTH, DATE_FORMAT.PAD_CHAR);
    return `${año}-${mes}-${dia} ${hora}:${minuto}:${segundo}`;
};

/**
 * @preserve
 * Returns the current time in the format "HH:MM:SS".
 * @returns {string} The current time.
 */
export const getTime = () => {
    const fecha = new Date();
    const hora = String(fecha.getHours()).padStart(DATE_FORMAT.PAD_LENGTH, DATE_FORMAT.PAD_CHAR);
    const minuto = String(fecha.getMinutes()).padStart(DATE_FORMAT.PAD_LENGTH, DATE_FORMAT.PAD_CHAR);
    const segundo = String(fecha.getSeconds()).padStart(DATE_FORMAT.PAD_LENGTH, DATE_FORMAT.PAD_CHAR);
    return `${hora}:${minuto}:${segundo}`;
};

/**
 * @preserve
 * Añade un número específico de días a una fecha dada y devuelve la nueva fecha en formato YYYY-MM-DD.
 *
 * @param {string|Date} fecha - La fecha inicial a la que se le añadirán los días. Puede ser una cadena de texto en formato reconocible por Date o un objeto Date.
 * @param {number} dias - El número de días a añadir a la fecha.
 * @returns {string} La nueva fecha en formato YYYY-MM-DD.
 * @throws {Error} Si los parámetros de fecha y días no son válidos.
 */
export const addDias = (fecha, dias) => {
    // Verificar que los parámetros sean válidos
    if (!fecha || !dias || Number.isNaN(dias)) {
        throw new Error('Los parámetros de fecha y días son obligatorios y deben ser válidos.');
    }

    const fechaActual = new Date(fecha);
    fechaActual.setDate(fechaActual.getDate() + dias);

    // Obtener los valores de año, mes y día
    const { year, month, day } = {
        year: fechaActual.getFullYear(),
        month: String(fechaActual.getMonth() + DATE_FORMAT.MONTH_OFFSET).padStart(
            DATE_FORMAT.PAD_LENGTH,
            DATE_FORMAT.PAD_CHAR,
        ),
        day: String(fechaActual.getDate()).padStart(DATE_FORMAT.PAD_LENGTH, DATE_FORMAT.PAD_CHAR),
    };

    // Formatear la fecha en formato YYYY-MM-DD
    const fechaFormateada = `${year}-${month}-${day}`;
    return fechaFormateada;
};

export const diffDias = (/** @type {string} */ fecha1, /** @type {string} */ fecha2) => {
    const fecha1Date = new Date(fecha1);
    const fecha2Date = new Date(fecha2);
    const diffTime = Math.abs(fecha2Date.getTime() - fecha1Date.getTime()) + PAGINATION.DEFAULT_PAGE;
    return Math.ceil(
        diffTime /
            (TIME_CONVERSIONS.MS_TO_SECONDS *
                TIME_CONVERSIONS.SECONDS_TO_MINUTES *
                TIME_CONVERSIONS.MINUTES_TO_HOURS *
                TIME_CONVERSIONS.HOURS_TO_DAY),
    );
};

/**
 * @preserve
 * Displays a custom alert using the Swal library.
 *
 * @param {Object} Params - The parameters for the alert.
 * @param {string} [Params.title='¡Éxito!'] - The title of the alert.
 * @param {string} [Params.message=''] - The message of the alert.
 * @param {string} [Params.html=''] - The HTML content of the alert.
 * @param {string} [Params.type='success'] - The type of the alert icon.
 * @param {boolean} [Params.AutoClose=true] - Determines if the alert should automatically close after a certain time.
 * @param {function} [Params.callback] - The callback function to be executed when the alert is closed.
 * @param {Object} [Params.customClass={}] - The custom classes to apply to the alert.
 */
interface VersaAlertParams {
    title?: string;
    message?: string;
    html?: string;
    type?: 'success' | 'error' | 'warning' | 'info' | 'question';
    AutoClose?: boolean;
    callback?: () => void;
    customClass?: { [key: string]: string };
}

export const versaAlert = async (Params: VersaAlertParams): Promise<void> => {
    const {
        title = '¡Éxito!',
        message = '',
        html = '',
        type = 'success',
        AutoClose = true,
        callback,
        customClass = {},
    } = Params;

    const result = await Swal.fire({
        title: title,
        text: message,
        html: html,
        icon: type,
        confirmButtonText: 'Aceptar',
        allowOutsideClick: true,
        allowEscapeKey: true,
        allowEnterKey: true,
        timer: AutoClose ? TIMEOUTS.TOAST_AUTO_CLOSE : PAGINATION.ARRAY_FIRST_INDEX,
        customClass: customClass,
    });
    if (result) {
        if (callback) {
            callback();
        }
    }
};

export const log = console.log.bind(console);

/**
 * @preserve
 * Removes all backslashes from the given string.
 *
 * @param {string} str - The string from which to remove backslashes.
 * @returns {string} The resulting string with all backslashes removed.
 */
export const removeScape = (/** @type {string} */ str) => str.replaceAll(String.raw`/\/`, '');

/**
 * @preserve
 * Displays a custom alert using the Swal library.
 * @param {Object} Params - The parameters for the alert.
 * @param {string} [Params.title='¡Éxito!'] - The title of the alert.
 * @param {string} [Params.message=''] - The message of the alert.
 */
export const VersaToast = Swal.mixin({
    toast: true,
    position: 'top-right',
    iconColor: 'white',
    customClass: {
        popup: 'colored-toast',
    },
    showConfirmButton: false,
    timer: 1500,
    timerProgressBar: true,
});

/**
 * @preserve
 * Gets the current date and time in Unix timestamp format.
 *
 * @returns {number} The current Unix timestamp in seconds.
 */
export const getFechaUnix = () => {
    const fecha = new Date();
    return Math.floor(fecha.getTime() / TIME_CONVERSIONS.MS_TO_SECONDS);
};

/**
 * @preserve
 * Displays an error response using versaAlert.
 *
 * @param {object} response - The response object containing error information.
 * @param {object} [response.errors] - An optional object containing specific error messages.
 * @param {string} response.message - A general error message.
 * @param {string} [type='alert'] - The type of notification to display ('alert' or 'toast').
 */
export const showErrorResponse = (/** @type {object} */ response, type = 'alert') => {
    if (response?.errors === undefined) {
        if (type === 'toast') {
            VersaToast.fire({
                icon: 'error',
                title: response.message,
            });
        } else {
            versaAlert({
                title: 'Error',
                message: response.message,
                type: 'error',
            });
        }
        return;
    }
    const errores = html`
        <ul class="max-w-md space-y-1 text-gray-500 list-disc list-inside dark:text-gray-400">
            ${Object.keys(response.errors)
                .map(
                    key => html`
                        <li>${response.errors[key]}</li>
                    `,
                )
                .join('')}
        </ul>
    `;
    if (type === 'toast') {
        VersaToast.fire({
            icon: 'error',
            title: errores,
        });
    } else {
        versaAlert({
            title: 'Error',
            html: errores,
            type: 'error',
        });
    }
};

/**
 * Converts a 24-hour time string to a 12-hour time string with AM/PM.
 *
 * @param {number} timing - The value of the timing en miliseconds.
 * @returns {string} the timing in ms, seconds, minutes or hours.
 */
export const showTimingForHumans = timing => {
    if (timing < TIME_CONVERSIONS.MS_TO_SECONDS) {
        return `${timing} ms`;
    }
    if (timing < TIME_CONVERSIONS.MS_TO_MINUTES) {
        return `${timing / TIME_CONVERSIONS.MS_TO_SECONDS} s`;
    }
    if (timing < TIME_CONVERSIONS.MS_TO_HOURS) {
        return `${timing / TIME_CONVERSIONS.MS_TO_MINUTES} min`;
    }
    return `${timing / TIME_CONVERSIONS.MS_TO_HOURS} h`;
};

/**
 * Sanitize the module path to prevent directory traversal attacks.
 * @param {string} module - The module path to sanitize.
 * @returns {string} - The sanitized module path.
 */
export const sanitizeModulePath = (module: string): string => {
    return module
        .replaceAll(String.raw`\.\.\/`, '') // Eliminar ".."
        .replaceAll(/\/+/g, '/') // Normalizar barras
        .replaceAll(/[^a-zA-Z0-9/_-]/g, ''); // Eliminar caracteres no permitidos
};

type ModuleName = string & { __brand: 'ModuleName' };
export function isValidModuleName(module: string): module is ModuleName {
    const MODULE_NAME_REGEX = /^(?:@\/)?[a-zA-Z0-9][a-zA-Z0-9/_-]*[a-zA-Z0-9]$/;
    return MODULE_NAME_REGEX.test(module);
}
