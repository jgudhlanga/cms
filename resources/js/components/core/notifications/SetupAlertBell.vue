<script setup lang="ts">
import { router, usePage } from '@inertiajs/vue3';
import { trans } from 'laravel-vue-i18n';
import { computed, nextTick, onBeforeUnmount, onMounted, ref } from 'vue';

interface SetupGapItem {
    id: string;
    check: string;
    checkLabel: string;
    severity: 'critical' | 'warning' | 'info';
    title: string;
    body: string | null;
    url: string | null;
    department: string | null;
    detectedAt: string | null;
}

const POLL_INTERVAL_MS = 60_000;
const panelId = 'setup-alert-panel';

const page = usePage();
const isOpen = ref(false);
const isLoading = ref(false);
const loadFailed = ref(false);
const isRefreshing = ref(false);
const refreshingCheck = ref<string | null>(null);
const refreshFailed = ref(false);
const items = ref<SetupGapItem[]>([]);
const openCount = ref<number>(Number((page.props as { setupGaps?: { openCount?: number } | null }).setupGaps?.openCount ?? 0));
const buttonRef = ref<HTMLButtonElement | null>(null);
const panelRef = ref<HTMLDivElement | null>(null);
let pollTimer: number | undefined;

const buttonLabel = computed(() =>
    openCount.value > 0 ? trans('setup_gaps.open_count', { count: String(openCount.value) }) : trans('setup_gaps.title'),
);

const severityClass = (severity: SetupGapItem['severity']): string => {
    if (severity === 'critical') {
        return 'bg-rose-600';
    }

    return severity === 'warning' ? 'bg-amber-500' : 'bg-sky-500';
};

const severityLabel = (severity: SetupGapItem['severity']): string => trans(`setup_gaps.severity_${severity}`);

const requestJson = async <T,>(url: string): Promise<T> => {
    const response = await fetch(url, {
        credentials: 'same-origin',
        headers: {
            Accept: 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
        },
    });

    if (!response.ok) {
        throw new Error(`Setup issues request failed with status ${response.status}`);
    }

    return (await response.json()) as T;
};

const loadGaps = async (): Promise<void> => {
    isLoading.value = true;
    loadFailed.value = false;

    try {
        const data = await requestJson<{ gaps: SetupGapItem[]; openCount: number }>(route('setup-gaps.index'));
        items.value = data.gaps ?? [];
        openCount.value = Number(data.openCount ?? 0);
    } catch {
        loadFailed.value = true;
    } finally {
        isLoading.value = false;
    }
};

const xsrfHeader = (): Record<string, string> => {
    const token = document.cookie
        .split('; ')
        .find((cookie) => cookie.startsWith('XSRF-TOKEN='))
        ?.split('=')[1];

    return token ? { 'X-XSRF-TOKEN': decodeURIComponent(token) } : {};
};

/**
 * Re-runs the checks now. Without a `check` it re-runs everything this user can see; with one it
 * re-runs just that issue's check, which is what the button on each row does.
 */
const refresh = async (check?: string): Promise<void> => {
    if (isRefreshing.value) {
        return;
    }

    isRefreshing.value = true;
    refreshingCheck.value = check ?? null;
    loadFailed.value = false;
    refreshFailed.value = false;

    try {
        const response = await fetch(route('setup-gaps.refresh'), {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                Accept: 'application/json',
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                ...xsrfHeader(),
            },
            body: JSON.stringify(check ? { check } : {}),
        });

        if (!response.ok) {
            throw new Error(`Setup issues refresh failed with status ${response.status}`);
        }

        const data = (await response.json()) as { gaps: SetupGapItem[]; openCount: number };
        items.value = data.gaps ?? [];
        openCount.value = Number(data.openCount ?? 0);
    } catch {
        refreshFailed.value = true;
    } finally {
        isRefreshing.value = false;
        refreshingCheck.value = null;
    }
};

const closePanel = (returnFocus = true): void => {
    isOpen.value = false;

    if (returnFocus) {
        buttonRef.value?.focus();
    }
};

const togglePanel = async (): Promise<void> => {
    if (isOpen.value) {
        closePanel();

        return;
    }

    isOpen.value = true;
    await nextTick();
    panelRef.value?.focus();
    await loadGaps();
};

