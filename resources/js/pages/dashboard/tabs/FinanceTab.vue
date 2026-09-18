<script setup lang="ts">
import CardEmpty from '../components/CardEmpty.vue';
import type { FinanceDashboard } from '@/types/dashboard';
import { Banknote, Coins, Receipt } from 'lucide-vue-next';
import { computed } from 'vue';
import { trans } from 'laravel-vue-i18n';
import DashboardCard from '../components/DashboardCard.vue';
import DataRow from '../components/DataRow.vue';
import MetricCard from '../components/MetricCard.vue';
import type { Tone } from '../components/tones';

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

const barTone = (percent: number): Tone => {
    if (percent >= 80) return 'emerald';
    if (percent >= 50) return 'orange';

    return 'rose';
};

const reconciledSubtext = computed(() =>
    trans('dashboard.finance_reconciled_today', { count: String(summary.reconciledToday) }),
);

const transactionsSubtext = computed(() =>
    trans('dashboard.finance_transactions_today', { count: String(summary.todayCount) }),
);
</script>

<template>
    <div class="mt-3 flex flex-col gap-2.5">
        <div class="grid grid-cols-2 gap-2 sm:grid-cols-3">
            <MetricCard
                tone="emerald"
                :title="$t('dashboard.finance_today_cash_flow')"
                :value="formatAmount(summary.todayTotal)"
                :subtext="reconciledSubtext"
                trend="neutral"
            >
                <template #icon><Banknote class="h-3.5 w-3.5" /></template>
            </MetricCard>
            <MetricCard
                tone="amber"
                :title="$t('dashboard.finance_transaction_count')"
                :value="summary.todayCount"
                :subtext="transactionsSubtext"
                trend="neutral"
            >
                <template #icon><Coins class="h-3.5 w-3.5" /></template>
            </MetricCard>
            <MetricCard
                tone="blue"
                :title="$t('dashboard.finance_reconciled_count')"
                :value="summary.reconciledToday"
                :subtext="reconciledSubtext"
                trend="neutral"
            >
                <template #icon><Receipt class="h-3.5 w-3.5" /></template>
            </MetricCard>
        </div>

        <div class="grid grid-cols-1 gap-2.5 lg:grid-cols-2">
            <DashboardCard :title="$t('dashboard.finance_cash_flow_by_department')">
                <CardEmpty v-if="byDepartment.length === 0" :message="$t('dashboard.finance_no_cash_flow_data')" />
                <div v-else class="flex flex-col gap-2">
                    <DataRow
                        v-for="row in byDepartment"
                        :key="row.departmentId"
                        :label="row.departmentName"
                        :value="formatAmount(row.amount)"
                        :percent="barPercent(row.amount)"
                        :tone="barTone(barPercent(row.amount))"
                        label-width-class="w-32"
                        value-width-class="w-20"
                    />
                </div>
            </DashboardCard>
        </div>
    </div>
</template>
