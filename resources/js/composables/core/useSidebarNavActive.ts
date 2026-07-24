import { usePage } from '@inertiajs/vue3';

function getOrigin(): string {
    if (typeof window !== 'undefined' && window.location?.origin) {
        return window.location.origin;
    }

    return 'http://localhost';
}

function parseUrl(url: string): { pathname: string; searchParams: URLSearchParams } | null {
    try {
        const parsed = new URL(url, getOrigin());

        return { pathname: parsed.pathname, searchParams: parsed.searchParams };
    } catch {
        return null;
    }
}

function currentPageUrl(pageUrl: string): { pathname: string; searchParams: URLSearchParams } {
    const raw = String(pageUrl);
    const withOrigin = raw.startsWith('http')
        ? raw
        : `${getOrigin()}${raw.startsWith('/') ? '' : '/'}${raw}`;
    const parsed = parseUrl(withOrigin);

    return parsed ?? { pathname: raw.split('?')[0] ?? '', searchParams: new URLSearchParams() };
}

function queryParamsMatch(hrefParams: URLSearchParams, currentParams: URLSearchParams): boolean {
    for (const key of hrefParams.keys()) {
        if (hrefParams.get(key) !== currentParams.get(key)) {
            return false;
        }
    }

    return true;
}

/**
 * Whether a sidebar link href matches the current Inertia page URL.
 * Path match (exact or nested). When the href includes query params (e.g. tab, is_academic),
 * those values must also match so sibling links stay distinct.
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

        const href = parseUrl(url);
        if (!href) {
            return false;
        }

        const current = currentPageUrl(page.url);
        const pathMatches =
            current.pathname === href.pathname
            || (href.pathname !== '/' && current.pathname.startsWith(`${href.pathname}/`));

        if (!pathMatches) {
            return false;
        }

        if ([...href.searchParams.keys()].length > 0) {
            // Nested paths (e.g. /institution/config under /institution) ignore child query keys.
            if (current.pathname !== href.pathname) {
                return true;
            }

            return queryParamsMatch(href.searchParams, current.searchParams);
        }

        return true;
    }

    function isAnyActive(urls: Array<string | undefined> | undefined): boolean {
        return urls?.some((u) => isActive(u)) ?? false;
    }

    return { isActive, isAnyActive };
}
