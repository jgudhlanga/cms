<script setup lang="ts">
import { PageProps } from '@/types';
import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

type EnvironmentStyle = {
    badge: string;
    dot: string;
    ping: string;
};

const environmentStyles: Record<string, EnvironmentStyle> = {
    local: {
        badge: 'bg-red-100 text-red-800',
        dot: 'bg-red-600',
        ping: 'bg-red-400',
    },
    staging: {
        badge: 'bg-amber-100 text-amber-800',
        dot: 'bg-amber-600',
        ping: 'bg-amber-400',
    },
    production: {
        badge: 'bg-emerald-100 text-emerald-800',
        dot: 'bg-emerald-600',
        ping: 'bg-emerald-400',
    },
};

const fallbackStyle: EnvironmentStyle = {
    badge: 'bg-gray-100 text-gray-800',
    dot: 'bg-gray-600',
    ping: 'bg-gray-400',
};

const page = usePage<PageProps>();

const appEnv = computed(() => page.props.appEnv ?? 'production');

const environmentStyle = computed(() => environmentStyles[appEnv.value] ?? fallbackStyle);

const environmentLabel = computed(() => {
    return appEnv.value.charAt(0).toUpperCase() + appEnv.value.slice(1);
});
</script>

<template>
    <span
        class="hidden items-center gap-1.5 rounded-full px-2 py-0.5 text-[11px] sm:flex"
        :class="environmentStyle.badge"
    >
        <span class="relative flex h-2 w-2">
            <span
                class="absolute inline-flex h-full w-full animate-ping rounded-full opacity-75"
                :class="environmentStyle.ping"
            />
            <span
                class="relative inline-flex h-2 w-2 rounded-full"
                :class="environmentStyle.dot"
            />
        </span>
        {{ environmentLabel }}
    </span>
</template>
