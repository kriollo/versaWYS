import { $dom, $domAll } from '@/dashboard/js/composables/dom';

const $themeToggleDarkIcon = $dom('#theme-toggle-dark-icon');
const $themeToggleLightIcon = $dom('#theme-toggle-light-icon');

if ($themeToggleDarkIcon && $themeToggleLightIcon) {
    // Change the icons inside the button based on previous settings
    if (
        localStorage.getItem('color-theme') === 'dark' ||
        (!('color-theme' in localStorage) &&
            window.matchMedia('(prefers-color-scheme: dark)').matches)
    ) {
        $themeToggleLightIcon.classList.remove('hidden');
        document.documentElement.classList.add('dark');
    } else {
        $themeToggleDarkIcon.classList.remove('hidden');
        document.documentElement.classList.remove('dark');
    }

    const $themeToggleBtn = $dom('#theme-toggle');
    if ($themeToggleBtn) {
        const event = new Event('dark-mode');

        $themeToggleBtn.addEventListener('click', function () {
            const updateThemeState = () => {
                const isCurrentlyDark =
                    document.documentElement.classList.contains('dark');

                if (isCurrentlyDark) {
                    document.documentElement.classList.remove('dark');
                    localStorage.setItem('color-theme', 'light');
                } else {
                    document.documentElement.classList.add('dark');
                    localStorage.setItem('color-theme', 'dark');
                }

                // Alternar iconos
                $themeToggleDarkIcon.classList.toggle('hidden');
                $themeToggleLightIcon.classList.toggle('hidden');

                document.dispatchEvent(event);
            };

            if (!document.startViewTransition) {
                updateThemeState();
            } else {
                // Añadir una clase para indicar que es una transición de tema
                document.documentElement.classList.add('theme-transition');

                const transition = document.startViewTransition(() => {
                    updateThemeState();
                });

                // Limpiar la clase después de que la transición termine
                transition.finished.finally(() => {
                    document.documentElement.classList.remove(
                        'theme-transition',
                    );
                });
            }
        });
    }
}

const $sidebar = $dom('#sidebar');
if ($sidebar) {
    const $sidebarItems = $dom('.sidebar-item', $sidebar);
    if ($sidebarItems !== null) {
        const $sidebarItemsButton = $domAll(
            '.sidebar-item-button',
            $sidebarItems,
        );
        $sidebarItemsButton.forEach(button => {
            button.addEventListener('click', e => {
                e.preventDefault();
                const $target = e.currentTarget as HTMLElement;
                const $caret = $dom('[sidebar-item-i]', $target);
                if ($caret) {
                    if ($caret.classList.contains('bi-caret-right-fill')) {
                        $caret.classList.remove('bi-caret-right-fill');
                        $caret.classList.add('bi-caret-down-fill');
                    } else {
                        $caret.classList.remove('bi-caret-down-fill');
                        $caret.classList.add('bi-caret-right-fill');
                    }
                }
            });
        });
    }
}

const $toggleAside = $dom('#toggleAside');
if ($toggleAside instanceof HTMLElement && $toggleAside !== null) {
    const $toggleAsideHamburger = $dom('#toggleAsideHamburger');
    const $aside = $dom('#sidebar');
    const $maincontent = $dom('#main-content');
    const $sidebarBackdrop = $dom('#sidebarBackdrop');

    const $asideItems = $domAll('[sidebar-toggle-item]', $aside);

    const toggleSidebarMobile = () => {
        $toggleAsideHamburger.classList.toggle('rotate-90');
        $aside.classList.toggle('hidden');

        //obtener el ancho de la pantalla
        const width = window.innerWidth;
        //si el ancho de la pantalla es menor a 1024px
        if (width < 1024) {
            $sidebarBackdrop.classList.toggle('hidden');
            $aside.classList.remove('w-56');
            $aside.classList.add('w-16');
            $maincontent.classList.remove('lg:ml-56');
            $maincontent.classList.add('lg:ml-16');

            $asideItems.forEach((item: HTMLDivElement) => {
                item.classList.add('hidden');
            });
        } else if ($aside.classList.contains('w-56')) {
            $aside.classList.remove('w-56');
            $aside.classList.add('w-16');
            $maincontent.classList.remove('lg:ml-56');
            $maincontent.classList.add('lg:ml-16');

            $asideItems.forEach((item: HTMLDivElement) => {
                item.classList.add('hidden');
            });
        } else {
            $aside.classList.remove('w-16');
            $aside.classList.add('w-56');
            $maincontent.classList.remove('lg:ml-16');
            $maincontent.classList.add('lg:ml-56');

            $asideItems.forEach((item: HTMLDivElement) => {
                item.classList.remove('hidden');
            });
        }
    };

    $sidebarBackdrop.addEventListener('click', () => {
        toggleSidebarMobile();
    });

    $toggleAside.addEventListener('click', () => {
        toggleSidebarMobile();
    });
}
