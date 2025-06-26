import { html } from 'code-tag';

import { $dom, blockedForm, serializeToArray } from '@/dashboard/js/composables/dom.js';
import { API_RESPONSE_CODES } from '@/dashboard/js/constants';
import { versaAlert, versaFetch } from '@/dashboard/js/functions';

const $buttonResetPassword = $dom('#resetPassword');
if ($buttonResetPassword instanceof HTMLButtonElement) {
    $buttonResetPassword.addEventListener('click', async e => {
        e.preventDefault();
        const $formResetPass = $dom('#formResetPassword');
        if (!($formResetPass instanceof HTMLFormElement)) {
            return;
        }
        if ('true' === $formResetPass.dataset.locked) {
            return;
        }
        const __data: { [key: string]: unknown } = {};
        for (const x of serializeToArray($formResetPass)) {
            __data[x.name] = x.value;
        }
        blockedForm($formResetPass, 'true');

        const response = await versaFetch({
            url: $formResetPass.getAttribute('action') || '',
            method: 'POST',
            data: JSON.stringify(__data),
            headers: {
                'Content-Type': 'application/json',
            },
        });
        if (API_RESPONSE_CODES.ERROR === response.success) {
            const errores = html`
                <ul class="max-w-md space-y-1 text-gray-500 list-disc list-inside dark:text-gray-400">
                    ${Object.keys(response.errors)
                        .map(key => `<li>${response.errors[key]}</li>`)
                        .join('')}
                </ul>
            `;
            versaAlert({
                title: 'Error',
                message: response.message,
                type: 'error',
                html: errores,
            });
        } else {
            versaAlert({
                title: 'Éxito',
                message: response.message,
                type: 'success',
                callback: () => {
                    const expiratePass = document.cookie
                        .split(';')
                        .find(cookie => cookie.trim().startsWith('expiratePass'));
                    if (expiratePass) {
                        location.reload();
                    }
                    $formResetPass.reset();
                },
            });
        }
        blockedForm($formResetPass, 'false');
    });
}

const $fileAvatar = $dom('#fileAvatar');
if ($fileAvatar instanceof HTMLInputElement) {
    $fileAvatar.addEventListener('change', async e => {
        e.preventDefault();
        const $formAvatar = $dom('#formUpdateAvatar');
        if (!($formAvatar instanceof HTMLFormElement)) {
            return;
        }
        if ('true' === $formAvatar.dataset.locked) {
            return;
        }

        const formData = new FormData($formAvatar);
        blockedForm($formAvatar, 'true');
        const response = await versaFetch({
            url: $formAvatar.getAttribute('action') || '',
            method: 'POST',
            data: formData,
        });
        if (API_RESPONSE_CODES.ERROR === response.success) {
            versaAlert({
                title: 'Error',
                message: response.message,
                type: 'error',
            });
        } else {
            versaAlert({
                title: 'Éxito',
                message: response.message,
                type: 'success',
                callback: () => {
                    location.reload();
                },
            });
        }
        blockedForm($formAvatar, 'false');
    });
}
