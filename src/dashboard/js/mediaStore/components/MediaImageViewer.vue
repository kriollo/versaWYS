<script setup lang="ts">
    import { computed, onMounted, onUnmounted, ref } from 'vue';

    import type { MediaAsset } from '@/dashboard/js/mediaStore/types';

    interface Props {
        asset: MediaAsset;
        index: number;
        total: number;
    }

    interface Emits {
        close: [];
        previous: [];
        next: [];
    }

    const props = defineProps<Props>();
    const emit = defineEmits<Emits>();

    const closeButton = ref<HTMLButtonElement | null>(null);
    const viewer = ref<HTMLElement | null>(null);
    const counter = computed(() => `${String(props.index).padStart(2, '0')} / ${String(props.total).padStart(2, '0')}`);
    const previousDisabled = computed(() => props.total < 2);
    const nextDisabled = computed(() => props.total < 2);

    const handleGlobalKeydown = (event: KeyboardEvent) => {
        if (event.key === 'Escape') {
            emit('close');
            return;
        }

        if (event.key === 'ArrowLeft' && !previousDisabled.value) {
            event.preventDefault();
            emit('previous');
            return;
        }

        if (event.key === 'ArrowRight' && !nextDisabled.value) {
            event.preventDefault();
            emit('next');
        }
    };

    const trapTabKey = (event: KeyboardEvent) => {
        if (event.key !== 'Tab' || !viewer.value) {
            return;
        }

        const buttons = [...viewer.value.querySelectorAll<HTMLButtonElement>('button:not(:disabled)')];
        const [firstButton, ...remainingButtons] = buttons;
        const lastButton = remainingButtons.pop();
        const { activeElement } = document;

        if (event.shiftKey && (activeElement === firstButton || !viewer.value.contains(activeElement))) {
            event.preventDefault();
            lastButton?.focus();
        } else if (!event.shiftKey && (activeElement === lastButton || !viewer.value.contains(activeElement))) {
            event.preventDefault();
            firstButton?.focus();
        }
    };

    let previousBodyOverflow = '';

    onMounted(() => {
        previousBodyOverflow = document.body.style.overflow;
        document.body.style.overflow = 'hidden';
        closeButton.value?.focus();
        window.addEventListener('keydown', handleGlobalKeydown);
    });

    onUnmounted(() => {
        document.body.style.overflow = previousBodyOverflow;
        window.removeEventListener('keydown', handleGlobalKeydown);
    });
</script>

<template>
    <Teleport to="body">
        <div
            ref="viewer"
            role="dialog"
            aria-modal="true"
            aria-label="Visor de imágenes"
            tabindex="-1"
            class="media-image-viewer fixed inset-0 z-[100] flex flex-col bg-[#090b10] text-white"
            @keydown="trapTabKey"
            @click.self="emit('close')">
            <header class="flex items-center justify-between gap-4 border-b border-white/10 px-4 py-3 sm:px-7">
                <div class="min-w-0">
                    <p class="text-[10px] font-semibold uppercase tracking-[0.24em] text-white/45">Media Store</p>
                    <p
                        class="mt-1 max-w-[65vw] truncate text-sm font-medium text-white/90"
                        :title="asset.original_name">
                        {{ asset.original_name }}
                    </p>
                </div>

                <div class="flex shrink-0 items-center gap-4">
                    <span class="hidden font-mono text-xs tracking-widest text-white/55 sm:block">{{ counter }}</span>
                    <button
                        ref="closeButton"
                        type="button"
                        aria-label="Cerrar visor"
                        title="Cerrar (Esc)"
                        class="flex h-10 w-10 items-center justify-center rounded-full border border-white/15 bg-white/5 text-lg text-white/75 transition hover:border-white/35 hover:bg-white/10 hover:text-white focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-white"
                        @click="emit('close')">
                        <i class="bi bi-x-lg" aria-hidden="true"></i>
                    </button>
                </div>
            </header>

            <main class="relative flex min-h-0 flex-1 items-center justify-center overflow-hidden px-14 py-6 sm:px-24">
                <div class="pointer-events-none absolute inset-0 opacity-20" aria-hidden="true">
                    <div class="media-image-viewer__glow"></div>
                </div>

                <button
                    type="button"
                    aria-label="Imagen anterior"
                    title="Anterior (←)"
                    :disabled="previousDisabled"
                    class="absolute left-3 top-1/2 z-10 flex h-11 w-11 -translate-y-1/2 items-center justify-center rounded-full border border-white/15 bg-black/40 text-xl text-white/80 backdrop-blur transition hover:border-white/40 hover:bg-white/10 hover:text-white disabled:cursor-default disabled:opacity-30 sm:left-7 sm:h-12 sm:w-12"
                    @click="emit('previous')">
                    <i class="bi bi-chevron-left" aria-hidden="true"></i>
                </button>

                <img
                    :key="asset.id"
                    :src="asset.public_url"
                    :alt="asset.original_name"
                    class="media-image-viewer__image relative z-1 max-h-full max-w-full select-none object-contain"
                    draggable="false" />

                <button
                    type="button"
                    aria-label="Imagen siguiente"
                    title="Siguiente (→)"
                    :disabled="nextDisabled"
                    class="absolute right-3 top-1/2 z-10 flex h-11 w-11 -translate-y-1/2 items-center justify-center rounded-full border border-white/15 bg-black/40 text-xl text-white/80 backdrop-blur transition hover:border-white/40 hover:bg-white/10 hover:text-white disabled:cursor-default disabled:opacity-30 sm:right-7 sm:h-12 sm:w-12"
                    @click="emit('next')">
                    <i class="bi bi-chevron-right" aria-hidden="true"></i>
                </button>
            </main>

            <footer
                class="flex items-center justify-center gap-3 border-t border-white/10 px-4 py-3 text-[11px] tracking-wide text-white/45">
                <span class="font-mono sm:hidden">{{ counter }}</span>
                <span class="hidden sm:inline">Usa las flechas para navegar</span>
                <span class="hidden h-1 w-1 rounded-full bg-white/30 sm:block" aria-hidden="true"></span>
                <span class="hidden sm:inline">Esc para cerrar</span>
            </footer>
        </div>
    </Teleport>
</template>

<style scoped>
    .media-image-viewer {
        animation: viewer-appear 180ms ease-out;
    }

    .media-image-viewer__glow {
        position: absolute;
        inset: 10% 15%;
        background: radial-gradient(ellipse at center, rgb(83 96 119 / 24%), transparent 68%);
    }

    .media-image-viewer__image {
        animation: image-appear 220ms ease-out;
    }

    @keyframes viewer-appear {
        from {
            opacity: 0;
        }
        to {
            opacity: 1;
        }
    }

    @keyframes image-appear {
        from {
            opacity: 0.5;
            transform: scale(0.99);
        }
        to {
            opacity: 1;
            transform: scale(1);
        }
    }

    @media (prefers-reduced-motion: reduce) {
        .media-image-viewer,
        .media-image-viewer__image {
            animation: none;
        }
    }
</style>
