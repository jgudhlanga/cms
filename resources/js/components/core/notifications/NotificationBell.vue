<script setup lang="ts">
import { router, usePage } from '@inertiajs/vue3';
import { trans } from 'laravel-vue-i18n';
import { computed, nextTick, onBeforeUnmount, onMounted, ref } from 'vue';

interface BellNotification {
    id: string;
    kind: string;
    title: string;
    body: string;
    url: string | null;
    readAt: string | null;
    createdAt: string | null;
}

const POLL_INTERVAL_MS = 60_000;
const panelId = 'notification-bell-panel';

const page = usePage();
const isOpen = ref(false);
const isLoading = ref(false);
const loadFailed = ref(false);
const items = ref<BellNotification[]>([]);
const unreadCount = ref<number>(
    Number((page.props as { notifications?: { unreadCount?: number } | null }).notifications?.unreadCount ?? 0),
);
const buttonRef = ref<HTMLButtonElement | null>(null);
const panelRef = ref<HTMLDivElement | null>(null);
let pollTimer: number | undefined;

const buttonLabel = computed(() =>
    unreadCount.value > 0
        ? trans('notifications.unread_count', { count: String(unreadCount.value) })
        : trans('notifications.title'),
);

const xsrfHeader = (): Record<string, string> => {
    const token = document.cookie
        .split('; ')
        .find((cookie) => cookie.startsWith('XSRF-TOKEN='))
        ?.split('=')[1];

    return token ? { 'X-XSRF-TOKEN': decodeURIComponent(token) } : {};
};

const requestJson = async <T,>(url: string, method: 'GET' | 'POST' = 'GET'): Promise<T> => {
    const response = await fetch(url, {
        method,
        credentials: 'same-origin',
        headers: {
            Accept: 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            ...(method === 'POST' ? xsrfHeader() : {}),
        },
    });

    if (!response.ok) {
        throw new Error(`Notification request failed with status ${response.status}`);
    }

    return (await response.json()) as T;
};

