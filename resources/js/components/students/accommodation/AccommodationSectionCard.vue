<script setup lang="ts">
import type { Component } from 'vue';
import { computed } from 'vue';

export type AccommodationSectionIconTone = 'blue' | 'amber' | 'red' | 'green';

interface SectionBadge {
    label: string;
    variant?: 'default' | 'success' | 'warning' | 'info';
}

interface Props {
    title: string;
    icon: Component;
    iconTone: AccommodationSectionIconTone;
    badge?: SectionBadge | null;
}

const props = defineProps<Props>();

const iconCircleClass = computed(() => {
    switch (props.iconTone) {
        case 'amber':
            return 'bg-amber-400 text-white';
        case 'red':
            return 'bg-destructive text-destructive-foreground';
        case 'green':
            return 'bg-emerald-600 text-white dark:bg-emerald-700';
        default:
            return 'bg-primary text-primary-foreground';
    }
});

const badgeClass = computed(() => {
    switch (props.badge?.variant) {
        case 'success':
            return 'border-emerald-500/30 bg-emerald-500/10 text-emerald-700 dark:text-emerald-400';
        case 'warning':
            return 'border-amber-500/30 bg-amber-500/10 text-amber-700 dark:text-amber-400';
        case 'info':
            return 'border-primary/30 bg-primary/10 text-primary';
        default:
            return 'border-border bg-muted/40 text-muted-foreground';
    }
});
</script>

<template>
    <section class="overflow-hidden rounded-2xl border border-border bg-card shadow-sm">
        <div class="flex items-center justify-between gap-3 border-b border-border/80 px-4 py-3.5 sm:px-5">
            <div class="flex min-w-0 items-center gap-2.5">
                <span
                    class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full"
                    :class="iconCircleClass"
                    aria-hidden="true"
                >
                    <component :is="icon" class="h-4 w-4" />
                </span>
                <h2 class="truncate text-xs font-bold uppercase tracking-wide text-primary">
                    {{ title }}
                </h2>
            </div>
            <div class="flex shrink-0 items-center gap-2">
                <slot name="header-action" />
                <span
                    v-if="badge"
                    class="rounded-full border px-2.5 py-1 text-xs font-semibold"
                    :class="badgeClass"
                >
                    {{ badge.label }}
                </span>
            </div>
        </div>

        <div class="px-4 py-4 sm:px-5">
            <slot />
        </div>
    </section>
</template>
