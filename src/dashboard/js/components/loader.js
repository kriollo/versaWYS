import { app } from '@/dashboard/js/vue-instancia';
import { html } from '@/vendor/code-tag/code-tag-esm';

const loaderComponent = {
    template: html`
        <svg
            class="animate-spin p-1 h-6 w-6 text-white"
            fill="none"
            viewBox="0 0 24 24"
            xmlns="http://www.w3.org/2000/svg">
            <circle
                class="opacity-25 stroke-blue-900 dark:stroke-blue-100"
                cx="12"
                cy="12"
                r="10"
                stroke="currentColor"
                stroke-width="4"></circle>
            <path
                class="opacity-75"
                d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"
                fill="currentColor"></path>
        </svg>
    `,
};

export const loader = app.component('loader', loaderComponent);
