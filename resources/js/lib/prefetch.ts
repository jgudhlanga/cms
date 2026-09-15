import { clearDropdownCache } from '@/lib/dropdownCache';
import { router } from '@inertiajs/vue3';

/**
 * Hover-prefetched sidebar pages are reused for 30 seconds, then shown while they refresh for up to
 * two minutes.
 */
export const PREFETCH_CACHE_FOR: [string, string] = ['30s', '2m'];

/**
 * Any write can change list pages and lookup values, so drop prefetched pages and cached dropdown lists.
 */
export function forgetCachedPages(): void {
    clearDropdownCache();
    router.flushAll();
}
