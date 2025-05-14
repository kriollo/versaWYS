import { createInjection } from '@/dashboard/js/composables/injectStrict';
// import type { InjectionKey } from 'vue';
export interface ShowModalSubForm {
    showModalSubForm: boolean;
    itemSelected: any;
    action: string;
}

export const ShowModalSubFormInjection =
    createInjection<ShowModalSubForm>('ShowModalSubForm');

export interface ShowModalForm {
    showModalForm: boolean;
    itemSelected: any;
    action: string;
}
export const ShowModalFormInjection =
    createInjection<ShowModalForm>('ShowModalForm');
