<script setup lang="ts">
import ExaminationStatCard from '@/components/examinations/ExaminationStatCard.vue';
import ExaminationDashboardFilters from '@/components/examinations/filters/ExaminationDashboardFilters.vue';
import type {
    ExaminationChartLabels,
    ExaminationComparison,
    ExaminationDashboardFiltersState,
    ExaminationFilterOptions,
    ExaminationStatusCounts,
    ExaminationStatusLabels,
} from '@/types/examinations';
import { router } from '@inertiajs/vue3';
import {
    Award,
    Ban,
    CircleSlash,
    Eye,
    Forward,
    PauseCircle,
    Percent,
    UserX,
} from '@lucide/vue';
import { Chart, registerables } from 'chart.js';
import { computed, nextTick, onMounted, onBeforeUnmount, ref, watch } from 'vue';

Chart.register(...registerables);

const props = withDefaults(
    defineProps<{
        filters: ExaminationDashboardFiltersState;
        filterOptions: ExaminationFilterOptions;
        statusCounts: ExaminationStatusCounts;
        statusLabels: ExaminationStatusLabels;
        chartLabels: ExaminationChartLabels;
        totalCandidates: number;
        passRate: number | null;
        onlineViewedCount: number;
        onlineViewedRate: number | null;
        comparison: ExaminationComparison | null;
        reloadUrl: string;
        extraQuery?: Record<string, string | number | undefined | null>;
        only?: string[];
    }>(),
    {
        extraQuery: () => ({}),
        only: () => [
            'filters',
            'filterOptions',
            'statusCounts',
            'statusLabels',
            'chartLabels',
            'totalCandidates',
            'passRate',
            'onlineViewedCount',
            'onlineViewedRate',
            'comparison',
        ],
    },
);

const statusChartCanvas = ref<HTMLCanvasElement | null>(null);
const passRateChartCanvas = ref<HTMLCanvasElement | null>(null);
const moduleChartCanvas = ref<HTMLCanvasElement | null>(null);

let statusChartInstance: Chart | null = null;
let passRateChartInstance: Chart | null = null;
let moduleChartInstance: Chart | null = null;

const statusCards = computed(() => [
    {
        key: 'ABSENT' as const,
        label: props.statusLabels.ABSENT,
        value: props.statusCounts.ABSENT,
        icon: UserX,
        iconClass: 'bg-slate-50 text-slate-600',
        valueClass: 'text-slate-600',
    },
    {
        key: 'AWARD' as const,
        label: props.statusLabels.AWARD,
        value: props.statusCounts.AWARD,
        icon: Award,
        iconClass: 'bg-emerald-50 text-emerald-600',
        valueClass: 'text-emerald-600',
    },
    {
        key: 'DEFERRED' as const,
        label: props.statusLabels.DEFERRED,
        value: props.statusCounts.DEFERRED,
        icon: PauseCircle,
        iconClass: 'bg-amber-50 text-amber-600',
        valueClass: 'text-amber-600',
    },
    {
        key: 'DISQUALIFIED' as const,
        label: props.statusLabels.DISQUALIFIED,
        value: props.statusCounts.DISQUALIFIED,
        icon: Ban,
        iconClass: 'bg-rose-50 text-rose-600',
        valueClass: 'text-rose-600',
    },
    {
        key: 'PROCEED' as const,
        label: props.statusLabels.PROCEED,
        value: props.statusCounts.PROCEED,
        icon: Forward,
        iconClass: 'bg-indigo-50 text-indigo-600',
        valueClass: 'text-indigo-600',
    },
    {
        key: 'REFERRED' as const,
        label: props.statusLabels.REFERRED,
        value: props.statusCounts.REFERRED,
        icon: CircleSlash,
        iconClass: 'bg-orange-50 text-orange-600',
        valueClass: 'text-orange-600',
    },
]);

const passRateDisplay = computed(() => (props.passRate === null ? '—' : `${props.passRate}%`));

const onlineViewedRateDisplay = computed(() =>
    props.onlineViewedRate === null ? '—' : `${props.onlineViewedRate}%`,
);

const statusColors = [
    'rgba(100, 116, 139, 0.8)',
    'rgba(16, 185, 129, 0.8)',
    'rgba(245, 158, 11, 0.8)',
    'rgba(244, 63, 94, 0.8)',
    'rgba(79, 70, 229, 0.8)',
    'rgba(249, 115, 22, 0.8)',
];

const destroyCharts = (): void => {
    statusChartInstance?.destroy();
    passRateChartInstance?.destroy();
    moduleChartInstance?.destroy();
    statusChartInstance = null;
    passRateChartInstance = null;
    moduleChartInstance = null;
};

