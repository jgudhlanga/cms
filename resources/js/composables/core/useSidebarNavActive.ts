import { usePage } from '@inertiajs/vue3';

/**
 * Whether a sidebar link href matches the current Inertia page URL (path match or nested path).
 */
export function useSidebarNavActive(): {
    isActive: (url: string | undefined) => boolean;
    isAnyActive: (urls: Array<string | undefined> | undefined) => boolean;
} {
    const page = usePage();

    function isActive(url: string | undefined): boolean {
        if (!url || url === '#') {
            return false;
        }

        let path: string;
        try {
            path = new URL(url, window.location.origin).pathname;
        } catch {
            return false;
        }

        const current = String(page.url).split('?')[0] ?? '';

        if (current === path) {
            return true;
        }

        if (path !== '/' && current.startsWith(`${path}/`)) {
            return true;
        }

        return false;
    }

    function isAnyActive(urls: Array<string | undefined> | undefined): boolean {
        return urls?.some((u) => isActive(u)) ?? false;
    }

    return { isActive, isAnyActive };
}
