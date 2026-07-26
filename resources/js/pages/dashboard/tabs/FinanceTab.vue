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
    <div class="mt-4 flex flex-col gap-4">
        <div class="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-3">
            <MetricCard
                :title="$t('dashboard.finance_today_cash_flow')"
                :value="formatAmount(summary.todayTotal)"
                :subtext="reconciledSubtext"
                trend="neutral"
            >
                <template #icon><Banknote class="h-4 w-4" /></template>
            </MetricCard>
            <MetricCard
                :title="$t('dashboard.finance_transaction_count')"
                :value="summary.todayCount"
                :subtext="transactionsSubtext"
                trend="neutral"
            >
                <template #icon><Coins class="h-4 w-4" /></template>
            </MetricCard>
            <MetricCard
                :title="$t('dashboard.finance_reconciled_count')"
                :value="summary.reconciledToday"
                :subtext="reconciledSubtext"
                trend="neutral"
            >
                <template #icon><Receipt class="h-4 w-4" /></template>
            </MetricCard>
        </div>

        <div class="grid grid-cols-1 gap-4 lg:grid-cols-2">
            <DashboardCard :title="$t('dashboard.finance_cash_flow_by_department')">
                <Empty v-if="byDepartment.length === 0" :message="$t('dashboard.finance_no_cash_flow_data')" />
                <div v-else class="mt-1 flex flex-col gap-2">
                    <div v-for="row in byDepartment" :key="row.departmentId" class="flex items-center gap-2">
                        <div class="w-32 shrink-0 truncate text-xs text-gray-900">{{ row.departmentName }}</div>
                        <div class="h-2 flex-1 overflow-hidden rounded-sm bg-gray-100">
                            <div
                                class="h-2 rounded-sm"
                                :class="barClass(barPercent(row.amount))"
                                :style="{ width: `${barPercent(row.amount)}%` }"
                            />
                        </div>
                        <div class="w-20 text-right text-xs text-gray-500">{{ formatAmount(row.amount) }}</div>
                    </div>
                </div>
            </DashboardCard>
        </div>
    </div>
</template>
