export interface VersaParamsFetch {
    url: string;
    method: 'GET' | 'POST' | 'PUT' | 'DELETE' | 'PATCH';
    headers?: Record<string, string> | HeadersInit;
    data?: FormData | Record<string, any> | string;
    credentials?: 'omit' | 'same-origin' | 'include';
    skipSessionValidation?: boolean;
}

export interface VersaFetchResponse {
    success: number;
    message: string;
    data?: any;
    errors?: any;
    id?: number;
    urls?: any;
    redirect?: string;
    acciones?: any;
    disabled?: boolean;
    total?: number;
    page?: number;
}

export interface SwalResult {
    isConfirmed: boolean;
    value?: any;
}

export interface AccionData {
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
}

// Alias recomendado usando Record para mapear acciones por clave
export type actionsType = Record<string, () => void>;

export interface file {
    archivo: string;
    type: string;
    size: number;
    file: File;
}

export interface Archivo {
    data: string[];
    files: file;
    hoja: string;
    primeraLinea: boolean;
}

declare module 'versaTypes' {
    export { AccionData, actionsType, Archivo, SwalResult, VersaFetchResponse, VersaParamsFetch };
}
