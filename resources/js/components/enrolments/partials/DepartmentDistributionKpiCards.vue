<script setup lang="ts">
import { Card, CardContent } from '@/components/ui/card';
import type { DepartmentDistributionKpis } from '@/lib/departmentDistributionPresentation';
import { formatPercent } from '@/lib/departmentDistributionPresentation';
import { cn } from '@/lib/utils';
import { computed } from 'vue';

interface Props {
    kpis: DepartmentDistributionKpis;
}

const props = defineProps<Props>();

const formatCount = (value: number): string => value.toLocaleString();

const cards = computed(() => [
    {
        key: 'total',
        titleKey: 'trans.ui_total_applications',
        value: formatCount(props.kpis.totalApplications),
        valueClass: 'text-foreground',
        subtextParams: {
            count: String(props.kpis.assignedDepartmentCount),
        },
        subtextKey: 'trans.ui_across_n_departments',
    },
    {
        key: 'final',
        titleKey: 'trans.ui_final_accepted',
        value: formatCount(props.kpis.finalCount),
        valueClass: 'text-emerald-600 dark:text-emerald-400',
        subtextParams: { percent: formatPercent(props.kpis.finalPercent) },
        subtextKey: 'trans.ui_percent_of_total',
    },
    {
        key: 'verified',
        titleKey: 'trans.ui_verified',
        value: formatCount(props.kpis.verifiedCount),
        valueClass: 'text-sky-600 dark:text-sky-400',
        subtextParams: {},
        subtextKey: 'trans.ui_awaiting_finalization',
    },
    {
        key: 'rejected',
        titleKey: 'trans.ui_rejected',
        value: formatCount(props.kpis.rejectedCount),
        valueClass: 'text-rose-600 dark:text-rose-400',
        subtextParams: { percent: formatPercent(props.kpis.rejectedPercent) },
        subtextKey: 'trans.ui_percent_of_total',
    },
    {
        key: 'gender',
        titleKey: 'trans.ui_gender_split',
        value: `${props.kpis.malePercent} / ${props.kpis.femalePercent}`,
        valueClass: 'text-foreground',
        subtextParams: {},
        subtextKey: 'trans.ui_male_female_percent',
    },
    {
        key: 'disabled',
        titleKey: 'trans.ui_disabled_applicants',
        value: formatCount(props.kpis.disabledCount),
        valueClass: 'text-foreground',
        subtextParams: { percent: formatPercent(props.kpis.disabledPercent) },
        subtextKey: 'trans.ui_percent_of_total',
    },
]);
</script>

<template>
    <div class="grid grid-cols-2 gap-1.5 md:grid-cols-3 xl:grid-cols-6">
        <Card
            v-for="card in cards"
            :key="card.key"
            class="border border-border/60 bg-card shadow-none"
        >
            <CardContent class="space-y-0.5 p-2">
                <div class="truncate text-[10px] font-semibold tracking-wide text-muted-foreground uppercase">
                    {{ $t(card.titleKey) }}
                </div>
                <div :class="cn('text-lg leading-none font-semibold tracking-tight', card.valueClass)">
                    {{ card.value }}
                </div>
                <div class="truncate text-[10px] leading-tight text-muted-foreground">
                    {{ $t(card.subtextKey, card.subtextParams) }}
                </div>
            </CardContent>
        </Card>
    </div>
</template>
