<script setup lang="ts">
export type DepartmentModeTotalsLegendItem = {
    id: string;
    label: string;
    count: number;
    colorClass: string;
};

interface Props {
    total: number;
    totalLabel: string;
    items: DepartmentModeTotalsLegendItem[];
    align?: 'start' | 'end';
}

withDefaults(defineProps<Props>(), {
    align: 'end',
});
</script>

<template>
    <p
        class="flex min-w-0 flex-wrap items-center gap-x-2 gap-y-0.5 text-[11px] leading-none text-muted-foreground"
        :class="align === 'start' ? 'justify-start' : 'justify-end'"
        :aria-label="`${total} ${totalLabel}`"
    >
        <span>
            <span class="font-semibold tabular-nums text-foreground">{{ total }}</span>
            {{ ' ' }}{{ totalLabel.toLowerCase() }}
        </span>
        <template v-for="item in items" :key="item.id">
            <span class="text-border" aria-hidden="true">·</span>
            <span class="inline-flex items-center gap-1">
                <span class="h-1.5 w-1.5 shrink-0 rounded-full" :class="item.colorClass" aria-hidden="true" />
                <span>{{ item.label }}</span>
                <span class="tabular-nums">{{ item.count }}</span>
            </span>
        </template>
    </p>
</template>
