import { $dom, $domAll } from '@/dashboard/js/composables/dom';
import { UI_BREAKPOINTS } from '@/dashboard/js/constants';
const $themeToggleDarkIcon = $dom('#theme-toggle-dark-icon');
const $themeToggleLightIcon = $dom('#theme-toggle-light-icon');
if ($themeToggleDarkIcon && $themeToggleLightIcon) {
    // Change the icons inside the button based on previous settings
    if ('dark' === localStorage.getItem('color-theme') ||
        (!('color-theme' in localStorage) && globalThis.matchMedia('(prefers-color-scheme: dark)').matches)) {
        $themeToggleLightIcon.classList.remove('hidden');
        document.documentElement.classList.add('dark');
    }
    else {
        $themeToggleDarkIcon.classList.remove('hidden');
        document.documentElement.classList.remove('dark');
    }
    const $themeToggleBtn = $dom('#theme-toggle');
    if ($themeToggleBtn) {
        const event = new Event('dark-mode');
        $themeToggleBtn.addEventListener('click', () => {
            const updateThemeState = () => {
                const isCurrentlyDark = document.documentElement.classList.contains('dark');
                if (isCurrentlyDark) {
                    document.documentElement.classList.remove('dark');
                    localStorage.setItem('color-theme', 'light');
                }
                else {
                    document.documentElement.classList.add('dark');
                    localStorage.setItem('color-theme', 'dark');
                }
                // Alternar iconos
                $themeToggleDarkIcon.classList.toggle('hidden');
                $themeToggleLightIcon.classList.toggle('hidden');
                document.dispatchEvent(event);
            };
            if (document.startViewTransition) {
                // Añadir una clase para indicar que es una transición de tema
                document.documentElement.classList.add('theme-transition');
                const transition = document.startViewTransition(() => {
                    updateThemeState();
                });
                // Limpiar la clase después de que la transición termine
                transition.finished.finally(() => {
                    document.documentElement.classList.remove('theme-transition');
                });
            }
            else {
                updateThemeState();
            }
        });
    }
}
// Función para configurar eventos de submenús
const setupSubmenuEvents = (button, dropdown, caret) => {
    let hoverTimeout = null;
    // Función para mostrar el submenú
    const showDropdown = () => {
        if (hoverTimeout) {
            clearTimeout(hoverTimeout);
            hoverTimeout = null;
        }
        dropdown.classList.remove('hidden');
        // Actualizar el icono del caret
        if (caret) {
            caret.classList.remove('bi-caret-right-fill');
            caret.classList.add('bi-caret-down-fill');
        }
    };
    // Función para ocultar el submenú con delay
    const hideDropdown = (delay = 200) => {
        if (hoverTimeout) {
            clearTimeout(hoverTimeout);
        }
        hoverTimeout = window.setTimeout(() => {
            dropdown.classList.add('hidden');
            // Actualizar el icono del caret
            if (caret) {
                caret.classList.remove('bi-caret-down-fill');
                caret.classList.add('bi-caret-right-fill');
            }
        }, delay);
    };
    // Función para toggle manual (click)
    const toggleDropdown = () => {
        if (dropdown.classList.contains('hidden')) {
            showDropdown();
        }
        else {
            hideDropdown(0); // Sin delay para el click
        }
    };
    // Eventos de hover para desktop
    if (window.innerWidth >= UI_BREAKPOINTS.DESKTOP_MIN_WIDTH) {
        // Hover en el botón principal
        button.addEventListener('mouseenter', showDropdown);
        button.addEventListener('mouseleave', () => hideDropdown());
        // Hover en el dropdown
        dropdown.addEventListener('mouseenter', () => {
            if (hoverTimeout) {
                clearTimeout(hoverTimeout);
                hoverTimeout = null;
            }
        });
        dropdown.addEventListener('mouseleave', () => hideDropdown());
    }
    // Evento de click (funciona en desktop y móvil)
    button.addEventListener('click', e => {
        e.preventDefault();
        toggleDropdown();
    });
};
// Configurar submenús del sidebar
const initializeSidebarSubmenus = () => {
    const $sidebar = $dom('#sidebar');
    if (!$sidebar) {
        return;
    }
    const $sidebarItems = $dom('.sidebar-item', $sidebar);
    if (!$sidebarItems) {
        return;
    }
    const $sidebarItemsButton = $domAll('.sidebar-item-button', $sidebarItems);
    for (const button of $sidebarItemsButton) {
        const buttonElement = button;
        const dropdownId = buttonElement.getAttribute('aria-controls');
        if (dropdownId) {
            const $dropdown = $dom(`#${dropdownId}`);
            if ($dropdown) {
                const $caret = $dom('[sidebar-item-i]', buttonElement);
                setupSubmenuEvents(buttonElement, $dropdown, $caret);
            }
        }
    }
};
initializeSidebarSubmenus();
const $toggleAside = $dom('#toggleAside');
if ($toggleAside instanceof HTMLElement && null !== $toggleAside) {
    const $toggleAsideHamburger = $dom('#toggleAsideHamburger');
    const $aside = $dom('#sidebar');
    const $maincontent = $dom('#main-content');
    const $sidebarBackdrop = $dom('#sidebarBackdrop');
    if (!$toggleAsideHamburger || !$aside || !$maincontent || !$sidebarBackdrop) {
        console.error('One or more elements are missing in the DOM.');
    }
    else {
        const toggleSidebarMobile = () => {
            $toggleAsideHamburger.classList.toggle('rotate-90');
            //obtener el ancho de la pantalla
            const width = window.innerWidth;
            //si el ancho de la pantalla es menor a 1024px (móvil)
            if (UI_BREAKPOINTS.MOBILE_MAX_WIDTH > width) {
                // En móvil, solo toggle la visibilidad del sidebar
                $aside.classList.toggle('hidden');
                $sidebarBackdrop.classList.toggle('hidden');
                // En móvil cuando se muestra, asegurar que esté expandido (w-56) para mostrar textos
                if (!$aside.classList.contains('hidden')) {
                    $aside.classList.remove('w-16');
                    $aside.classList.add('w-56');
                }
            }
            else if ($aside.classList.contains('w-56')) {
                // Contraer sidebar en desktop
                $aside.classList.remove('w-56');
                $aside.classList.add('w-16');
                $maincontent.classList.remove('lg:ml-56');
                $maincontent.classList.add('lg:ml-16');
            }
            else {
                // Expandir sidebar en desktop
                $aside.classList.remove('w-16');
                $aside.classList.add('w-56');
                $maincontent.classList.remove('lg:ml-16');
                $maincontent.classList.add('lg:ml-56');
            }
        };
        $sidebarBackdrop.addEventListener('click', () => {
            toggleSidebarMobile();
        });
        $toggleAside.addEventListener('click', () => {
            toggleSidebarMobile();
        });
    }
}
//# sourceMappingURL=dashboard.js.map