/**
 * Manejador de hover inteligente para submenús del sidebar
 * Mejora la experiencia de usuario al navegar entre menús y submenús
 */

let currentOpenDropdown: HTMLElement | null = null;
let _currentParentItem: HTMLElement | null = null;
let hoverTimeout: number | null = null;
let showTimeout: number | null = null;

const HOVER_DELAY = 250; // Ms de delay antes de ocultar
const SHOW_DELAY = 150; // Ms de delay antes de mostrar
const DROPDOWN_MARGIN = 8; // Separación mínima con los bordes de la ventana

/**
 * Ajusta un submenú para que siempre quepa en la ventana.
 *
 * Los submenús se abren a la derecha del ítem padre y, cuando el menú tiene muchas
 * opciones, se salían por abajo de la pantalla. Acá se pasan a `position: fixed`,
 * se limita su alto, se les da scroll interno y se los desplaza para que no queden
 * opciones inalcanzables. Al ser `fixed` no los recorta el scroll del sidebar.
 */
function fitDropdownToViewport(dropdown: HTMLElement, parentItem: HTMLElement): void {
    // Si el submenú no está desplegado (display:none) no hay nada que medir
    if (getComputedStyle(dropdown).display === 'none') {
        return;
    }

    const parentRect = parentItem.getBoundingClientRect();
    const nav = document.querySelector('nav');
    const navBottom = nav ? nav.getBoundingClientRect().bottom : 0;
    const topLimit = Math.max(DROPDOWN_MARGIN, navBottom + DROPDOWN_MARGIN);
    const bottomLimit = window.innerHeight - DROPDOWN_MARGIN;
    const maxAvailable = Math.max(1, bottomLimit - topLimit);

    // Medir el alto natural sin el límite anterior (el submenú está visible por el hover)
    dropdown.style.maxHeight = 'none';
    const naturalHeight = dropdown.scrollHeight;
    const height = Math.min(naturalHeight, maxAvailable);
    const { width } = dropdown.getBoundingClientRect();

    // Mantener el submenú alineado con el ítem padre, salvo que se salga por arriba o abajo
    let top = Math.max(topLimit, parentRect.top);
    if (top + height > bottomLimit) {
        top = bottomLimit - height;
    }
    top = Math.max(topLimit, top);

    // Que tampoco se salga por la derecha en pantallas angostas
    let left = parentRect.right + DROPDOWN_MARGIN;
    if (left + width > window.innerWidth - DROPDOWN_MARGIN) {
        left = window.innerWidth - DROPDOWN_MARGIN - width;
    }
    left = Math.max(DROPDOWN_MARGIN, left);

    dropdown.style.position = 'fixed';
    dropdown.style.maxHeight = `${height}px`;
    dropdown.style.overflowY = 'auto';
    dropdown.style.overscrollBehavior = 'contain';
    dropdown.style.top = `${top}px`;
    dropdown.style.left = `${left}px`;
}

/**
 * Oculta todos los dropdowns abiertos inmediatamente
 */
function hideAllDropdowns(): void {
    const allDropdowns = document.querySelectorAll<HTMLElement>('ul[id^="dropdown-"]');
    allDropdowns.forEach(dropdown => {
        dropdown.style.visibility = 'hidden';
        dropdown.style.opacity = '0';
        dropdown.style.pointerEvents = 'none';
    });

    // Ocultar todos los tooltips también
    const allTooltips = document.querySelectorAll<HTMLElement>('.tooltip-sidebar');
    allTooltips.forEach(tooltip => {
        tooltip.style.visibility = 'hidden';
        tooltip.style.opacity = '0';
    });

    currentOpenDropdown = null;
    _currentParentItem = null;
}

/**
 * Muestra un submenú con delay
 */
function showDropdown(dropdown: HTMLElement, parentItem: HTMLElement): void {
    // Cancelar cualquier timeout pendiente
    if (hoverTimeout) {
        clearTimeout(hoverTimeout);
        hoverTimeout = null;
    }

    if (showTimeout) {
        clearTimeout(showTimeout);
        showTimeout = null;
    }

    // Si ya hay un dropdown abierto diferente, cerrarlo inmediatamente
    if (currentOpenDropdown && currentOpenDropdown !== dropdown) {
        hideAllDropdowns();
    }

    // Ocultar el tooltip del item padre cuando se muestra el dropdown
    const tooltip = parentItem.querySelector<HTMLElement>('.tooltip-sidebar');
    if (tooltip) {
        tooltip.style.visibility = 'hidden';
        tooltip.style.opacity = '0';
    }

    showTimeout = window.setTimeout(() => {
        fitDropdownToViewport(dropdown, parentItem);
        dropdown.style.visibility = 'visible';
        dropdown.style.opacity = '1';
        dropdown.style.pointerEvents = 'auto';
        currentOpenDropdown = dropdown;
        _currentParentItem = parentItem;
        showTimeout = null;
    }, SHOW_DELAY);
}

/**
 * Oculta un submenú con delay
 */