const loadNotifications = async (): Promise<void> => {
    isLoading.value = true;
    loadFailed.value = false;

    try {
        const data = await requestJson<{ notifications: BellNotification[]; unreadCount: number }>(route('notifications.index'));
        items.value = data.notifications ?? [];
        unreadCount.value = Number(data.unreadCount ?? 0);
    } catch {
        loadFailed.value = true;
    } finally {
        isLoading.value = false;
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
    await loadNotifications();
};

const openNotification = async (item: BellNotification): Promise<void> => {
    if (!item.readAt) {
        try {
            const data = await requestJson<{ unreadCount: number }>(route('notifications.read', { notification: item.id }), 'POST');
            unreadCount.value = Number(data.unreadCount ?? 0);
            item.readAt = new Date().toISOString();
        } catch {
            // Opening the link still helps even when marking as read fails.
        }
    }

    if (item.url) {
        closePanel(false);
        router.visit(item.url);
    }
};

const markAllRead = async (): Promise<void> => {
    try {
        await requestJson<{ unreadCount: number }>(route('notifications.read-all'), 'POST');
        unreadCount.value = 0;
        const now = new Date().toISOString();
        items.value = items.value.map((item) => ({ ...item, readAt: item.readAt ?? now }));
    } catch {
        loadFailed.value = true;
    }
};

const formatCreatedAt = (value: string | null): string =>
    value ? new Date(value).toLocaleString('en-GB', { day: '2-digit', month: 'short', hour: '2-digit', minute: '2-digit' }) : '';

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

const pollUnreadCount = async (): Promise<void> => {
    if (document.visibilityState !== 'visible') {
        return;
    }

    try {
        const data = await requestJson<{ unreadCount: number }>(route('notifications.index', { count_only: 1 }));
        unreadCount.value = Number(data.unreadCount ?? 0);
    } catch {
        // Keep the last known count; the next poll retries.
    }
};

onMounted(() => {
    document.addEventListener('keydown', onKeydown);
    document.addEventListener('click', onDocumentClick);
    pollTimer = window.setInterval(pollUnreadCount, POLL_INTERVAL_MS);
});

onBeforeUnmount(() => {
    document.removeEventListener('keydown', onKeydown);
    document.removeEventListener('click', onDocumentClick);
    window.clearInterval(pollTimer);
});
</script>

<template>
    <div class="relative">
        <button
            ref="buttonRef"
            type="button"
            class="relative inline-flex size-9 items-center justify-center rounded-full text-muted-foreground hover:bg-muted hover:text-foreground focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary"
            :aria-label="buttonLabel"
            :aria-expanded="isOpen ? 'true' : 'false'"
            :aria-controls="panelId"
            @click.stop="togglePanel"
        >
            <svg aria-hidden="true" viewBox="0 0 24 24" class="size-5" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M6 8a6 6 0 0 1 12 0c0 7 3 9 3 9H3s3-2 3-9" />
                <path d="M10.3 21a1.94 1.94 0 0 0 3.4 0" />
            </svg>
            <span
                v-if="unreadCount > 0"
                aria-hidden="true"
                class="absolute -top-0.5 -right-0.5 min-w-4 rounded-full bg-rose-600 px-1 text-center text-[10px] leading-4 font-semibold text-white"
            >
                {{ unreadCount > 99 ? '99+' : unreadCount }}
            </span>
        </button>
        <span class="sr-only" aria-live="polite">{{ unreadCount > 0 ? buttonLabel : '' }}</span>

        <div
            v-if="isOpen"
            :id="panelId"
            ref="panelRef"
            role="region"
            :aria-label="$t('notifications.title')"
            tabindex="-1"
            class="absolute right-0 z-50 mt-2 w-80 max-w-[calc(100vw-2rem)] rounded-md border border-border bg-popover p-2 text-popover-foreground shadow-lg focus:outline-none"
        >
            <div class="flex items-center justify-between gap-2 px-2 py-1">
                <h2 class="text-sm font-semibold">{{ $t('notifications.title') }}</h2>
                <button
                    v-if="unreadCount > 0"
                    type="button"
                    class="rounded text-xs text-primary hover:underline focus-visible:outline-2 focus-visible:outline-primary"
                    @click="markAllRead"
                >
                    {{ $t('notifications.mark_all_read') }}
                </button>
            </div>

            <p v-if="loadFailed" role="alert" class="px-2 py-2 text-sm text-destructive">{{ $t('notifications.load_failed') }}</p>
            <p v-if="isLoading && items.length === 0" role="status" class="px-2 py-3 text-sm text-muted-foreground">
                {{ $t('notifications.loading') }}
            </p>
            <p v-else-if="!isLoading && items.length === 0 && !loadFailed" class="px-2 py-3 text-sm text-muted-foreground">
                {{ $t('notifications.empty') }}
            </p>

            <ul v-if="items.length > 0" class="max-h-96 space-y-1 overflow-y-auto">
                <li v-for="item in items" :key="item.id">
                    <button
                        type="button"
                        class="w-full rounded-md px-2 py-2 text-left hover:bg-muted focus-visible:outline-2 focus-visible:outline-primary"
                        @click="openNotification(item)"
                    >
                        <span class="flex items-start gap-2">
                            <span
                                aria-hidden="true"
                                class="mt-1.5 size-2 shrink-0 rounded-full"
                                :class="item.readAt ? 'bg-transparent' : 'bg-primary'"
                            />
                            <span class="min-w-0">
                                <span class="block text-sm" :class="item.readAt ? 'text-foreground' : 'font-semibold text-foreground'">
                                    <span v-if="!item.readAt" class="sr-only">{{ $t('notifications.unread') }}: </span>{{ item.title }}
                                </span>
                                <span v-if="item.body" class="mt-0.5 block text-xs text-muted-foreground">{{ item.body }}</span>
                                <span v-if="item.createdAt" class="mt-0.5 block text-[11px] text-muted-foreground">
                                    {{ formatCreatedAt(item.createdAt) }}
                                </span>
                            </span>
                        </span>
                    </button>
                </li>
            </ul>
        </div>
    </div>
</template>
