<script setup lang="ts">
import DateRangePicker from '@/components/core/form/date/DateRangePicker.vue';
import DataLoadingSpinner from '@/components/core/loader/DataLoadingSpinner.vue';
import PageContainer from '@/components/core/page/PageContainer.vue';
import { useMetrics } from '@/composables/metrics/useMetrics';
import ComponentHeader from '@/pages/dashboard/partials/ComponentHeader.vue';
import StatsCard from '@/pages/dashboard/partials/StatsCard.vue';
import { AuthObject } from '@/types/data-pagination';
import { BreadcrumbItemInterface } from '@/types/ui';
import { Head } from '@inertiajs/vue3';
import { Chart, registerables } from 'chart.js';
import { onMounted, ref } from 'vue';

const breadcrumbs: BreadcrumbItemInterface[] = [{ transChoiceKey: 'dashboard' }];
Chart.register(...registerables);
interface Props {
    auth: AuthObject;
    errors: object;
}

interface DashboardMetrics {
    users: string | number;
    totalApplications: string | number;
    maleApplications: string | number;
    femaleApplications: string | number;
}

const props = defineProps<Props>();
const rangeData = ref();
const startDate = new Date();
rangeData.value = [startDate, null];
const today = new Date();
const metricsData = ref<DashboardMetrics | null>(null);
const { isLoading, loadAdminDashboardMetrics } = useMetrics();

const totalStudents = ref(1245);
const thisMonthEnrollments = ref(87);
const growthRate = ref(12.5);
const avgProcessingTime = ref(3.2);

const enrollmentData = ref({
    labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
    datasets: [
        {
            label: 'Enrollments',
            data: [65, 59, 80, 81, 56, 55, 40, 72, 88, 94, 102, 120],
            backgroundColor: 'rgba(79, 70, 229, 0.2)',
            borderColor: 'rgba(79, 70, 229, 1)',
            borderWidth: 2,
            tension: 0.4,
            fill: true,
        },
    ],
});

const programData = ref({
    labels: ['Computer Science', 'Business Administration', 'Engineering', 'Health Sciences', 'Arts & Humanities', 'Other'],
    datasets: [
        {
            data: [30, 25, 15, 12, 10, 8],
            backgroundColor: [
                'rgba(79, 70, 229, 0.7)',
                'rgba(16, 185, 129, 0.7)',
                'rgba(245, 158, 11, 0.7)',
                'rgba(239, 68, 68, 0.7)',
                'rgba(139, 92, 246, 0.7)',
                'rgba(107, 114, 128, 0.7)',
            ],
            borderColor: [
                'rgba(79, 70, 229, 1)',
                'rgba(16, 185, 129, 1)',
                'rgba(245, 158, 11, 1)',
                'rgba(239, 68, 68, 1)',
                'rgba(139, 92, 246, 1)',
                'rgba(107, 114, 128, 1)',
            ],
            borderWidth: 1,
        },
    ],
});

const comparisonData = ref({
    labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
    datasets: [
        {
            label: 'Current Year',
            data: [65, 59, 80, 81, 56, 55, 40, 72, 88, 94, 102, 120],
            borderColor: 'rgba(79, 70, 229, 1)',
            backgroundColor: 'rgba(79, 70, 229, 0.1)',
            borderWidth: 2,
            tension: 0.4,
        },
        {
            label: 'Last Year',
            data: [45, 48, 60, 65, 50, 45, 35, 58, 70, 75, 85, 95],
            borderColor: 'rgba(107, 114, 128, 1)',
            backgroundColor: 'rgba(107, 114, 128, 0.1)',
            borderWidth: 2,
            borderDash: [5, 5],
            tension: 0.4,
        },
    ],
});
// Chart references
const enrollmentChart = ref(null);
const programChart = ref(null);
const comparisonChart = ref(null);

// Format numbers with commas
const formatNumber = (num: number) => {
    return num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ',');
};

const handleDateChange = async (value: any) => {
    if (value == null) return;
    metricsData.value = await loadAdminDashboardMetrics(value);
};