const initStatusChart = (): void => {
    if (!statusChartCanvas.value || props.totalCandidates === 0) {
        statusChartInstance?.destroy();
        statusChartInstance = null;
        return;
    }

    const labels = statusCards.value.map((card) => card.label);
    const data = statusCards.value.map((card) => card.value);

    if (statusChartInstance) {
        statusChartInstance.destroy();
    }

    statusChartInstance = new Chart(statusChartCanvas.value, {
        type: 'doughnut',
        data: {
            labels,
            datasets: [
                {
                    data,
                    backgroundColor: statusColors,
                    borderWidth: 1,
                },
            ],
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { position: 'right', align: 'center' },
            },
            cutout: '55%',
        },
    });
};

const initPassRateChart = (): void => {
    if (!passRateChartCanvas.value || !props.comparison) {
        passRateChartInstance?.destroy();
        passRateChartInstance = null;
        return;
    }

    if (passRateChartInstance) {
        passRateChartInstance.destroy();
    }

    passRateChartInstance = new Chart(passRateChartCanvas.value, {
        type: 'bar',
        data: {
            labels: [props.chartLabels.session, props.chartLabels.compareSession],
            datasets: [
                {
                    label: props.chartLabels.passRate,
                    data: [
                        props.comparison.primaryPassRate ?? 0,
                        props.comparison.comparePassRate ?? 0,
                    ],
                    backgroundColor: ['rgba(79, 70, 229, 0.7)', 'rgba(16, 185, 129, 0.7)'],
                    borderRadius: 8,
                },
            ],
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: {
                y: {
                    beginAtZero: true,
                    max: 100,
                    ticks: { callback: (value) => `${value}%` },
                },
            },
        },
    });
};

const initModuleChart = (): void => {
    if (!moduleChartCanvas.value || !props.comparison || props.comparison.modules.length === 0) {
        moduleChartInstance?.destroy();
        moduleChartInstance = null;
        return;
    }

    const modules = props.comparison.modules.slice(0, 12);

    if (moduleChartInstance) {
        moduleChartInstance.destroy();
    }

    moduleChartInstance = new Chart(moduleChartCanvas.value, {
        type: 'bar',
        data: {
            labels: modules.map((module) => module.subjectCode),
            datasets: [
                {
                    label: props.chartLabels.modulePassPrimary,
                    data: modules.map((module) => module.primaryPassRate ?? 0),
                    backgroundColor: 'rgba(79, 70, 229, 0.7)',
                },
                {
                    label: props.chartLabels.modulePassCompare,
                    data: modules.map((module) => module.comparePassRate ?? 0),
                    backgroundColor: 'rgba(16, 185, 129, 0.7)',
                },
            ],
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { position: 'bottom' } },
            scales: {
                y: {
                    beginAtZero: true,
                    max: 100,
                    ticks: { callback: (value) => `${value}%` },
                },
            },
        },
    });
};

const initCharts = async (): Promise<void> => {
    await nextTick();
    initStatusChart();
    initPassRateChart();
    initModuleChart();
};

onMounted(() => {
    void initCharts();
});

onBeforeUnmount(() => {
    destroyCharts();
});

watch(
    () => [
        props.statusCounts,
        props.statusLabels,
        props.chartLabels,
        props.comparison,
        props.totalCandidates,
    ],
    () => {
        void initCharts();
    },
    { deep: true },
);

const applyFilters = (filters: ExaminationDashboardFiltersState): void => {
    const query: Record<string, string | number | undefined> = {
        ...props.extraQuery,
        session: filters.session ?? undefined,
        discipline: filters.discipline ?? undefined,
        subject_code: filters.subject_code ?? undefined,
        compare_session: filters.compare_session ?? undefined,
    };

    Object.keys(query).forEach((key) => {
        if (query[key] === undefined || query[key] === null || query[key] === '') {
            delete query[key];
        }
    });

    router.get(props.reloadUrl, query, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
        only: props.only,
    });
};

const formatRate = (value: number | null): string => (value === null ? '—' : `${value}%`);

const trendLabel = (trend: 'improved' | 'declined' | 'unchanged'): string => {
    if (trend === 'improved') {
        return props.chartLabels.moduleImproved;
    }
    if (trend === 'declined') {
        return props.chartLabels.moduleDeclined;
    }

    return props.chartLabels.moduleUnchanged;
};

const trendClass = (trend: 'improved' | 'declined' | 'unchanged'): string => {
    if (trend === 'improved') {
        return 'text-emerald-600';
    }
    if (trend === 'declined') {
        return 'text-rose-600';
    }

    return 'text-muted-foreground';
};
</script>

