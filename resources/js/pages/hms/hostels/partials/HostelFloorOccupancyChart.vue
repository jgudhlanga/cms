<script setup lang="ts">
import type { HostelFloorChartData } from '@/composables/hms/useHostelShow';
import { Chart, registerables, type Chart as ChartInstance, type ScriptableContext } from 'chart.js';
import { BarChart3 } from '@lucide/vue';
import { trans } from 'laravel-vue-i18n';
import { nextTick, onBeforeUnmount, ref, watch } from 'vue';

Chart.register(...registerables);

const BAR_CORNER_RADIUS = 6;

const datasetNumber = (data: unknown, index: number): number => {
    if (!Array.isArray(data)) {
        return 0;
    }

    const value = data[index];

    return typeof value === 'number' ? value : 0;
};

const stackedBarBorderRadius = (datasetIndex: number) => {
    return (ctx: ScriptableContext<'bar'>): number | { topLeft: number; topRight: number; bottomLeft: number; bottomRight: number } => {
        const occupied = datasetNumber(ctx.chart.data.datasets[0]?.data, ctx.dataIndex);
        const available = datasetNumber(ctx.chart.data.datasets[1]?.data, ctx.dataIndex);
        const isTopSegment = datasetIndex === 1;
        const isOnlyVisibleSegment = isTopSegment ? occupied <= 0 : available <= 0;

        if (isOnlyVisibleSegment) {
            return BAR_CORNER_RADIUS;
        }

        if (isTopSegment) {
            return { topLeft: BAR_CORNER_RADIUS, topRight: BAR_CORNER_RADIUS, bottomLeft: 0, bottomRight: 0 };
        }

        return { topLeft: 0, topRight: 0, bottomLeft: BAR_CORNER_RADIUS, bottomRight: BAR_CORNER_RADIUS };
    };
};

interface Props {
    chartData: HostelFloorChartData;
    isLoading?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
    isLoading: false,
});

const canvasRef = ref<HTMLCanvasElement | null>(null);
let chartInstance: ChartInstance | null = null;

const getThemeColor = (token: string, fallback: string): string => {
    if (typeof window === 'undefined') {
        return fallback;
    }
    const value = getComputedStyle(document.documentElement).getPropertyValue(token).trim();
    return value ? `hsl(${value})` : fallback;
};

const destroyChart = (): void => {
    chartInstance?.destroy();
    chartInstance = null;
};

const renderChart = (): void => {
    if (!canvasRef.value || props.chartData.labels.length === 0) {
        destroyChart();
        return;
    }

    destroyChart();

    chartInstance = new Chart(canvasRef.value, {
        type: 'bar',
        data: {
            labels: props.chartData.labels,
            datasets: [
                {
                    label: trans('hms.show_chart_occupied'),
                    data: props.chartData.occupied,
                    backgroundColor: '#EC4899',
                    borderRadius: stackedBarBorderRadius(0),
                    borderSkipped: false,
                },
                {
                    label: trans('hms.show_chart_available'),
                    data: props.chartData.available,
                    backgroundColor: '#60A5FA',
                    borderRadius: stackedBarBorderRadius(1),
                    borderSkipped: false,
                },
            ],
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: {
                        boxWidth: 12,
                        font: { size: 12 },
                        color: getThemeColor('--muted-foreground', '#64748B'),
                    },
                },
            },
            scales: {
                x: {
                    stacked: true,
                    ticks: { color: getThemeColor('--muted-foreground', '#64748B') },
                    grid: { display: false },
                },
                y: {
                    stacked: true,
                    beginAtZero: true,
                    ticks: { color: getThemeColor('--muted-foreground', '#64748B') },
                    grid: { color: getThemeColor('--border', '#F1F5F9') },
                },
            },
        },
    });
};

const scheduleRender = async (): Promise<void> => {
    if (props.isLoading || props.chartData.labels.length === 0) {
        destroyChart();
        return;
    }

    await nextTick();
    renderChart();
};

watch(
    () => [props.isLoading, props.chartData.labels, props.chartData.occupied, props.chartData.available] as const,
    () => {
        void scheduleRender();
    },
    { immediate: true },
);

onBeforeUnmount(() => {
    destroyChart();
});
</script>

<template>
    <div class="rounded-2xl border border-border bg-card p-5">
        <div class="text-foreground mb-4 flex items-center gap-2 text-sm font-bold">
            <BarChart3 class="h-4 w-4 text-indigo-500" />
            {{ $t('hms.show_floor_occupancy_chart') }}
        </div>
        <div v-if="isLoading" class="text-muted-foreground flex h-48 items-center justify-center text-sm">
            {{ $t('trans.loading') }}…
        </div>
        <div v-else-if="chartData.labels.length === 0" class="text-muted-foreground flex h-48 items-center justify-center text-sm">
            {{ $t('hms.show_no_floor_data') }}
        </div>
        <div v-else class="relative h-52 w-full">
            <canvas ref="canvasRef" class="h-full w-full" />
        </div>
    </div>
</template>
