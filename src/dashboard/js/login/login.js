'strict mode';
import { versaAlert, versaFetch } from '../functions.js';

// Login Form
const login = document.getElementById('login');
if (login != null) {
    login.addEventListener('click', event => {
        event.preventDefault();

        const formulario = document.getElementById('formulario');
        if (!(formulario instanceof HTMLFormElement)) return;
        const datos = new FormData(formulario);

        const params = {
            url: '/admin/login/autentication',
            method: 'POST',
            data: datos,
        };

        versaFetch(params).then(data => {
            if (data.success == 0) {
                const alerta = document.getElementById('alert');

                let errores = `<ul class="max-w-md space-y-1 text-gray-500 list-disc list-inside dark:text-gray-400">`;
                for (const key in data.errors) {
                    errores += `<li>${data.errors[key]}</li>`;
                }
                errores += `</ul>`;

                alerta.innerHTML = `
                    <div id="alert-4" class="flex items-center p-4 mb-4 text-yellow-800 rounded-lg bg-yellow-50 dark:bg-gray-800 dark:text-yellow-300" role="alert">
                        <svg class="flex-shrink-0 w-4 h-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M10 .5a9.5 9.5 0 1 0 9.5 9.5A9.51 9.51 0 0 0 10 .5ZM9.5 4a1.5 1.5 0 1 1 0 3 1.5 1.5 0 0 1 0-3ZM12 15H8a1 1 0 0 1 0-2h1v-3H8a1 1 0 0 1 0-2h2a1 1 0 0 1 1 1v4h1a1 1 0 0 1 0 2Z"/>
                        </svg>
                        <span class="sr-only">Info</span>
                        <div class="ms-3 text-sm font-medium">
                            ${data.message}
                            <div>
                                ${errores}
                            </div>
                        </div>
                        <button type="button" class="ms-auto -mx-1.5 -my-1.5 bg-yellow-50 text-yellow-500 rounded-lg focus:ring-2 focus:ring-yellow-400 p-1.5 hover:bg-yellow-200 inline-flex items-center justify-center h-8 w-8 dark:bg-gray-800 dark:text-yellow-300 dark:hover:bg-gray-700" data-dismiss-target="#alert-4" aria-label="Close">
                            <span class="sr-only">Close</span>
                            <svg class="w-3 h-3" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 14 14">
                                <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m1 1 6 6m0 0 6 6M7 7l6-6M7 7l-6 6"/>
                            </svg>
                        </button>
                    </div>
                `;
                return false;
            }
            versaAlert({
                message: data.message,
                type: 'success',
                callback: () => {
                    window.location.href = '/admin/dashboard';
                },
            });
        });
    });
}

// Show Password
const showPass = document.getElementById('togglePassword');
const imgShowPass = document.getElementById('imgShowPass');
const imgHiddenPass = document.getElementById('imgHiddenPass');
const password = document.getElementById('password');

if (showPass != null) {
    showPass.addEventListener('click', event => {
        event.preventDefault();

        if (!(password instanceof HTMLInputElement)) return;
        if (password.type == 'password') {
            password.type = 'text';
            imgShowPass.classList.remove('hidden');
            imgHiddenPass.classList.add('hidden');
        } else {
            password.type = 'password';
            imgShowPass.classList.add('hidden');
            imgHiddenPass.classList.remove('hidden');
        }
    });
}

