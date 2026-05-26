<script setup lang="ts">
import type { HostelFloorChartData } from '@/composables/hms/useHostelShow';
import { Chart, registerables, type Chart as ChartInstance } from 'chart.js';
import { BarChart3 } from '@lucide/vue';
import { trans } from 'laravel-vue-i18n';
import { nextTick, onBeforeUnmount, ref, watch } from 'vue';

Chart.register(...registerables);

interface Props {
    chartData: HostelFloorChartData;
    isLoading?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
    isLoading: false,
});

const canvasRef = ref<HTMLCanvasElement | null>(null);
let chartInstance: ChartInstance | null = null;

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
                    backgroundColor: '#F472B6',
                    borderRadius: 6,
                    borderSkipped: false,
                },
                {
                    label: trans('hms.show_chart_available'),
                    data: props.chartData.available,
                    backgroundColor: '#E0E7FF',
                    borderRadius: 6,
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
                    },
                },
            },
            scales: {
                x: {
                    stacked: true,
                    grid: { display: false },
                },
                y: {
                    stacked: true,
                    beginAtZero: true,
                    grid: { color: '#F1F5F9' },
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
    <div class="rounded-2xl border border-slate-200 bg-white p-5">
        <div class="mb-4 flex items-center gap-2 text-sm font-bold text-slate-800">
            <BarChart3 class="h-4 w-4 text-indigo-500" />
            {{ $t('hms.show_floor_occupancy_chart') }}
        </div>
        <div v-if="isLoading" class="flex h-48 items-center justify-center text-sm text-slate-400">
            {{ $t('trans.loading') }}…
        </div>
        <div v-else-if="chartData.labels.length === 0" class="flex h-48 items-center justify-center text-sm text-slate-400">
            {{ $t('hms.show_no_floor_data') }}
        </div>
        <div v-else class="relative h-52 w-full">
            <canvas ref="canvasRef" class="h-full w-full" />
        </div>
    </div>
</template>