const openGap = (item: SetupGapItem): void => {
    if (!item.url) {
        return;
    }

    closePanel(false);
    router.visit(item.url);
};

const formatDetectedAt = (value: string | null): string =>
    value ? new Date(value).toLocaleDateString('en-GB', { day: '2-digit', month: 'short' }) : '';

const onKeydown = (event: KeyboardEvent): void => {
    if (event.key === 'Escape' && isOpen.value) {
        closePanel();
    }
};

const onDocumentClick = (event: MouseEvent): void => {
    if (!isOpen.value) {
        return;
    }

    const target = event.target as Node;

    if (!panelRef.value?.contains(target) && !buttonRef.value?.contains(target)) {
        closePanel(false);
    }
};

const pollOpenCount = async (): Promise<void> => {
    if (document.visibilityState !== 'visible') {
        return;
    }

    try {
        const data = await requestJson<{ openCount: number }>(route('setup-gaps.index', { count_only: 1 }));
        openCount.value = Number(data.openCount ?? 0);
    } catch {
        // Keep the last known count; the next poll retries.
    }
};

onMounted(() => {
    document.addEventListener('keydown', onKeydown);
    document.addEventListener('click', onDocumentClick);
    pollTimer = window.setInterval(pollOpenCount, POLL_INTERVAL_MS);
});

onBeforeUnmount(() => {
    document.removeEventListener('keydown', onKeydown);
    document.removeEventListener('click', onDocumentClick);
    window.clearInterval(pollTimer);
});
</script>