// Lost Password
const btnlostPass = document.getElementById('btnLostPass');
if (btnlostPass != null) {
    btnlostPass.addEventListener('click', event => {
        event.preventDefault();
        const alerta = document.getElementById('alert');
        const formLostPass = document.getElementById('formLostPass');

        if (!(formLostPass instanceof HTMLFormElement)) return;
        const datos = new FormData(formLostPass);

        if (!(btnlostPass instanceof HTMLButtonElement)) return;
        btnlostPass.disabled = true;
        btnlostPass.innerHTML = `
          <div role="status" class="flex items-center">
            <svg aria-hidden="true" class="w-8 h-8 text-gray-200 animate-spin dark:text-gray-600 fill-blue-600" viewbox="0 0 100 101" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M100 50.5908C100 78.2051 77.6142 100.591 50 100.591C22.3858 100.591 0 78.2051 0 50.5908C0 22.9766 22.3858 0.59082 50 0.59082C77.6142 0.59082 100 22.9766 100 50.5908ZM9.08144 50.5908C9.08144 73.1895 27.4013 91.5094 50 91.5094C72.5987 91.5094 90.9186 73.1895 90.9186 50.5908C90.9186 27.9921 72.5987 9.67226 50 9.67226C27.4013 9.67226 9.08144 27.9921 9.08144 50.5908Z" fill="currentColor"/><path d="M93.9676 39.0409C96.393 38.4038 97.8624 35.9116 97.0079 33.5539C95.2932 28.8227 92.871 24.3692 89.8167 20.348C85.8452 15.1192 80.8826 10.7238 75.2124 7.41289C69.5422 4.10194 63.2754 1.94025 56.7698 1.05124C51.7666 0.367541 46.6976 0.446843 41.7345 1.27873C39.2613 1.69328 37.813 4.19778 38.4501 6.62326C39.0873 9.04874 41.5694 10.4717 44.0505 10.1071C47.8511 9.54855 51.7191 9.52689 55.5402 10.0491C60.8642 10.7766 65.9928 12.5457 70.6331 15.2552C75.2735 17.9648 79.3347 21.5619 82.5849 25.841C84.9175 28.9121 86.7997 32.2913 88.1811 35.8758C89.083 38.2158 91.5421 39.6781 93.9676 39.0409Z" fill="currentFill"/></svg>
            <span class="ms-2">Enviando...</span>
          </div>
        `;
        alerta.innerHTML = '';

        // @ts-ignore
        const objectData = Object.fromEntries(datos.entries());

        const params = {
            url: '/admin/lost-password/send',
            method: 'PATCH',
            headers: {
                'content-type': 'application/json',
            },
            data: JSON.stringify(objectData),
        };
        versaFetch(params).then(data => {
            if (data.success == 0) {
                let errores = `<ul class="max-w-md space-y-1 text-gray-500 list-disc list-inside dark:text-gray-400">`;
                for (const key in data.errors) {
                    errores += `<li>${data.errors[key]}</li>`;
                }
                errores += `</ul>`;

                alerta.innerHTML = `
                    <div id="alert-4" class="flex items-center p-4 mb-4 text-yellow-800 rounded-lg bg-yellow-50 dark:bg-gray-800 dark:text-yellow-300" role="alert">
                        <svg class="flex-shrink-0 w-4 h-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M10 .5a9.5 9.5 0 1 0 9.5 9.5A9.51 9.51 0 0 0 10 .5ZM9.5 4a1.5 1.5 0 1 1 0 3 1.5 1.5 0 0 1 0-3ZM12 15H8a1 1 0 0 1 0-2h1v-3H8a1 1 0 0 1 0-2h2a1 1 0 0 1 1 1v4h1a1 1 0 0 1 0 2Z"/>
                        </svg>
                        <span class="sr-only">Info</span>
                        <div class="ms-3 text-sm font-medium">
                            ${data.message}
                            <div>
                                ${errores}
                            </div>
                        </div>
                        <button type="button" class="ms-auto -mx-1.5 -my-1.5 bg-yellow-50 text-yellow-500 rounded-lg focus:ring-2 focus:ring-yellow-400 p-1.5 hover:bg-yellow-200 inline-flex items-center justify-center h-8 w-8 dark:bg-gray-800 dark:text-yellow-300 dark:hover:bg-gray-700" data-dismiss-target="#alert-4" aria-label="Close">
                            <span class="sr-only">Close</span>
                            <svg class="w-3 h-3" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 14 14">
                                <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m1 1 6 6m0 0 6 6M7 7l6-6M7 7l-6 6"/>
                            </svg>
                        </button>
                    </div>
                `;

                btnlostPass.disabled = false;
                btnlostPass.innerHTML = 'Solicitar Reset';
                return false;
            }

            alerta.innerHTML = `
                <div id="alert-4" class="flex items-center p-4 mb-4 text-green-800 rounded-lg bg-green-50 dark:bg-gray-800 dark:text-green-300" role="alert">
                    <svg class="flex-shrink-0 w-4 h-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" clip-rule="evenodd" d="M10 18a8 8 0 1 0 0-16 8 8 0 0 0 0 16ZM9 6a1 1 0 0 1 2 0v5a1 1 0 1 1-2 0V6Zm1 11a1 1 0 1 1 0-2 1 1 0 0 1 0 2Z"/>
                    </svg>
                    <span class="sr-only">Info</span>
                    <div class="ms-3 text-sm font-medium">
                        ${data.message}
                    </div>
                    <button type="button" class="ms-auto -mx-1.5 -my-1.5 bg-green-50 text-green-500 rounded-lg focus:ring-2 focus:ring-green-400 p-1.5 hover:bg-green-200 inline-flex items-center justify-center h-8 w-8 dark:bg-gray-800 dark:text-green-300 dark:hover:bg-gray-700" data-dismiss-target="#alert-4" aria-label="Close">
                        <span class="sr-only">Close</span>
                        <svg class="w-3 h-3" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 14 14">
                            <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m1 1 6 6m0 0 6 6M7 7l6-6M7 7l-6 6"/>
                        </svg>
                    </button>
                </div>
            `;

            btnlostPass.disabled = false;
            btnlostPass.innerHTML = 'Solicitar Reset';
        });
    });
}

