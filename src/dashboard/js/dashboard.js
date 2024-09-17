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

    const $sidebar = $dom('#sidebar');
    if ($sidebar) {
        const toggleSidebarMobile = (
            sidebar,
            sidebarBackdrop,
            toggleSidebarMobileHamburger,
            toggleSidebarMobileClose,
        ) => {
            sidebar.classList.toggle('hidden');
            sidebarBackdrop.classList.toggle('hidden');
            toggleSidebarMobileHamburger.classList.toggle('hidden');
            toggleSidebarMobileClose.classList.toggle('hidden');
        };

        const $toggleSidebarMobileEl = $dom('#toggleSidebarMobile');
        const $sidebarBackdrop = $dom('#sidebarBackdrop');
        const $toggleSidebarMobileHamburger = $dom(
            '#toggleSidebarMobileHamburger',
        );
        const $toggleSidebarMobileClose = $dom('#toggleSidebarMobileClose');
        const $toggleSidebarMobileSearch = $dom('#toggleSidebarMobileSearch');

        if ($toggleSidebarMobileSearch) {
            $toggleSidebarMobileSearch.addEventListener('click', () => {
                toggleSidebarMobile(
                    $sidebar,
                    $sidebarBackdrop,
                    $toggleSidebarMobileHamburger,
                    $toggleSidebarMobileClose,
                );
            });
        }

        if ($toggleSidebarMobileEl) {
            $toggleSidebarMobileEl.addEventListener('click', () => {
                toggleSidebarMobile(
                    $sidebar,
                    $sidebarBackdrop,
                    $toggleSidebarMobileHamburger,
                    $toggleSidebarMobileClose,
                );
            });
        }

        if ($sidebarBackdrop) {
            $sidebarBackdrop.addEventListener('click', () => {
                toggleSidebarMobile(
                    $sidebar,
                    $sidebarBackdrop,
                    $toggleSidebarMobileHamburger,
                    $toggleSidebarMobileClose,
                );
            });
        }
    }
}

const $sidebar = $dom('#sidebar');
if ($sidebar instanceof HTMLElement) {
    const $sidebarItems = $dom('.sidebar-item', $sidebar);
    const $sidebarItemsButton = $domAll('.sidebar-item-button', $sidebarItems);
    $sidebarItemsButton.forEach(button => {
        button.addEventListener('click', e => {
            e.preventDefault();
            const $target = e.currentTarget;
            const $caret = $dom('[sidebar-toggle-item]', $target);
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
