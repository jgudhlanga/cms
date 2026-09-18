<script setup lang="ts">
import { computed } from 'vue';
import { type Tone, toneChip, toneGradient } from './tones';

interface Props {
    title: string;
    value: string | number;
    subtext: string;
    trend?: 'up' | 'down' | 'neutral' | 'warning';
    tone?: Tone;
    badge?: string | null;
}

const props = withDefaults(defineProps<Props>(), {
    badge: null,
});

const chipClass = computed(() => (props.tone ? toneChip[props.tone] : 'bg-muted text-muted-foreground'));

const trendClass = computed(() => {
    if (props.trend === 'up') return 'text-emerald-600 dark:text-emerald-400';
    if (props.trend === 'down') return 'text-rose-600 dark:text-rose-400';
    if (props.trend === 'warning') return 'text-amber-600 dark:text-amber-400';

    return 'text-muted-foreground';
});
</script>

<template>
    <div
        class="group relative flex flex-col overflow-hidden rounded-lg border border-border/60 bg-card p-2 shadow-xs transition-colors duration-200 hover:border-border"
    >
        <div v-if="tone" class="absolute inset-x-0 top-0 h-0.5" :class="toneGradient[tone]" aria-hidden="true" />

        <div class="flex items-start justify-between gap-1">
            <div class="flex min-w-0 items-center gap-1.5">
                <div v-if="$slots.icon" class="shrink-0 rounded p-0.5" :class="chipClass">
                    <slot name="icon"></slot>
                </div>
                <span
                    class="truncate text-[9px] leading-tight font-semibold tracking-[0.06em] text-muted-foreground uppercase"
                >
                    {{ title }}
                </span>
            </div>
            <span
                v-if="badge"
                class="shrink-0 rounded-full bg-muted px-1 py-0.5 text-[9px] leading-none font-medium tabular-nums text-muted-foreground"
            >
                {{ badge }}
            </span>
        </div>

        <div class="mt-1 text-base leading-none font-semibold tracking-tight tabular-nums text-foreground">
            {{ value }}
        </div>

        <div class="mt-0.5 flex items-center gap-1 text-[10px] leading-tight" :class="trendClass">
            <slot name="trendIcon"></slot>
            <span class="truncate">{{ subtext }}</span>
        </div>
    </div>
</template>
