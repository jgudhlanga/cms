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

function pathMatchesHref(currentPathname: string, hrefPathname: string): boolean {
    return currentPathname === hrefPathname
        || (hrefPathname !== '/' && currentPathname.startsWith(`${hrefPathname}/`));
}

function hasMoreSpecificSiblingMatch(
    hrefPathname: string,
    currentPathname: string,
    siblingUrls: Array<string | undefined>,
): boolean {
    return siblingUrls.some((sibling) => {
        if (!sibling || sibling === '#') {
            return false;
        }

        const siblingHref = parseUrl(sibling);
        if (!siblingHref) {
            return false;
        }

        if (
            siblingHref.pathname.length <= hrefPathname.length
            || !siblingHref.pathname.startsWith(`${hrefPathname}/`)
        ) {
            return false;
        }

        return pathMatchesHref(currentPathname, siblingHref.pathname);
    });
}

/**
 * Whether a sidebar link href matches the current Inertia page URL.
 * Path match (exact or nested). When the href includes query params (e.g. tab, is_academic),
 * the pathname must be exact and those values must match so sibling links stay distinct.
 * Pass siblingUrls so a shorter prefix (e.g. /students) does not stay active when a longer
 * sibling (e.g. /students/id-card-requests) also matches.
 */
export function useSidebarNavActive(): {
    isActive: (url: string | undefined, siblingUrls?: Array<string | undefined>) => boolean;
    isExactActive: (url: string | undefined) => boolean;
    isAnyActive: (urls: Array<string | undefined> | undefined) => boolean;
} {
    const page = usePage();

    function isExactActive(url: string | undefined): boolean {
        if (!url || url === '#') {
            return false;
        }

        const href = parseUrl(url);
        if (!href) {
            return false;
        }

        const current = currentPageUrl(page.url);
        if (current.pathname !== href.pathname) {
            return false;
        }

        if ([...href.searchParams.keys()].length > 0) {
            return queryParamsMatch(href.searchParams, current.searchParams);
        }

        return true;
    }

    function isActive(url: string | undefined, siblingUrls: Array<string | undefined> = []): boolean {
        if (!url || url === '#') {
            return false;
        }

        const href = parseUrl(url);
        if (!href) {
            return false;
        }

        const current = currentPageUrl(page.url);
        if (!pathMatchesHref(current.pathname, href.pathname)) {
            return false;
        }

        if (hasMoreSpecificSiblingMatch(href.pathname, current.pathname, siblingUrls)) {
            return false;
        }

        if ([...href.searchParams.keys()].length > 0) {
            // Query-param siblings (e.g. is_academic=0|1) must not both light up on nested routes.
            if (current.pathname !== href.pathname) {
                return false;
            }

            return queryParamsMatch(href.searchParams, current.searchParams);
        }

        return true;
    }

    function isAnyActive(urls: Array<string | undefined> | undefined): boolean {
        return urls?.some((u) => isActive(u)) ?? false;
    }

    return { isActive, isExactActive, isAnyActive };
}