// Reset Password
const btnResetPass = document.getElementById('btnResetPass');
if (btnResetPass != null) {
    btnResetPass.addEventListener('click', event => {
        event.preventDefault();

        const FormResetPass = document.getElementById('FormResetPass');
        if (!(FormResetPass instanceof HTMLFormElement)) return;
        const datos = new FormData(FormResetPass);
        const alerta = document.getElementById('alert');
        alerta.innerHTML = '';

        const params = {
            url: '/admin/login/apply-reset-password',
            method: 'POST',
            data: datos,
        };

        if (!(btnResetPass instanceof HTMLButtonElement)) return;
        btnResetPass.disabled = true;
        versaFetch(params).then(data => {
            if (data.success == 0) {
                let errores = `<ul class="max-w-md space-y-1 text-gray-500 list-disc list-inside dark:text-gray-400">`;
                for (const key in data.errors) {
                    errores += `<li>${data.errors[key]}</li>`;
                }
                errores += `</ul>`;

                alerta.innerHTML = `
                    <div id="alert-4" class="flex items-center p-4 mb-4 text-yellow-800 rounded-lg bg-yellow-50 dark:bg-gray-800 dark:text-yellow-300" role="alert">
                        <svg class="flex-shrink-0 w-4 h-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M10 .5a9.5 9.5 0 1 0 9.5 9.5A9.51 9.51 0 0 0 10 .5ZM9.5 4a1.5 1.5 0 1 1 0 3 1.5 1.5 0 0 1 0-3ZM12 15H8a1 1 0 0 1 0-2h1v-3H8a1 1 0 0 1 0-2h2a1 1 0 0 1 1 1v4h1a1 1 0 0 1 0 2Z"/>
                        </svg>
                        <span class="sr-only">Info</span>
                        <div class="ms-3 text-sm font-medium">
                            ${data.message}
                            <div>
                                ${errores}
                            </div>
                        </div>
                        <button type="button" class="ms-auto -mx-1.5 -my-1.5 bg-yellow-50 text-yellow-500 rounded-lg focus:ring-2 focus:ring-yellow-400 p-1.5 hover:bg-yellow-200 inline-flex items-center justify-center h-8 w-8 dark:bg-gray-800 dark:text-yellow-300 dark:hover:bg-gray-700" data-dismiss-target="#alert-4" aria-label="Close">
                            <span class="sr-only">Close</span>
                            <svg class="w-3 h-3" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 14 14">
                                <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m1 1 6 6m0 0 6 6M7 7l6-6M7 7l-6 6"/>
                            </svg>
                        </button>
                    </div>
                `;
                btnResetPass.disabled = false;
                return false;
            }

            const divProgress = document.getElementById('divProgress');
            const progress = document.getElementById('progress');

            divProgress.classList.remove('hidden');
            let width = 0;
            const id = setInterval(frame, 10);
            function frame() {
                if (width >= 100) {
                    clearInterval(id);

                    alerta.innerHTML = `
                        <div id="alert-4" class="flex items-center p-4 mb-4 text-green-800 rounded-lg bg-green-50 dark:bg-gray-800 dark:text-green-300" role="alert">
                            <svg class="flex-shrink-0 w-4 h-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" clip-rule="evenodd" d="M10 18a8 8 0 1 0 0-16 8 8 0 0 0 0 16ZM9 6a1 1 0 0 1 2 0v5a1 1 0 1 1-2 0V6Zm1 11a1 1 0 1 1 0-2 1 1 0 0 1 0 2Z"/>
                            </svg>
                            <span class="sr-only">Info</span>
                            <div class="ms-3 text-sm font-medium">
                                ${data.message}
                            </div>
                            <button type="button" class="ms-auto -mx-1.5 -my-1.5 bg-green-50 text-green-500 rounded-lg focus:ring-2 focus:ring-green-400 p-1.5 hover:bg-green-200 inline-flex items-center justify-center h-8 w-8 dark:bg-gray-800 dark:text-green-300 dark:hover:bg-gray-700" data-dismiss-target="#alert-4" aria-label="Close">
                                <span class="sr-only">Close</span>
                                <svg class="w-3 h-3" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 14 14">
                                    <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m1 1 6 6m0 0 6 6M7 7l6-6M7 7l-6 6"/>
                                </svg>
                            </button>
                        </div>
                    `;

                    setTimeout(() => {
                        window.location.href = '/admin/login';
                    }, 1000);
                } else {
                    width++;
                    progress.style.width = width + '%';
                    // progress.innerHTML = width * 1 + '%';
                }
            }
        });
    });
}

