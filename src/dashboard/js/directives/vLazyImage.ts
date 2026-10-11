const LOADED_ATTR = 'data-lazy-loaded';

function loadImage(el: HTMLImageElement): void {
    const { src } = el.dataset;
    if (!src || el.getAttribute(LOADED_ATTR) === src) {
        return;
    }
    el.src = src;
    el.setAttribute(LOADED_ATTR, src);
    el.addEventListener(
        'load',
        () => {
            el.classList.add('loaded');
        },
        { once: true },
    );
}

function observeImage(el: HTMLImageElement): void {
    if (!('IntersectionObserver' in window)) {
        loadImage(el);
        return;
    }

    const observer = new IntersectionObserver(
        (entries, obs) => {
            const [entry] = entries;
            if (!entry || !entry.isIntersecting) {
                return;
            }
            loadImage(el);
            obs.unobserve(el);
        },
        { rootMargin: '100px' },
    );
    observer.observe(el);
}

/**
 * Directiva v-lazy-image para Vue 3.
 *
 * Uso:
 *   <img v-lazy-image :data-src="url" src="" alt="..." />
 *
 * - Carga la imagen solo cuando el elemento entra al viewport (IntersectionObserver).
 * - Añade la clase "loaded" al completar la carga (útil para animaciones CSS).
 * - Fallback inmediato si IntersectionObserver no está disponible.
 * - El hook "updated" maneja el caso en que el mismo nodo DOM se reutiliza
 *   con un data-src distinto (ej. v-for con mismo key y producto diferente).
 */
const vLazyImage = {
    mounted(el: HTMLImageElement): void {
        observeImage(el);
    },
    updated(el: HTMLImageElement): void {
        const { src: newSrc } = el.dataset;
        if (!newSrc || el.getAttribute(LOADED_ATTR) === newSrc) {
            return;
        }
        // El nodo fue reutilizado con una imagen diferente — resetear y re-observar
        el.src = '';
        el.classList.remove('loaded');
        observeImage(el);
    },
};

export default vLazyImage;
