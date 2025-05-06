declare module 'gestionTypes' {
    type Gestion = {
        id: number;
        nombre: string;
        cargabase: boolean;
        cargamanual: boolean;
        encabezado: boolean;
        duplicada: boolean;
        modificarestado: boolean;
        encabezado: boolean;
        nbaseduplicados?: number;
        estado?: boolean;
    };

    type Columna = {
        id: number;
        nombre: string;
        tipo: string;
        ancho: number;
        alineacion: string;
        editable: boolean | string;
        estado: boolean | string;
        requerido: boolean | string;
        filtro: boolean | string;
        agrupar: boolean | string;
        orden: number;
    };

    type LoadTemplate = {
        showModal: boolean;
        template: { nombre: string; col: string }[];
        load: boolean;
    };

    type ejecutivo = {
        [key: number]: string;
    };
    type Ejecutivos = ejecutivos[];

    type FileUpload = {
        file: File | null;
        primeraLinea: boolean | null;
        hoja: string | null;
    };

    export { Columna, Ejecutivos, FileUpload, Gestion, LoadTemplate };
}
