<script setup lang="ts">
import type { MaintenanceExportBreakdown } from '@/types/maintenance-exports';
import { computed } from 'vue';

interface Props {
    title: string;
    rows: MaintenanceExportBreakdown[];
    limit?: number;
}

const props = withDefaults(defineProps<Props>(), {
    limit: 5,
});

const visibleRows = computed(() => props.rows.filter((row) => row.count > 0).slice(0, props.limit));
</script>

<template>
    <div class="rounded-xl border border-border bg-card p-4">
        <p class="text-[0.63rem] font-semibold uppercase tracking-[0.1em] text-muted-foreground">{{ title }}</p>

        <ul v-if="visibleRows.length" class="mt-2 space-y-1.5">
            <li v-for="row in visibleRows" :key="row.name" class="flex items-center justify-between gap-3 text-sm">
                <span class="min-w-0 truncate text-foreground">{{ row.name }}</span>
                <span class="shrink-0 font-semibold tabular-nums text-foreground">{{ row.count }}</span>
            </li>
        </ul>

        <p v-else class="mt-2 text-sm text-muted-foreground">—</p>
    </div>
</template>
