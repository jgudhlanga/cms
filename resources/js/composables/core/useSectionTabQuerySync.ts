import { router, usePage } from '@inertiajs/vue3';
import { type Ref, watch } from 'vue';

/**
 * Keep a section-nav activeTab in sync with ?tab= on the current page URL (and vice versa).
 */
export function useSectionTabQuerySync(
    activeTab: Ref<string>,
    validTabValues: () => string[],
    options?: { preferTab?: () => string | null | undefined },
): void {
    const page = usePage();

    function readTabFromUrl(): string | null {
        try {
            return new URL(page.url, window.location.origin).searchParams.get('tab');
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

    watch(
        activeTab,
        (tab) => {
            const valid = validTabValues();
            if (!valid.includes(tab)) {
                return;
            }

            const currentTab = readTabFromUrl();
            if (currentTab === tab) {
                return;
            }

            let pathname = String(page.url).split('?')[0] ?? '';
            try {
                pathname = new URL(page.url, window.location.origin).pathname;
            } catch {
                // keep split path
            }

            router.get(
                pathname,
                { tab },
                {
                    preserveState: true,
                    preserveScroll: true,
                    replace: true,
                    only: [],
                },
            );
        },
        { immediate: true },
    );
}
