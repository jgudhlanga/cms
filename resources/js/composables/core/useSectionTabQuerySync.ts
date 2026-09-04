import { usePage } from '@inertiajs/vue3';
import { type Ref, watch } from 'vue';

/**
 * Keep a section-nav activeTab in sync with ?tab= on the current page URL (and vice versa).
 *
 * URL writes use history.replaceState so we never start an Inertia visit from setup.
 * A visit here races the initial page load (especially a full document load from
 * target=_blank) and can render the destination as a blank screen.
 */
export function useSectionTabQuerySync(
    activeTab: Ref<string>,
    validTabValues: () => string[],
    options?: { preferTab?: () => string | null | undefined },
): void {
    const page = usePage();

    function origin(): string {
        if (typeof window !== 'undefined' && window.location?.origin) {
            return window.location.origin;
        }

        return 'http://localhost';
    }

    function currentUrl(): URL {
        try {
            return new URL(page.url, origin());
        } catch {
            return new URL(origin());
        }
    }

    function readTabFromUrl(): string | null {
        const fromPage = currentUrl().searchParams.get('tab');
        if (fromPage) {
            return fromPage;
        }

        if (typeof window === 'undefined') {
            return null;
        }

        try {
            return new URL(window.location.href).searchParams.get('tab');
        } catch {
            return null;
        }
    }

    function applyTabFromUrlOrPreference(): void {
        const preferred = options?.preferTab?.();
        if (preferred && validTabValues().includes(preferred)) {
            if (activeTab.value !== preferred) {
                activeTab.value = preferred;
            }
            return;
        }

        const tabParam = readTabFromUrl();
        if (tabParam && validTabValues().includes(tabParam) && activeTab.value !== tabParam) {
            activeTab.value = tabParam;
        }
    }

    function writeTabToUrl(tab: string): void {
        if (!validTabValues().includes(tab)) {
            return;
        }

        const url = currentUrl();
        if (url.searchParams.get('tab') === tab) {
            return;
        }

        url.searchParams.set('tab', tab);
        const next = `${url.pathname}${url.search}${url.hash}`;

        if (typeof window === 'undefined' || typeof window.history?.replaceState !== 'function') {
            return;
        }

        const historyState = window.history.state;
        window.history.replaceState(
            historyState && typeof historyState === 'object' ? { ...historyState, url: next } : historyState,
            '',
            next,
        );
    }

    applyTabFromUrlOrPreference();

    watch(
        () => page.url,
        () => {
            applyTabFromUrlOrPreference();
        },
    );

    if (options?.preferTab) {
        watch(
            () => options.preferTab?.(),
            () => {
                applyTabFromUrlOrPreference();
            },
        );
    }

    watch(activeTab, (tab) => {
        writeTabToUrl(tab);
    });
}