<template>
    <div v-if="openCount > 0 || isOpen" class="relative">
        <button
            ref="buttonRef"
            type="button"
            class="hover:bg-muted focus-visible:outline-primary relative inline-flex size-9 items-center justify-center rounded-full text-amber-600 focus-visible:outline-2 focus-visible:outline-offset-2"
            :class="{ 'setup-alert-shake': openCount > 0 && !isOpen }"
            :aria-label="buttonLabel"
            :aria-expanded="isOpen ? 'true' : 'false'"
            :aria-controls="panelId"
            @click.stop="togglePanel"
        >
            <svg
                aria-hidden="true"
                viewBox="0 0 24 24"
                class="size-5"
                fill="none"
                stroke="currentColor"
                stroke-width="2"
                stroke-linecap="round"
                stroke-linejoin="round"
            >
                <path d="M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0" />
                <path d="M12 9v4" />
                <path d="M12 17h.01" />
            </svg>
            <span
                v-if="openCount > 0"
                aria-hidden="true"
                class="absolute -top-0.5 -right-0.5 min-w-4 rounded-full bg-amber-500 px-1 text-center text-[10px] leading-4 font-semibold text-white"
            >
                {{ openCount > 99 ? '99+' : openCount }}
            </span>
        </button>
        <span class="sr-only" aria-live="polite">{{ openCount > 0 ? buttonLabel : '' }}</span>

        <div
            v-if="isOpen"
            :id="panelId"
            ref="panelRef"
            role="region"
            :aria-label="$t('setup_gaps.title')"
            tabindex="-1"
            class="border-border bg-popover text-popover-foreground absolute right-0 z-50 mt-2 w-96 max-w-[calc(100vw-2rem)] rounded-md border p-2 shadow-lg focus:outline-none"
        >
            <div class="flex items-center justify-between gap-2 px-2 py-1">
                <h2 class="text-sm font-semibold">{{ $t('setup_gaps.title') }}</h2>
                <div class="flex items-center gap-2">
                    <button
                        type="button"
                        class="text-primary focus-visible:outline-primary inline-flex items-center gap-1 rounded text-xs hover:underline focus-visible:outline-2 disabled:opacity-60"
                        :disabled="isRefreshing"
                        @click="refresh()"
                    >
                        <svg
                            aria-hidden="true"
                            viewBox="0 0 24 24"
                            class="size-3.5"
                            :class="{ 'animate-spin': isRefreshing && refreshingCheck === null }"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="2"
                            stroke-linecap="round"
                            stroke-linejoin="round"
                        >
                            <path d="M3 12a9 9 0 0 1 15-6.7L21 8" />
                            <path d="M21 3v5h-5" />
                            <path d="M21 12a9 9 0 0 1-15 6.7L3 16" />
                            <path d="M3 21v-5h5" />
                        </svg>
                        {{ isRefreshing && refreshingCheck === null ? $t('setup_gaps.refreshing') : $t('setup_gaps.refresh') }}
                    </button>
                    <span class="text-muted-foreground text-xs">{{ openCount }}</span>
                </div>
            </div>

            <p v-if="loadFailed" role="alert" class="text-destructive px-2 py-2 text-sm">{{ $t('setup_gaps.load_failed') }}</p>
            <p v-if="refreshFailed" role="alert" class="text-destructive px-2 py-2 text-sm">{{ $t('setup_gaps.refresh_failed') }}</p>
            <p v-if="isLoading && items.length === 0" role="status" class="text-muted-foreground px-2 py-3 text-sm">
                {{ $t('setup_gaps.loading') }}
            </p>
            <p v-else-if="!isLoading && items.length === 0 && !loadFailed" class="text-muted-foreground px-2 py-3 text-sm">
                {{ $t('setup_gaps.empty') }}
            </p>

            <ul v-if="items.length > 0" class="max-h-96 space-y-1 overflow-y-auto">
                <li v-for="item in items" :key="item.id" class="hover:bg-muted flex items-start gap-1 rounded-md">
                    <component
                        :is="item.url ? 'button' : 'div'"
                        :type="item.url ? 'button' : undefined"
                        class="min-w-0 flex-1 rounded-md px-2 py-2 text-left"
                        :class="item.url ? 'focus-visible:outline-primary focus-visible:outline-2' : ''"
                        @click="item.url ? openGap(item) : undefined"
                    >
                        <span class="flex items-start gap-2">
                            <span aria-hidden="true" class="mt-1.5 size-2 shrink-0 rounded-full" :class="severityClass(item.severity)" />
                            <span class="min-w-0">
                                <span class="text-foreground block text-sm font-semibold">
                                    <span class="sr-only">{{ severityLabel(item.severity) }}: </span>{{ item.title }}
                                </span>
                                <span v-if="item.body" class="text-muted-foreground mt-0.5 block text-xs">{{ item.body }}</span>
                                <span class="text-muted-foreground mt-0.5 block text-[11px]">
                                    {{ item.department ? `${item.department} · ` : '' }}{{ item.checkLabel }}
                                    <template v-if="item.detectedAt">
                                        · {{ $t('setup_gaps.detected_on', { date: formatDetectedAt(item.detectedAt) }) }}
                                    </template>
                                </span>
                            </span>
                        </span>
                    </component>
                    <button
                        type="button"
                        class="text-muted-foreground hover:text-foreground focus-visible:outline-primary mt-2 mr-1 shrink-0 rounded p-1 focus-visible:outline-2 disabled:opacity-60"
                        :aria-label="$t('setup_gaps.refresh_one')"
                        :title="$t('setup_gaps.refresh_one')"
                        :disabled="isRefreshing"
                        @click.stop="refresh(item.check)"
                    >
                        <svg
                            aria-hidden="true"
                            viewBox="0 0 24 24"
                            class="size-3.5"
                            :class="{ 'animate-spin': refreshingCheck === item.check }"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="2"
                            stroke-linecap="round"
                            stroke-linejoin="round"
                        >
                            <path d="M3 12a9 9 0 0 1 15-6.7L21 8" />
                            <path d="M21 3v5h-5" />
                            <path d="M21 12a9 9 0 0 1-15 6.7L3 16" />
                            <path d="M3 21v-5h5" />
                        </svg>
                    </button>
                </li>
            </ul>
        </div>
    </div>
</template>

<style scoped>
/* Draws the eye when something needs configuring, then settles. */
@keyframes setup-alert-shake {
    0%,
    88%,
    100% {
        transform: rotate(0deg);
    }
    90% {
        transform: rotate(-9deg);
    }
    92% {
        transform: rotate(8deg);
    }
    94% {
        transform: rotate(-6deg);
    }
    96% {
        transform: rotate(5deg);
    }
    98% {
        transform: rotate(-3deg);
    }
}

.setup-alert-shake {
    animation: setup-alert-shake 4s ease-in-out infinite;
    transform-origin: center;
}

@media (prefers-reduced-motion: reduce) {
    .setup-alert-shake {
        animation: none;
    }
}
</style>
