<script setup lang="ts">
import TextLink from '@/components/core/util/TextLink.vue';
import { cn } from '@/lib/utils';
import { computed } from 'vue';

export type StatusPillTone = 'provisional' | 'waiting' | 'rejected' | 'verified' | 'final';

interface Props {
    actionable: boolean;
    title: string;
    routeName?: string;
    tone?: StatusPillTone;
    flagged?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
    tone: 'provisional',
    flagged: false,
});

const toneClasses: Record<StatusPillTone, string> = {
    provisional:
        'border-amber-200 bg-amber-50 text-amber-800 hover:bg-amber-100 dark:border-amber-900/60 dark:bg-amber-950/50 dark:text-amber-200 dark:hover:bg-amber-900/60',
    waiting:
        'border-violet-200 bg-violet-50 text-violet-800 hover:bg-violet-100 dark:border-violet-900/60 dark:bg-violet-950/50 dark:text-violet-200 dark:hover:bg-violet-900/60',
    rejected:
        'border-rose-200 bg-rose-50 text-rose-800 dark:border-rose-900/60 dark:bg-rose-950/50 dark:text-rose-200',
    verified:
        'border-sky-200 bg-sky-50 text-sky-800 hover:bg-sky-100 dark:border-sky-900/60 dark:bg-sky-950/50 dark:text-sky-200 dark:hover:bg-sky-900/60',
    final: 'border-emerald-200 bg-emerald-50 text-emerald-800 hover:bg-emerald-100 dark:border-emerald-900/60 dark:bg-emerald-950/50 dark:text-emerald-200 dark:hover:bg-emerald-900/60',
};

const pillClass = computed(() =>
    cn(
        'inline-flex min-w-7 items-center justify-center rounded-full border px-1.5 py-px text-[10px] font-semibold tabular-nums leading-4 transition-colors',
        toneClasses[props.tone],
        props.flagged && 'ring-1 ring-rose-500/70 ring-offset-1 ring-offset-background dark:ring-rose-400/80',
        props.actionable && 'cursor-pointer',
        !props.actionable && props.tone !== 'rejected' && 'hover:bg-inherit',
    ),
);
</script>

<template>
    <TextLink v-if="actionable" :title="title" :href="routeName ?? ''" :classes="pillClass" />
    <span v-else :class="pillClass">{{ title }}</span>
</template>
