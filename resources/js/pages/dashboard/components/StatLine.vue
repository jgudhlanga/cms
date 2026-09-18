<script setup lang="ts">
interface Props {
    label: string;
    value: string | number;
    /** Highlights the value as a pill — use for counts that need attention. */
    emphasis?: 'none' | 'critical' | 'warning' | 'success';
}

withDefaults(defineProps<Props>(), {
    emphasis: 'none',
});

const pillClass: Record<string, string> = {
    critical: 'bg-rose-100 text-rose-700 dark:bg-rose-950 dark:text-rose-300',
    warning: 'bg-amber-100 text-amber-700 dark:bg-amber-950 dark:text-amber-300',
    success: 'bg-emerald-100 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-300',
};
</script>

<template>
    <div
        class="flex items-center justify-between gap-2 rounded-md px-1.5 py-1 transition-colors hover:bg-muted/40"
    >
        <span class="truncate text-[11px] text-muted-foreground">{{ label }}</span>
        <span
            v-if="emphasis === 'none'"
            class="shrink-0 text-[11px] font-semibold tabular-nums tracking-tight text-foreground"
        >
            {{ value }}
        </span>
        <span
            v-else
            class="shrink-0 rounded-full px-1.5 py-0.5 text-[10px] font-semibold tabular-nums"
            :class="pillClass[emphasis]"
        >
            {{ value }}
        </span>
    </div>
</template>
