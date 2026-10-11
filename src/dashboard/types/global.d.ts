/**
 * Declaraciones de tipos globales para el proyecto versaWYS
 */

// Extender atributos HTML para incluir nuestros atributos personalizados
declare module '@vue/runtime-dom' {
    interface HTMLAttributes {
        'data-spa'?: boolean | string;
    }
}

// Extender atributos de Vue para elementos específicos
declare module '@vue/runtime-core' {
    interface AllowedComponentProps {
        'data-spa'?: boolean | string;
    }
}

// Declaraciones para propiedades del window object
declare global {
    interface Window {
        // Flowbite functions
        initFlowbite?: () => void;
        initDropdowns?: () => void;
        initModals?: () => void;
        initTooltips?: () => void;
        initPopovers?: () => void;
        initTabs?: () => void;
        initAccordions?: () => void;
        initCarousels?: () => void;
        initDismisses?: () => void;
        initDrawers?: () => void;
        initCollapses?: () => void;
        initDials?: () => void;
        initInputCounters?: () => void;

        // Bootstrap
        bootstrap?: any;

        // Custom functions
        initThemeToggle?: () => void;
        initSidebar?: () => void;
        initPageSpecific?: () => void;
        initCustomComponents?: () => void;
        initDashboard?: () => void;
        initLogin?: () => void;
        initForm?: () => void;

        // Flowbite object
        Flowbite?: any;

        // Popper.js
        Popper?: any;

        // Vue app manager
        vueAppManager?: {
            unmountCurrent(): void;
            mount(app: any, container: HTMLElement): void;
            getCurrentApp(): any;
        };
        loadVueModule?: (modulePath: string) => Promise<void>;
    }
}
