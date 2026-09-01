<script setup lang="ts">
import Empty from '@/components/core/util/Empty.vue';
import type { FinanceDashboard } from '@/types/dashboard';
import { Banknote, Coins, Receipt } from 'lucide-vue-next';
import { computed } from 'vue';
import { trans } from 'laravel-vue-i18n';
import DashboardCard from '../components/DashboardCard.vue';
import MetricCard from '../components/MetricCard.vue';

interface Props {
    financeDashboard: FinanceDashboard;
}

const props = defineProps<Props>();

const { summary, byDepartment } = props.financeDashboard;

const formatAmount = (value: number): string =>
    new Intl.NumberFormat(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(value);

const maxDepartmentAmount = computed(() => Math.max(...byDepartment.map((row) => row.amount), 0));

const barPercent = (amount: number): number => {
    if (maxDepartmentAmount.value <= 0) {
        return 0;
    }

    return Math.round((amount / maxDepartmentAmount.value) * 100);
};

const barClass = (percent: number): string => {
    if (percent >= 80) return 'bg-emerald-500';
    if (percent >= 50) return 'bg-orange-400';

    return 'bg-rose-500';
};

const reconciledSubtext = computed(() =>
    trans('dashboard.finance_reconciled_today', { count: String(summary.reconciledToday) }),
);

const transactionsSubtext = computed(() =>
    trans('dashboard.finance_transactions_today', { count: String(summary.todayCount) }),
);
</script>

<template>
    <div class="mt-4 flex flex-col gap-3">
        <div class="grid grid-cols-2 gap-2 sm:grid-cols-3">
            <MetricCard
                compact
                accent="bg-emerald-100 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-300"
                :title="$t('dashboard.finance_today_cash_flow')"
                :value="formatAmount(summary.todayTotal)"
                :subtext="reconciledSubtext"
                trend="neutral"
            >
                <template #icon><Banknote class="h-3.5 w-3.5" /></template>
            </MetricCard>
            <MetricCard
                compact
                accent="bg-amber-100 text-amber-700 dark:bg-amber-950 dark:text-amber-300"
                :title="$t('dashboard.finance_transaction_count')"
                :value="summary.todayCount"
                :subtext="transactionsSubtext"
                trend="neutral"
            >
                <template #icon><Coins class="h-3.5 w-3.5" /></template>
            </MetricCard>
            <MetricCard
                compact
                accent="bg-blue-100 text-blue-700 dark:bg-blue-950 dark:text-blue-300"
                :title="$t('dashboard.finance_reconciled_count')"
                :value="summary.reconciledToday"
                :subtext="reconciledSubtext"
                trend="neutral"
            >
                <template #icon><Receipt class="h-3.5 w-3.5" /></template>
            </MetricCard>
        </div>

        <div class="grid grid-cols-1 gap-3 lg:grid-cols-2">
            <DashboardCard :title="$t('dashboard.finance_cash_flow_by_department')">
                <Empty v-if="byDepartment.length === 0" :message="$t('dashboard.finance_no_cash_flow_data')" />
                <div v-else class="mt-1 flex flex-col gap-2">
                    <div v-for="row in byDepartment" :key="row.departmentId" class="flex items-center gap-2">
                        <div class="w-32 shrink-0 truncate text-xs text-foreground">{{ row.departmentName }}</div>
                        <div class="h-2 flex-1 overflow-hidden rounded-sm bg-muted">
                            <div
                                class="h-2 rounded-sm"
                                :class="barClass(barPercent(row.amount))"
                                :style="{ width: `${barPercent(row.amount)}%` }"
                            />
                        </div>
                        <div class="w-20 text-right text-xs tabular-nums text-muted-foreground">{{ formatAmount(row.amount) }}</div>
                    </div>
                </div>
            </DashboardCard>
        </div>
    </div>
</template>