// Show New Password
const togglePasswordNew = document.getElementById('togglePasswordNew');
const imgShowPassNew = document.getElementById('imgShowPassNew');
const imgHiddenPassNew = document.getElementById('imgHiddenPassNew');
const new_password = document.getElementById('new_password');

if (togglePasswordNew != null) {
    togglePasswordNew.addEventListener('click', event => {
        event.preventDefault();

        if (!(new_password instanceof HTMLInputElement)) return;
        if (new_password.type == 'password') {
            new_password.type = 'text';
            imgShowPassNew.classList.remove('hidden');
            imgHiddenPassNew.classList.add('hidden');
        } else {
            new_password.type = 'password';
            imgShowPassNew.classList.add('hidden');
            imgHiddenPassNew.classList.remove('hidden');
        }
    });
}

const togglePasswordConfirmNew = document.getElementById('togglePasswordConfirmNew');
const imgShowPassConfirmNew = document.getElementById('imgShowPassConfirmNew');
const imgHiddenPassConfirmNew = document.getElementById('imgHiddenPassConfirmNew');
const comfirm_new_password = document.getElementById('comfirm_new_password');

if (togglePasswordConfirmNew != null) {
    togglePasswordConfirmNew.addEventListener('click', event => {
        event.preventDefault();

        if (!(comfirm_new_password instanceof HTMLInputElement)) return;
        if (comfirm_new_password.type == 'password') {
            comfirm_new_password.type = 'text';
            imgShowPassConfirmNew.classList.remove('hidden');
            imgHiddenPassConfirmNew.classList.add('hidden');
        } else {
            comfirm_new_password.type = 'password';
            imgShowPassConfirmNew.classList.add('hidden');
            imgHiddenPassConfirmNew.classList.remove('hidden');
        }
    });
}
