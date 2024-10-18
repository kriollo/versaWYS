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
            // toggle icons
            $themeToggleDarkIcon.classList.toggle('hidden');
            $themeToggleLightIcon.classList.toggle('hidden');

            // if set via local storage previously
            if (localStorage.getItem('color-theme')) {
                if (localStorage.getItem('color-theme') === 'light') {
                    document.documentElement.classList.add('dark');
                    localStorage.setItem('color-theme', 'dark');
                } else {
                    document.documentElement.classList.remove('dark');
                    localStorage.setItem('color-theme', 'light');
                }

                // if NOT set via local storage previously
            } else if (document.documentElement.classList.contains('dark')) {
                document.documentElement.classList.remove('dark');
                localStorage.setItem('color-theme', 'light');
            } else {
                document.documentElement.classList.add('dark');
                localStorage.setItem('color-theme', 'dark');
            }

            document.dispatchEvent(event);
        });
    }

    // const $sidebar = $dom('#sidebar');
    // if ($sidebar) {
    //     const toggleSidebarMobile = (sidebar, sidebarBackdrop) => {
    //         sidebar.classList.toggle('hidden');
    //         sidebarBackdrop.classList.toggle('hidden');
    //     };

    //     const $toggleSidebarMobileEl = $dom('#toggleSidebarMobile');
    //     const $sidebarBackdrop = $dom('#sidebarBackdrop');
    //     const $toggleSidebarMobileSearch = $dom('#toggleSidebarMobileSearch');

    //     if ($toggleSidebarMobileSearch) {
    //         $toggleSidebarMobileSearch.addEventListener('click', () => {
    //             toggleSidebarMobile($sidebar, $sidebarBackdrop);
    //         });
    //     }

    //     if ($toggleSidebarMobileEl) {
    //         $toggleSidebarMobileEl.addEventListener('click', () => {
    //             toggleSidebarMobile($sidebar, $sidebarBackdrop);
    //         });
    //     }

    //     if ($sidebarBackdrop) {
    //         $sidebarBackdrop.addEventListener('click', () => {
    //             toggleSidebarMobile($sidebar, $sidebarBackdrop);
    //         });
    //     }
    // }
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
                const $target = e.currentTarget;
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

            $asideItems.forEach(item => {
                item.classList.add('hidden');
            });
        } else {
            if ($aside.classList.contains('w-56')) {
                $aside.classList.remove('w-56');
                $aside.classList.add('w-16');
                $maincontent.classList.remove('lg:ml-56');
                $maincontent.classList.add('lg:ml-16');

                $asideItems.forEach(item => {
                    item.classList.add('hidden');
                });
            } else {
                $aside.classList.remove('w-16');
                $aside.classList.add('w-56');
                $maincontent.classList.remove('lg:ml-16');
                $maincontent.classList.add('lg:ml-56');

                $asideItems.forEach(item => {
                    item.classList.remove('hidden');
                });
            }
        }
    };

    $sidebarBackdrop.addEventListener('click', () => {
        toggleSidebarMobile();
    });

    $toggleAside.addEventListener('click', () => {
        toggleSidebarMobile();
    });
}
