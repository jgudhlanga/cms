export type MergeQueryIntoUrlOptions = {
    /** Query keys that are sent as `1` when strictly `true`, otherwise omitted. */
    booleanParamKeys?: string[];
};

/**
 * Merges plain-object query params into a URL and returns `pathname + search`
 * for use with HttpService (axios baseURL + path).
 */
export function mergeQueryParamsIntoRequestPath(
    url: string,
    query: Record<string, unknown>,
    options?: MergeQueryIntoUrlOptions,
): string {
    const base = typeof window !== 'undefined' ? window.location.origin : 'https://localhost';
    const parsed = new URL(url, base);
    const booleanKeys = new Set(options?.booleanParamKeys ?? []);

    for (const [key, val] of Object.entries(query)) {
        if (val === undefined || val === null || val === '') {
            continue;
        }
        if (booleanKeys.has(key)) {
            if (val === true) {
                parsed.searchParams.set(key, '1');
            }
            continue;
        }
        parsed.searchParams.set(key, String(val));
    }

    return `${parsed.pathname}${parsed.search}`;
}
