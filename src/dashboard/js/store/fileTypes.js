'use strict';
export default {
    data() {
        return {
            fileTypes: [
                {
                    ext: 'jpg',
                    icon: 'bi bi-image',
                    color: 'text-green-800',
                    type: 'image/jpeg',
                },
                {
                    ext: 'jpeg',
                    icon: 'bi bi-image',
                    color: 'text-green-800',
                    type: 'image/jpeg',
                },
                {
                    ext: 'png',
                    icon: 'bi bi-image',
                    color: 'text-green-800',
                    type: 'image/png',
                },
                {
                    ext: 'svg',
                    icon: 'bi bi-image',
                    color: 'text-green-800',
                    type: 'image/svg+xml',
                },
                {
                    ext: 'gif',
                    icon: 'bi bi-image',
                    color: 'text-green-800',
                    type: 'image/gif',
                },
                {
                    ext: 'pdf',
                    icon: 'bi bi-file-earmark-pdf',
                    color: 'text-red-800',
                    type: 'application/pdf',
                },
                {
                    ext: 'doc',
                    icon: 'bi bi-file-earmark-word',
                    color: 'text-blue-800',
                    type: 'application/msword',
                },
                {
                    ext: 'docx',
                    icon: 'bi bi-file-earmark-word',
                    color: 'text-blue-800',
                    type: 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                },
                {
                    ext: 'xls',
                    icon: 'bi bi-file-earmark-excel',
                    color: 'text-green-800',
                    type: 'application/vnd.ms-excel',
                },
                {
                    ext: 'xlsx',
                    icon: 'bi bi-file-earmark-excel',
                    color: 'text-green-800',
                    type: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                },
                {
                    ext: 'ppt',
                    icon: 'bi bi-file-earmark-ppt',
                    color: 'text-red-800',
                    type: 'application/vnd.ms-powerpoint',
                },
                {
                    ext: 'pptx',
                    icon: 'bi bi-file-earmark-ppt',
                    color: 'text-red-800',
                    type: 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
                },
                {
                    ext: 'zip',
                    icon: 'fa-file-archive',
                    color: 'text-yellow-800',
                    type: 'application/zip',
                },
                {
                    ext: 'rar',
                    icon: 'fa-file-archive',
                    color: 'text-yellow-800',
                    type: 'application/x-rar-compressed',
                },
                {
                    ext: 'mp3',
                    icon: 'fa-file-audio',
                    color: 'text-yellow-800',
                    type: 'audio/mpeg',
                },
                {
                    ext: 'mp4',
                    icon: 'fa-file-video',
                    color: 'text-yellow-800',
                    type: 'video/mp4',
                },
                {
                    ext: 'txt',
                    icon: 'fa-file-alt',
                    color: 'text-yellow-800',
                    type: 'text/plain',
                },
                {
                    ext: 'csv',
                    icon: 'fa-file-csv',
                    color: 'text-yellow-800',
                    type: 'text/csv',
                },
            ],
            path_file_evento: '',
        };
    },
    methods: {
        setPathFileEvento(path) {
            this.path_file_evento = path;
        },
        getPathFileEvento() {
            return this.path_file_evento;
        },
    },
};
