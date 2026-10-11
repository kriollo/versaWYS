export type MediaType = 'image' | 'video' | 'audio' | 'file';

export interface MediaAsset {
    id: number;
    media_type: MediaType;
    original_name: string;
    public_url: string;
    mime: string;
    size_bytes: number;
    created_at?: string;
    created_by?: string;
    folder_id?: number | null;
    url?: string; // Alias de public_url para compatibilidad con componentes existentes
    path?: string; // Alias de public_url para compatibilidad con componentes existentes
}

export interface MediaFolder {
    id: number;
    name: string;
    parent_folder_id: number | null;
}
