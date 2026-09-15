const DEFAULT_TTL_MS = 5 * 60 * 1000;

type CacheEntry = {
    value: unknown;
    expiresAt: number;
};

const entries = new Map<string, CacheEntry>();
const inFlight = new Map<string, Promise<unknown>>();

/**
 * Shares lookup lists (genders, titles, departments, ...) between comboboxes for a few minutes and
 * collapses simultaneous requests for the same key into one.
 */
export function cachedLookup<T>(key: string, load: () => Promise<T>, ttlMs = DEFAULT_TTL_MS, now: () => number = Date.now): Promise<T> {
    const entry = entries.get(key);

    if (entry && entry.expiresAt > now()) {
        return Promise.resolve(entry.value as T);
    }

    const pending = inFlight.get(key);

    if (pending) {
        return pending as Promise<T>;
    }

    const request: Promise<T> = load()
        .then((value) => {
            // A clear while the request was running means the result may already be stale.
            if (inFlight.get(key) === request) {
                entries.set(key, { value, expiresAt: now() + ttlMs });
            }

            return value;
        })
        .finally(() => {
            if (inFlight.get(key) === request) {
                inFlight.delete(key);
            }
        });

    inFlight.set(key, request);

    return request;
}

/**
 * Drops cached lookups whose key starts with the prefix (all of them by default).
 */
export function clearDropdownCache(prefix = ''): void {
    for (const key of [...entries.keys()]) {
        if (key.startsWith(prefix)) {
            entries.delete(key);
        }
    }

    for (const key of [...inFlight.keys()]) {
        if (key.startsWith(prefix)) {
            inFlight.delete(key);
        }
    }
}
