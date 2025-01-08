declare module 'versaTypes' {
    type VersaParamsFetch = {
        url: string;
        method: 'GET' | 'POST' | 'PUT' | 'DELETE' | 'PATCH';
        headers?: Record<string, string> | HeadersInit;
        data?: FormData | Record<string, any> | string;
        credentials?: 'omit' | 'same-origin' | 'include';
    };

    type VersaFetchResponse = {
        success: number;
        message: string;
        data?: any;
        errors?: any;
        redirect?: string;
        urls?: { url: string; nombre: string }[];
    };

    type SwalResult = {
        isConfirmed: boolean;
        value?: any;
    };

    type AccionData = {
        accion: string;
        item?: GrupoItem;
        tipo?: string;
        data?: any;
        primeraLinea?: string;
        files?: any;
        field?: string;
        newData?: any;
        id?: number;
    };

    type actionsType = {
        [key: string]: () => void;
    };

    export {
        AccionData,
        actionsType,
        SwalResult,
        VersaFetchResponse,
        VersaParamsFetch,
    };
}