// Initialize charts when component is mounted
onMounted(async () => {
    metricsData.value = await loadAdminDashboardMetrics(rangeData.value);
    // Enrollment Trends Chart (Line Chart)
    /*new Chart(enrollmentChart.value!, {
        type: 'line',
        data: enrollmentData.value,
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false,
                },
                tooltip: {
                    mode: 'index',
                    intersect: false,
                },
            },
            scales: {
                y: {
                    beginAtZero: true,
                    grid: {
                        drawBorder: false,
                    },
                },
                x: {
                    grid: {
                        display: false,
                    },
                },
            },
        },
    });

    // Program Distribution Chart (Doughnut Chart)
    new Chart(programChart.value!, {
        type: 'doughnut',
        data: programData.value,
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'right',
                },
            },
            cutout: '60%',
        },
    });

    // Monthly Comparison Chart (Line Chart)
    new Chart(comparisonChart.value!, {
        type: 'line',
        data: comparisonData.value,
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'top',
                },
            },
            scales: {
                y: {
                    beginAtZero: true,
                    grid: {
                        drawBorder: false,
                    },
                },
                x: {
                    grid: {
                        display: false,
                    },
                },
            },
        },
    });*/
});
</script>
<template>
    <Head :title="$tChoice('trans.dashboard', 2)" />
    <PageContainer :breadcrumbs="breadcrumbs">
        <div class="flex w-full flex-col">
            <div class="mt-3 flex items-center justify-between">
                <ComponentHeader header-title="Applications metrics" description="Overview of portal application metrics and stats" />
                <div>
                    <DateRangePicker
                        class="rounded-full"
                        v-model="rangeData"
                        :label-uppercase="false"
                        :vertical-layout="false"
                        @update:model-value="handleDateChange"
                        :max-date="today"
                    />
                </div>
            </div>
            <template v-if="isLoading">
                <DataLoadingSpinner message="loading metrics..." />
            </template>
            <template v-else>
                <div class="mt-3 grid grid-cols-1 gap-5 px-4 pt-4 sm:px-0 md:grid-cols-4">
                    <StatsCard
                        title="User accounts created"
                        :value="formatNumber(Number(metricsData?.users))"
                        icon="checkDone"
                        icon-bg-color="green"
                    />
                    <StatsCard
                        title="Total Applications"
                        :value="formatNumber(Number(metricsData?.totalApplications))"
                        icon="users"
                        icon-bg-color="indigo"
                    />
                    <StatsCard title="Males" :value="formatNumber(Number(metricsData?.maleApplications))" icon="male" icon-bg-color="persian" />
                    <StatsCard title="Females" :value="formatNumber(Number(metricsData?.femaleApplications))" icon="female" icon-bg-color="pink" />
                </div>
            </template>

            <!--            <div class="my-3 pt-4 flex flex-col">
                <ComponentHeader header-title="Payments" description="Overview of payments" />
                <div class="my-3 grid grid-cols-1 gap-5 px-4 sm:px-0 md:grid-cols-4">
                    <StatsCard title="Successful Payments" :value="growthRate" icon="paymentSuccess" icon-bg-color="green" />
                    <StatsCard title="Unsuccessful Payments" :value="growthRate" icon="paymentFailed" icon-bg-color="red" />
                    <StatsCard title="Pending payments" :value="growthRate" icon="time" icon-bg-color="yellow" />
                </div>
            </div>
            <div class="grid grid-cols-1 gap-6 px-4 sm:px-0 lg:grid-cols-2">
                <div class="rounded-lg bg-white p-6 shadow">
                    <h3 class="mb-4 text-lg font-medium text-gray-900">Enrollment Trends</h3>
                    <div class="h-80">
                        <canvas id="enrollmentChart" ref="enrollmentChart"></canvas>
                    </div>
                </div>

                <div class="rounded-lg bg-white p-6 shadow">
                    <h3 class="mb-4 text-lg font-medium text-gray-900">Program Distribution</h3>
                    <div class="h-80">
                        <canvas id="programChart" ref="programChart"></canvas>
                    </div>
                </div>

                <div class="rounded-lg bg-white p-6 shadow lg:col-span-2">
                    <h3 class="mb-4 text-lg font-medium text-gray-900">Monthly Comparison (Current Year vs Last Year)</h3>
                    <div class="h-80">
                        <canvas id="comparisonChart" ref="comparisonChart"></canvas>
                    </div>
                </div>
            </div>-->
        </div>
    </PageContainer>
</template>
