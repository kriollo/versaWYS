import createInjection from '@/dashboard/js/composables/injectStrict';

export interface Empresa {
    action: 'create' | 'edit';
    id: number;
    rut: string;
    nombre: string;
    telefono: string;
    email: string;
    estado: boolean;
    csrf_token: string | undefined;
    logo?: string;
}
export interface ShowModalEmpresa {
    showModalEmpresa: boolean;
    itemSelected: Empresa | null;
    action: 'create' | 'edit';
}

export interface ShowModalAsociaModulo {
    showModalAsociaModulo: boolean;
    itemSelected: Empresa | null;
}

export const empresa: Empresa = {
    action: 'create',
    id: 0,
    nombre: '',
    rut: '',
    telefono: '',
    email: '',
    estado: true,
    csrf_token: undefined,
};

export const ShowModalEmpresaInjection = createInjection<ShowModalEmpresa>('ShowModalEmpresa');
export const showModalAsociaModuloInjection = createInjection<ShowModalAsociaModulo>('ShowModalAsociaModulo');