<template>
    <div class="flex flex-col gap-4">
        <ExaminationDashboardFilters
            :filters="filters"
            :filter-options="filterOptions"
            @change="applyFilters"
        />

        <div class="grid grid-cols-2 gap-2 sm:grid-cols-4">
            <ExaminationStatCard
                :label="$t('examinations.total_candidates')"
                :value="totalCandidates"
                :icon="Percent"
                icon-class="bg-sky-50 text-sky-600"
                value-class="text-sky-600"
            />
            <ExaminationStatCard
                :label="$t('examinations.pass_rate')"
                :value="passRateDisplay"
                :icon="Award"
                icon-class="bg-emerald-50 text-emerald-600"
                value-class="text-emerald-600"
            />
            <ExaminationStatCard
                :label="$t('examinations.online_viewed')"
                :value="onlineViewedCount"
                :icon="Eye"
                icon-class="bg-violet-50 text-violet-600"
                value-class="text-violet-600"
            />
            <ExaminationStatCard
                :label="$t('examinations.online_viewed_rate')"
                :value="onlineViewedRateDisplay"
                :icon="Eye"
                icon-class="bg-fuchsia-50 text-fuchsia-600"
                value-class="text-fuchsia-600"
            />
        </div>
        <p class="text-xs text-muted-foreground">
            {{ $t('examinations.pass_rate_hint') }} · {{ $t('examinations.online_viewed_hint') }}
        </p>

        <div class="grid grid-cols-2 gap-2 sm:grid-cols-3 xl:grid-cols-6">
            <ExaminationStatCard
                v-for="card in statusCards"
                :key="card.key"
                :label="card.label"
                :value="card.value"
                :icon="card.icon"
                :icon-class="card.iconClass"
                :value-class="card.valueClass"
            />
        </div>

        <div
            v-if="totalCandidates === 0"
            class="rounded-lg border border-dashed border-border p-8 text-center text-sm text-muted-foreground"
        >
            {{ $t('examinations.no_dashboard_data') }}
        </div>

        <template v-else>
            <div class="rounded-xl border border-border bg-card p-4 shadow-sm">
                <h2 class="mb-4 text-sm font-semibold text-foreground">
                    {{ $t('examinations.status_distribution') }}
                </h2>
                <div class="relative h-72">
                    <canvas ref="statusChartCanvas" />
                </div>
            </div>

            <div
                v-if="!comparison"
                class="rounded-lg border border-dashed border-border p-6 text-center text-sm text-muted-foreground"
            >
                {{ $t('examinations.select_compare_session_hint') }}
            </div>

            <template v-else>
                <div class="grid grid-cols-1 gap-4 xl:grid-cols-2">
                    <div class="rounded-xl border border-border bg-card p-4 shadow-sm">
                        <h2 class="mb-4 text-sm font-semibold text-foreground">
                            {{ $t('examinations.session_pass_rate_comparison') }}
                        </h2>
                        <div class="relative h-64">
                            <canvas ref="passRateChartCanvas" />
                        </div>
                    </div>
                    <div class="rounded-xl border border-border bg-card p-4 shadow-sm">
                        <h2 class="mb-4 text-sm font-semibold text-foreground">
                            {{ $t('examinations.module_pass_improvement') }}
                        </h2>
                        <div class="relative h-64">
                            <canvas ref="moduleChartCanvas" />
                        </div>
                    </div>
                </div>

                <div class="overflow-x-auto rounded-xl border border-border bg-card shadow-sm">
                    <table class="min-w-full text-sm">
                        <thead class="border-b border-border bg-muted/40 text-left text-muted-foreground">
                            <tr>
                                <th class="px-4 py-3 font-medium">{{ $t('examinations.subject_code') }}</th>
                                <th class="px-4 py-3 font-medium">{{ $t('examinations.subject') }}</th>
                                <th class="px-4 py-3 font-medium">{{ $t('examinations.module_pass_primary') }}</th>
                                <th class="px-4 py-3 font-medium">{{ $t('examinations.module_pass_compare') }}</th>
                                <th class="px-4 py-3 font-medium">{{ $t('examinations.module_pass_delta') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr
                                v-for="module in comparison.modules"
                                :key="module.subjectCode"
                                class="border-b border-border/60 last:border-0"
                            >
                                <td class="px-4 py-3 font-medium">{{ module.subjectCode }}</td>
                                <td class="px-4 py-3">{{ module.subject ?? '—' }}</td>
                                <td class="px-4 py-3">{{ formatRate(module.primaryPassRate) }}</td>
                                <td class="px-4 py-3">{{ formatRate(module.comparePassRate) }}</td>
                                <td class="px-4 py-3" :class="trendClass(module.trend)">
                                    {{ formatRate(module.delta) }}
                                    <span class="ml-1 text-xs">({{ trendLabel(module.trend) }})</span>
                                </td>
                            </tr>
                            <tr v-if="comparison.modules.length === 0">
                                <td colspan="5" class="px-4 py-6 text-center text-muted-foreground">
                                    {{ $t('examinations.no_dashboard_data') }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </template>
        </template>
    </div>
</template>
