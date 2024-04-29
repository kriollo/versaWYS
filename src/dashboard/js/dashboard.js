'use strict';
import { $ } from '@/dashboard/js/functions';

const $themeToggleDarkIcon = $('#theme-toggle-dark-icon');
const $themeToggleLightIcon = $('#theme-toggle-light-icon');

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

const $themeToggleBtn = $('#theme-toggle');
if ($themeToggleBtn) {
    let event = new Event('dark-mode');
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

const $sidebar = $('#sidebar');
if ($sidebar) {
    const toggleSidebarMobile = (
        sidebar,
        sidebarBackdrop,
        toggleSidebarMobileHamburger,
        toggleSidebarMobileClose
    ) => {
        sidebar.classList.toggle('hidden');
        sidebarBackdrop.classList.toggle('hidden');
        toggleSidebarMobileHamburger.classList.toggle('hidden');
        toggleSidebarMobileClose.classList.toggle('hidden');
    };

    const $toggleSidebarMobileEl = $('#toggleSidebarMobile');
    const $sidebarBackdrop = $('#sidebarBackdrop');
    const $toggleSidebarMobileHamburger = $('#toggleSidebarMobileHamburger');
    const $toggleSidebarMobileClose = $('#toggleSidebarMobileClose');
    const $toggleSidebarMobileSearch = $('#toggleSidebarMobileSearch');

    if ($toggleSidebarMobileSearch) {
        $toggleSidebarMobileSearch.addEventListener('click', () => {
            toggleSidebarMobile(
                $sidebar,
                $sidebarBackdrop,
                $toggleSidebarMobileHamburger,
                $toggleSidebarMobileClose
            );
        });
    }

    if ($toggleSidebarMobileEl) {
        $toggleSidebarMobileEl.addEventListener('click', () => {
            toggleSidebarMobile(
                $sidebar,
                $sidebarBackdrop,
                $toggleSidebarMobileHamburger,
                $toggleSidebarMobileClose
            );
        });
    }

    if ($sidebarBackdrop) {
        $sidebarBackdrop.addEventListener('click', () => {
            toggleSidebarMobile(
                $sidebar,
                $sidebarBackdrop,
                $toggleSidebarMobileHamburger,
                $toggleSidebarMobileClose
            );
        });
    }
}
