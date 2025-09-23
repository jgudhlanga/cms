<script setup lang="ts">
import PageContainer from '@/components/core/page/PageContainer.vue';
import ComponentHeader from '@/pages/dashboard/partials/ComponentHeader.vue';
import { BreadcrumbItemInterface } from '@/types/ui';
import { Head } from '@inertiajs/vue3';
import { Chart, registerables } from 'chart.js';
import { onMounted, ref } from 'vue';
import StatsCard from '@/pages/dashboard/partials/StatsCard.vue';

Chart.register(...registerables);

const breadcrumbs: BreadcrumbItemInterface[] = [{ transChoiceKey: 'dashboard' }];

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

// Initialize charts when component is mounted
onMounted(() => {
    // Enrollment Trends Chart (Line Chart)
    new Chart(enrollmentChart.value!, {
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
    });
});
</script>
<template>
    <Head :title="$tChoice('trans.dashboard', 2)" />
    <PageContainer :breadcrumbs="breadcrumbs">
        <div class="flex w-full flex-col sm:px-6 lg:px-8">
            <!-- REGISTRATION -->
            <ComponentHeader header-title="Registration Stats" description="Overview of portal registration trends and statistics" />
            <!-- Stats Cards -->
            <div class="my-6 grid grid-cols-1 gap-5 px-4 sm:grid-cols-2 sm:px-0 lg:grid-cols-4">
                <StatsCard title="Total Registrations" :value="formatNumber(totalStudents)" icon="users" icon-bg-color="indigo"/>
                <StatsCard title="This Month" :value="formatNumber(thisMonthEnrollments)" icon="checkDone" icon-bg-color="green"/>
                <StatsCard title="Growth Rate" :value="growthRate" icon="chartGrowth" icon-bg-color="yellow"/>

                <div class="overflow-hidden rounded-lg bg-white shadow">
                    <div class="px-4 py-5 sm:p-6">
                        <div class="flex items-center">
                            <div class="flex-shrink-0 rounded-md bg-red-100 p-3">
                                <svg class="h-6 w-6 text-red-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"
                                    />
                                </svg>
                            </div>
                            <div class="ml-5 w-0 flex-1">
                                <dl>
                                    <dt class="truncate text-sm font-medium text-gray-500">Avg. Processing Time</dt>
                                    <dd class="text-lg font-medium text-gray-900">{{ avgProcessingTime }} days</dd>
                                </dl>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Charts Section -->
            <div class="grid grid-cols-1 gap-6 px-4 sm:px-0 lg:grid-cols-2">
                <!-- Enrollment Trends Chart -->
                <div class="rounded-lg bg-white p-6 shadow">
                    <h3 class="mb-4 text-lg font-medium text-gray-900">Enrollment Trends</h3>
                    <div class="h-80">
                        <canvas id="enrollmentChart" ref="enrollmentChart"></canvas>
                    </div>
                </div>

                <!-- Program Distribution Chart -->
                <div class="rounded-lg bg-white p-6 shadow">
                    <h3 class="mb-4 text-lg font-medium text-gray-900">Program Distribution</h3>
                    <div class="h-80">
                        <canvas id="programChart" ref="programChart"></canvas>
                    </div>
                </div>

                <!-- Monthly Comparison Chart -->
                <div class="rounded-lg bg-white p-6 shadow lg:col-span-2">
                    <h3 class="mb-4 text-lg font-medium text-gray-900">Monthly Comparison (Current Year vs Last Year)</h3>
                    <div class="h-80">
                        <canvas id="comparisonChart" ref="comparisonChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </PageContainer>
</template>
