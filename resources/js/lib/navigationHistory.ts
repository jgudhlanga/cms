import { router } from '@inertiajs/vue3';

const STORAGE_KEY = 'previous-page-url';

const pathOf = (url: string): string =>
    url
        .replace(/^[a-z]+:\/\/[^/]+/i, '')
        .split(/[?#]/)[0]
        .replace(/\/+$/, '') || '/';

const hasBrowser = (): boolean => typeof window !== 'undefined';

let currentUrl = hasBrowser() ? window.location.pathname + window.location.search : '';

/**
 * Remembers the page each visit came from, so dead-end screens can send people back where they were.
 * Session storage keeps the trail across a full reload without leaking into the next browser session.
 */
export const trackPageHistory = (): void => {
    router.on('navigate', (event) => {
        const next = event.detail.page.url;

        if (next === currentUrl) {
            return;
        }

        try {
            window.sessionStorage.setItem(STORAGE_KEY, currentUrl);
        } catch {
            // Storage can be unavailable (private browsing); navigation must not break over a back link.
        }

        currentUrl = next;
    });
};

/**
 * Falls back whenever the trail is empty, points at the page we are leaving, or at a page we must not return to.
 */
export const resolvePreviousPage = (previous: string | null, current: string, fallback: string, excluding?: string): string => {
    if (!previous) {
        return fallback;
    }

    const previousPath = pathOf(previous);

    if (previousPath === pathOf(current) || (excluding !== undefined && previousPath === pathOf(excluding))) {
        return fallback;
    }

    return previous;
};

export const previousPageUrl = (fallback: string, excluding?: string): string => {
    if (!hasBrowser()) {
        return fallback;
    }

    try {
        return resolvePreviousPage(window.sessionStorage.getItem(STORAGE_KEY), currentUrl, fallback, excluding);
    } catch {
        return fallback;
    }
};
