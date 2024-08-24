import { $, versaAlert, versaFetch } from '@/dashboard/js/functions';
import { html } from '@/vendor/code-tag/code-tag-esm.js';

const btnAddUser = $('#btnAddUser');
if (btnAddUser !== null) {
    btnAddUser.addEventListener('click', async () => {
        const formNewUser = $('#formNewUser');
        if (!(formNewUser instanceof HTMLFormElement)) return false;
        const formData = new FormData(formNewUser);

        const existsId = formData.get('tokenid') !== null;

        const objectData = Object.fromEntries(formData.entries());

        const paramsFetch = {
            url: existsId ? '/admin/users/editUser' : '/admin/users/addUser',
            method: existsId ? 'PUT' : 'POST',
            headers: {
                'content-type': 'application/json',
            },
            data: JSON.stringify(objectData),
        };

        const alerta = $('#alert');
        if (!(alerta instanceof HTMLDivElement)) return false;
        alerta.innerHTML = '';

        const data = await versaFetch(paramsFetch);
        if (data.success != '1') {
            let errores = `<ul class="max-w-md space-y-1 text-gray-500 list-disc list-inside dark:text-gray-400">`;
            for (const key in data.errors) {
                errores += `<li>${data.errors[key]}</li>`;
            }
            errores += `</ul>`;

            alerta.innerHTML = html`
                <div
                    id="alert-4"
                    class="flex items-center p-4 mb-4 text-yellow-800 rounded-lg bg-yellow-50 dark:bg-gray-800 dark:text-yellow-300"
                    role="alert">
                    <svg
                        class="flex-shrink-0 w-4 h-4"
                        aria-hidden="true"
                        xmlns="http://www.w3.org/2000/svg"
                        fill="currentColor"
                        viewBox="0 0 20 20">
                        <path
                            d="M10 .5a9.5 9.5 0 1 0 9.5 9.5A9.51 9.51 0 0 0 10 .5ZM9.5 4a1.5 1.5 0 1 1 0 3 1.5 1.5 0 0 1 0-3ZM12 15H8a1 1 0 0 1 0-2h1v-3H8a1 1 0 0 1 0-2h2a1 1 0 0 1 1 1v4h1a1 1 0 0 1 0 2Z" />
                    </svg>
                    <span class="sr-only">Info</span>
                    <div class="ms-3 text-sm font-medium">
                        ${data.message}
                        <div>${errores}</div>
                    </div>
                    <button
                        type="button"
                        class="ms-auto -mx-1.5 -my-1.5 bg-yellow-50 text-yellow-500 rounded-lg focus:ring-2 focus:ring-yellow-400 p-1.5 hover:bg-yellow-200 inline-flex items-center justify-center h-8 w-8 dark:bg-gray-800 dark:text-yellow-300 dark:hover:bg-gray-700"
                        data-dismiss-target="#alert-4"
                        aria-label="Close">
                        <span class="sr-only">Close</span>
                        <svg
                            class="w-3 h-3"
                            aria-hidden="true"
                            xmlns="http://www.w3.org/2000/svg"
                            fill="none"
                            viewBox="0 0 14 14">
                            <path
                                stroke="currentColor"
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="m1 1 6 6m0 0 6 6M7 7l6-6M7 7l-6 6" />
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
                window.location.href = '/admin/usuarios';
            },
        });
    });
    // Show Password
    const showPass = $('#togglePassword');
    const imgShowPass = $('#imgShowPass');
    const imgHiddenPass = $('#imgHiddenPass');
    const password = $('#password');

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
}