function hideDropdown(dropdown: HTMLElement, parentItem?: HTMLElement): void {
    dropdown.style.visibility = 'hidden';
    dropdown.style.opacity = '0';
    dropdown.style.pointerEvents = 'none';

    if (currentOpenDropdown === dropdown) {
        currentOpenDropdown = null;
        _currentParentItem = null;
    } // Restaurar el tooltip si existe
    if (parentItem) {
        const tooltip = parentItem.querySelector<HTMLElement>('.tooltip-sidebar');
        if (tooltip && document.querySelector('#sidebar')?.classList.contains('w-16')) {
            tooltip.style.visibility = 'visible';
            tooltip.style.opacity = '1';
        }
    }
}

/**
 * Programa el ocultado de un submenú
 */
function scheduleHideDropdown(dropdown: HTMLElement, parentItem?: HTMLElement): void {
    if (showTimeout) {
        clearTimeout(showTimeout);
        showTimeout = null;
    }

    if (hoverTimeout) {
        clearTimeout(hoverTimeout);
    }

    hoverTimeout = window.setTimeout(() => {
        hideDropdown(dropdown, parentItem);
        hoverTimeout = null;
    }, HOVER_DELAY);
}

/**
 * Cancela el ocultado programado
 */
function cancelHideDropdown(): void {
    if (hoverTimeout) {
        clearTimeout(hoverTimeout);
        hoverTimeout = null;
    }

    if (showTimeout) {
        clearTimeout(showTimeout);
        showTimeout = null;
    }
}

/**
 * Inicializa los event listeners para los menús
 */
function initSidebarDropdowns(): void {
    const sidebarItems = document.querySelectorAll<HTMLElement>('.sidebar-item');

    // Primero ocultar todos los dropdowns
    hideAllDropdowns();

    // Si el sidebar tiene scroll, cerrar el submenú abierto para que no quede flotando
    const sidebarScroll = document.querySelector<HTMLElement>('.sidebar-scroll');
    if (sidebarScroll && sidebarScroll.dataset.scrollBound !== '1') {
        sidebarScroll.dataset.scrollBound = '1';
        sidebarScroll.addEventListener(
            'scroll',
            () => {
                if (currentOpenDropdown) {
                    hideAllDropdowns();
                }
            },
            { passive: true },
        );
    }

    sidebarItems.forEach(item => {
        const dropdown = item.querySelector<HTMLElement>('ul[id^="dropdown-"]');

        if (!dropdown) {
            return;
        }

        // Eventos del item del menú padre
        item.addEventListener('mouseenter', () => {
            cancelHideDropdown();
            showDropdown(dropdown, item);
        });

        item.addEventListener('mouseleave', event => {
            // Solo programar ocultado si no nos movemos al submenú
            const mouseEvent = event as MouseEvent;
            const relatedTarget = mouseEvent.relatedTarget as HTMLElement;
            if (!dropdown.contains(relatedTarget)) {
                scheduleHideDropdown(dropdown, item);
            }
        });

        // Eventos del submenú
        dropdown.addEventListener('mouseenter', () => {
            cancelHideDropdown();
        });

        dropdown.addEventListener('mouseleave', () => {
            scheduleHideDropdown(dropdown, item);
        });

        // Asegurar que el submenú esté oculto inicialmente
        hideDropdown(dropdown, item);
    });

    // Gestionar tooltips para items sin submenús
    const simpleItems = document.querySelectorAll<HTMLElement>('.sidebar-item:not(:has(ul[id^="dropdown-"]))');
    simpleItems.forEach(simpleItem => {
        const tooltip = simpleItem.querySelector<HTMLElement>('.tooltip-sidebar');
        if (!tooltip) {
            return;
        }

        simpleItem.addEventListener('mouseenter', () => {
            // Solo mostrar tooltip si el sidebar está contraído
            const sidebar = document.querySelector('#sidebar');
            if (sidebar?.classList.contains('w-16')) {
                tooltip.style.visibility = 'visible';
                tooltip.style.opacity = '1';
            }
        });

        simpleItem.addEventListener('mouseleave', () => {
            tooltip.style.visibility = 'hidden';
            tooltip.style.opacity = '0';
        });
    });
}

/**
 * Limpia los event listeners existentes
 */
function cleanup(): void {
    if (hoverTimeout) {
        clearTimeout(hoverTimeout);
        hoverTimeout = null;
    }
    if (showTimeout) {
        clearTimeout(showTimeout);
        showTimeout = null;
    }
    hideAllDropdowns();
}

// Reajustar el submenú abierto si cambia el tamaño de la ventana
window.addEventListener('resize', () => {
    if (currentOpenDropdown && _currentParentItem) {
        fitDropdownToViewport(currentOpenDropdown, _currentParentItem);
    }
});

// Inicializar cuando el DOM esté listo
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initSidebarDropdowns);
} else {
    initSidebarDropdowns();
}

// Re-inicializar después de navegación SPA
document.addEventListener('spa:component:updated', () => {
    cleanup();
    setTimeout(initSidebarDropdowns, 150);
});

// Cerrar todos los dropdowns al hacer clic fuera del sidebar
document.addEventListener('click', event => {
    const target = event.target as HTMLElement;
    const sidebar = document.querySelector('#sidebar');
    if (sidebar && !sidebar.contains(target)) {
        hideAllDropdowns();
    }
});

export default initSidebarDropdowns;
