export type VersaParamsFetch = {
    url: string;
    method: 'GET' | 'POST' | 'PUT' | 'DELETE' | 'PATCH';
    headers?: Record<string, string> | HeadersInit;
    data?: FormData | Record<string, any> | string;
    credentials?: 'omit' | 'same-origin' | 'include';
};

export type VersaFetchResponse = {
    success: number;
    message: string;
    data?: any;
    errors?: any;
    id?: number;
    urls?: any;
    redirect?: string;
    turnos?: any;
    urlZip?: string;
    acciones?: any;
    marca?: any;
    disabled?: boolean;
};

export type SwalResult = {
    isConfirmed: boolean;
    value?: any;
};

export type AccionData = {
    accion: string;
    item?: GrupoItem;
    tipo?: string;
    data?: any;
    files?: any;
    field?: string;
    newData?: any;
    id?: number;
    primeraLinea?: boolean;
    from?: string;
    hoja?: string;
};

export type actionsType = {
    [key: string]: () => void;
};

export type file = {
    archivo: string;
    type: string;
    size: number;
    file: File;
};

export type Archivo = {
    data: string[];
    files: file;
    hoja: string;
    primeraLinea: boolean;
};

declare module 'versaTypes' {
    export { AccionData, actionsType, Archivo, SwalResult, VersaFetchResponse, VersaParamsFetch };
}
