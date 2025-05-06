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
        id?: number;
        urls?: any;
        redirect?: string;
        turnos?: any;
        urlZip?: string;
        acciones?: any;
        marca?: any;
        disabled?: boolean;
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
        files?: any;
        field?: string;
        newData?: any;
        id?: number;
        primeraLinea?: boolean;
        from?: string;
        hoja?: string;
    };

    type actionsType = {
        [key: string]: () => void;
    };

    type file = {
        archivo: string;
        type: string;
        size: number;
        file: File;
    };

    type Archivo = {
        data: string[];
        files: file;
        hoja: string;
        primeraLinea: boolean;
    };

    export {
        AccionData,
        actionsType,
        Archivo,
        SwalResult,
        VersaFetchResponse,
        VersaParamsFetch,
    };
}
