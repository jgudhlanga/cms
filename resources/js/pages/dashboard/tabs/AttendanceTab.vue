<script setup lang="ts">
import { CalendarCheck, CalendarX, Clock, UserMinus } from 'lucide-vue-next';
import DashboardCard from '../components/DashboardCard.vue';
import MetricCard from '../components/MetricCard.vue';
</script>

<template>
    <div class="mt-4 flex flex-col gap-4">
        <div class="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-4">
            <MetricCard title="Today's attendance" value="88.4%" subtext="6,047 present" trend="neutral">
                <template #icon><CalendarCheck class="h-4 w-4" /></template>
            </MetricCard>
            <MetricCard title="Absent today" value="793" subtext="11.6% of students" trend="down">
                <template #icon><CalendarX class="h-4 w-4" /></template>
            </MetricCard>
            <MetricCard title="Chronic absentees" value="162" subtext="Missing 10%+ days" trend="warning">
                <template #icon><UserMinus class="h-4 w-4" /></template>
            </MetricCard>
            <MetricCard title="Late arrivals (week)" value="184" subtext="Mostly 08:00 sessions" trend="neutral">
                <template #icon><Clock class="h-4 w-4" /></template>
            </MetricCard>
        </div>

        <div class="grid grid-cols-1 gap-4 lg:grid-cols-12">
            <div class="lg:col-span-7">
                <DashboardCard title="Weekly attendance trend vs target (90%)">
                    <div
                        class="flex h-[185px] w-full items-center justify-center rounded border border-dashed border-gray-200 bg-gray-50 text-sm text-gray-500"
                    >
                        [Line Chart Placeholder]
                    </div>
                </DashboardCard>
            </div>
            <div class="lg:col-span-5">
                <DashboardCard title="Attendance by department">
                    <div class="mt-1 flex flex-col gap-2">
                        <div class="flex items-center gap-2">
                            <div class="w-32 shrink-0 truncate text-xs text-gray-900">Hospitality</div>
                            <div class="h-2 flex-1 overflow-hidden rounded-sm bg-gray-100">
                                <div class="h-2 rounded-sm bg-emerald-500" style="width: 93%"></div>
                            </div>
                            <div class="w-12 text-right text-xs text-gray-500">93%</div>
                        </div>
                        <div class="flex items-center gap-2">
                            <div class="w-32 shrink-0 truncate text-xs text-gray-900">Commerce & Mgt</div>
                            <div class="h-2 flex-1 overflow-hidden rounded-sm bg-gray-100">
                                <div class="h-2 rounded-sm bg-emerald-500" style="width: 92%"></div>
                            </div>
                            <div class="w-12 text-right text-xs text-gray-500">92%</div>
                        </div>
                        <div class="flex items-center gap-2">
                            <div class="w-32 shrink-0 truncate text-xs text-gray-900">Fashion & Design</div>
                            <div class="h-2 flex-1 overflow-hidden rounded-sm bg-gray-100">
                                <div class="h-2 rounded-sm bg-emerald-500" style="width: 91%"></div>
                            </div>
                            <div class="w-12 text-right text-xs text-gray-500">91%</div>
                        </div>
                        <div class="flex items-center gap-2">
                            <div class="w-32 shrink-0 truncate text-xs text-gray-900">Applied Sciences</div>
                            <div class="h-2 flex-1 overflow-hidden rounded-sm bg-gray-100">
                                <div class="h-2 rounded-sm bg-orange-400" style="width: 90%"></div>
                            </div>
                            <div class="w-12 text-right text-xs text-gray-500">90%</div>
                        </div>
                        <div class="flex items-center gap-2">
                            <div class="w-32 shrink-0 truncate text-xs text-gray-900">Built Environment</div>
                            <div class="h-2 flex-1 overflow-hidden rounded-sm bg-gray-100">
                                <div class="h-2 rounded-sm bg-orange-400" style="width: 89%"></div>
                            </div>
                            <div class="w-12 text-right text-xs text-gray-500">89%</div>
                        </div>
                        <div class="flex items-center gap-2">
                            <div class="w-32 shrink-0 truncate text-xs text-gray-900">ICT</div>
                            <div class="h-2 flex-1 overflow-hidden rounded-sm bg-gray-100">
                                <div class="h-2 rounded-sm bg-orange-400" style="width: 87%"></div>
                            </div>
                            <div class="w-12 text-right text-xs text-gray-500">87%</div>
                        </div>
                        <div class="flex items-center gap-2">
                            <div class="w-32 shrink-0 truncate text-xs text-gray-900">Automotive</div>
                            <div class="h-2 flex-1 overflow-hidden rounded-sm bg-gray-100">
                                <div class="h-2 rounded-sm bg-rose-500" style="width: 85%"></div>
                            </div>
                            <div class="w-12 text-right text-xs text-gray-500">85%</div>
                        </div>
                        <div class="flex items-center gap-2">
                            <div class="w-32 shrink-0 truncate text-xs text-gray-900">Engineering Tech</div>
                            <div class="h-2 flex-1 overflow-hidden rounded-sm bg-gray-100">
                                <div class="h-2 rounded-sm bg-rose-500" style="width: 83%"></div>
                            </div>
                            <div class="w-12 text-right text-xs text-gray-500">83%</div>
                        </div>
                    </div>
                </DashboardCard>
            </div>
        </div>

        <div class="grid grid-cols-1 gap-4 lg:grid-cols-2">
            <DashboardCard title="Absence type breakdown">
                <div
                    class="mb-2 flex h-[160px] w-full items-center justify-center rounded border border-dashed border-gray-200 bg-gray-50 text-sm text-gray-500"
                >
                    [Donut Chart Placeholder]
                </div>
                <div class="mt-2 flex flex-wrap gap-3">
                    <div class="flex items-center gap-1.5 text-xs text-gray-500">
                        <div class="h-2.5 w-2.5 rounded-sm bg-rose-500"></div>
                        Unauthorised 38%
                    </div>
                    <div class="flex items-center gap-1.5 text-xs text-gray-500">
                        <div class="h-2.5 w-2.5 rounded-sm bg-blue-500"></div>
                        Authorised 29%
                    </div>
                    <div class="flex items-center gap-1.5 text-xs text-gray-500">
                        <div class="h-2.5 w-2.5 rounded-sm bg-emerald-500"></div>
                        Medical 22%
                    </div>
                    <div class="flex items-center gap-1.5 text-xs text-gray-500">
                        <div class="h-2.5 w-2.5 rounded-sm bg-gray-400"></div>
                        Other 11%
                    </div>
                </div>
            </DashboardCard>

            <DashboardCard title="Monthly attendance vs 90% target">
                <div
                    class="flex h-[185px] w-full items-center justify-center rounded border border-dashed border-gray-200 bg-gray-50 text-sm text-gray-500"
                >
                    [Bar Chart Placeholder]
                </div>
            </DashboardCard>
        </div>
    </div>
</template>
