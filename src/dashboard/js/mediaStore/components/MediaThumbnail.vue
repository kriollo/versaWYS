<script setup lang="ts">
    import { computed } from 'vue';

    import vLazyImage from '@/dashboard/js/directives/vLazyImage';
    import type { MediaType } from '@/dashboard/js/mediaStore/types';

    interface Props {
        type: MediaType;
        url: string;
        mime: string;
        className?: string;
    }

    const props = withDefaults(defineProps<Props>(), {
        className: 'w-16 h-16 object-cover rounded-lg',
    });

    const isImage = computed(() => props.type === 'image' || props.mime.startsWith('image/'));
    const isVideo = computed(() => props.type === 'video' || props.mime.startsWith('video/'));
    const isAudio = computed(() => props.type === 'audio' || props.mime.startsWith('audio/'));

    const fileIcon = computed(() => {
        if (props.mime.includes('pdf')) {
            return 'bi-file-earmark-pdf';
        }
        if (props.mime.includes('word') || props.mime.includes('doc')) {
            return 'bi-file-earmark-word';
        }
        if (props.mime.includes('excel') || props.mime.includes('sheet')) {
            return 'bi-file-earmark-excel';
        }
        if (props.mime.includes('zip') || props.mime.includes('rar')) {
            return 'bi-file-earmark-zip';
        }
        return 'bi-file-earmark';
    });
</script>

<template>
    <div
        :class="[
            'relative flex items-center justify-center bg-gray-100 dark:bg-gray-800 rounded-lg overflow-hidden border border-gray-200 dark:border-gray-700',
            className,
        ]">
        <template v-if="isImage">
            <img
                v-lazy-image
                :data-src="url"
                src=""
                alt="Thumbnail"
                class="lazy-image w-full h-full object-cover opacity-0 transition-opacity duration-300" />
        </template>

        <template v-else-if="isVideo">
            <div class="w-full h-full relative">
                <video :src="url" class="w-full h-full object-cover"></video>
                <div class="absolute inset-0 flex items-center justify-center bg-black/20">
                    <i class="bi bi-play-circle-fill text-white text-2xl drop-shadow-md"></i>
                </div>
            </div>
        </template>

        <template v-else-if="isAudio">
            <i class="bi bi-music-note-beamed text-2xl text-brand group-hover:scale-110 transition-transform"></i>
        </template>

        <template v-else>
            <i :class="['bi text-2xl text-gray-400 group-hover:scale-110 transition-transform', fileIcon]"></i>
        </template>
    </div>
</template>

<style scoped>
    .lazy-image.loaded {
        opacity: 1;
    }
</style>
