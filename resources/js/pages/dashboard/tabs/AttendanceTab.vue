<script setup lang="ts">
import { CalendarCheck, CalendarX, Clock, UserMinus } from 'lucide-vue-next';
import DashboardCard from '../components/DashboardCard.vue';
import MetricCard from '../components/MetricCard.vue';
</script>

<template>
    <div class="mt-4 flex flex-col gap-3">
        <div class="grid grid-cols-2 gap-2 sm:grid-cols-4">
            <MetricCard compact accent="bg-emerald-100 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-300" title="Today's attendance" value="88.4%" subtext="6,047 present" trend="neutral">
                <template #icon><CalendarCheck class="h-3.5 w-3.5" /></template>
            </MetricCard>
            <MetricCard compact accent="bg-rose-100 text-rose-700 dark:bg-rose-950 dark:text-rose-300" title="Absent today" value="793" subtext="11.6% of students" trend="down">
                <template #icon><CalendarX class="h-3.5 w-3.5" /></template>
            </MetricCard>
            <MetricCard compact accent="bg-amber-100 text-amber-700 dark:bg-amber-950 dark:text-amber-300" title="Chronic absentees" value="162" subtext="Missing 10%+ days" trend="warning">
                <template #icon><UserMinus class="h-3.5 w-3.5" /></template>
            </MetricCard>
            <MetricCard compact accent="bg-slate-100 text-slate-700 dark:bg-slate-950 dark:text-slate-300" title="Late arrivals (week)" value="184" subtext="Mostly 08:00 sessions" trend="neutral">
                <template #icon><Clock class="h-3.5 w-3.5" /></template>
            </MetricCard>
        </div>

        <div class="grid grid-cols-1 gap-3 lg:grid-cols-12">
            <div class="lg:col-span-7">
                <DashboardCard title="Weekly attendance trend vs target (90%)">
                    <div
                        class="flex h-[185px] w-full items-center justify-center rounded border border-dashed border-border bg-muted/40 text-sm text-muted-foreground"
                    >
                        [Line Chart Placeholder]
                    </div>
                </DashboardCard>
            </div>
            <div class="lg:col-span-5">
                <DashboardCard title="Attendance by department">
                    <div class="mt-1 flex flex-col gap-2">
                        <div class="flex items-center gap-2">
                            <div class="w-32 shrink-0 truncate text-xs text-foreground">Hospitality</div>
                            <div class="h-2 flex-1 overflow-hidden rounded-sm bg-muted">
                                <div class="h-2 rounded-sm bg-emerald-500" style="width: 93%"></div>
                            </div>
                            <div class="w-12 text-right text-xs text-muted-foreground">93%</div>
                        </div>
                        <div class="flex items-center gap-2">
                            <div class="w-32 shrink-0 truncate text-xs text-foreground">Commerce & Mgt</div>
                            <div class="h-2 flex-1 overflow-hidden rounded-sm bg-muted">
                                <div class="h-2 rounded-sm bg-emerald-500" style="width: 92%"></div>
                            </div>
                            <div class="w-12 text-right text-xs text-muted-foreground">92%</div>
                        </div>
                        <div class="flex items-center gap-2">
                            <div class="w-32 shrink-0 truncate text-xs text-foreground">Fashion & Design</div>
                            <div class="h-2 flex-1 overflow-hidden rounded-sm bg-muted">
                                <div class="h-2 rounded-sm bg-emerald-500" style="width: 91%"></div>
                            </div>
                            <div class="w-12 text-right text-xs text-muted-foreground">91%</div>
                        </div>
                        <div class="flex items-center gap-2">
                            <div class="w-32 shrink-0 truncate text-xs text-foreground">Applied Sciences</div>
                            <div class="h-2 flex-1 overflow-hidden rounded-sm bg-muted">
                                <div class="h-2 rounded-sm bg-orange-400" style="width: 90%"></div>
                            </div>
                            <div class="w-12 text-right text-xs text-muted-foreground">90%</div>
                        </div>
                        <div class="flex items-center gap-2">
                            <div class="w-32 shrink-0 truncate text-xs text-foreground">Built Environment</div>
                            <div class="h-2 flex-1 overflow-hidden rounded-sm bg-muted">
                                <div class="h-2 rounded-sm bg-orange-400" style="width: 89%"></div>
                            </div>
                            <div class="w-12 text-right text-xs text-muted-foreground">89%</div>
                        </div>
                        <div class="flex items-center gap-2">
                            <div class="w-32 shrink-0 truncate text-xs text-foreground">ICT</div>
                            <div class="h-2 flex-1 overflow-hidden rounded-sm bg-muted">
                                <div class="h-2 rounded-sm bg-orange-400" style="width: 87%"></div>
                            </div>
                            <div class="w-12 text-right text-xs text-muted-foreground">87%</div>
                        </div>
                        <div class="flex items-center gap-2">
                            <div class="w-32 shrink-0 truncate text-xs text-foreground">Automotive</div>
                            <div class="h-2 flex-1 overflow-hidden rounded-sm bg-muted">
                                <div class="h-2 rounded-sm bg-rose-500" style="width: 85%"></div>
                            </div>
                            <div class="w-12 text-right text-xs text-muted-foreground">85%</div>
                        </div>
                        <div class="flex items-center gap-2">
                            <div class="w-32 shrink-0 truncate text-xs text-foreground">Engineering Tech</div>
                            <div class="h-2 flex-1 overflow-hidden rounded-sm bg-muted">
                                <div class="h-2 rounded-sm bg-rose-500" style="width: 83%"></div>
                            </div>
                            <div class="w-12 text-right text-xs text-muted-foreground">83%</div>
                        </div>
                    </div>
                </DashboardCard>
            </div>
        </div>

        <div class="grid grid-cols-1 gap-3 lg:grid-cols-2">
            <DashboardCard title="Absence type breakdown">
                <div
                    class="mb-2 flex h-[160px] w-full items-center justify-center rounded border border-dashed border-border bg-muted/40 text-sm text-muted-foreground"
                >
                    [Donut Chart Placeholder]
                </div>
                <div class="mt-2 flex flex-wrap gap-3">
                    <div class="flex items-center gap-1.5 text-xs text-muted-foreground">
                        <div class="h-2.5 w-2.5 rounded-sm bg-rose-500"></div>
                        Unauthorised 38%
                    </div>
                    <div class="flex items-center gap-1.5 text-xs text-muted-foreground">
                        <div class="h-2.5 w-2.5 rounded-sm bg-blue-500"></div>
                        Authorised 29%
                    </div>
                    <div class="flex items-center gap-1.5 text-xs text-muted-foreground">
                        <div class="h-2.5 w-2.5 rounded-sm bg-emerald-500"></div>
                        Medical 22%
                    </div>
                    <div class="flex items-center gap-1.5 text-xs text-muted-foreground">
                        <div class="h-2.5 w-2.5 rounded-sm bg-gray-400"></div>
                        Other 11%
                    </div>
                </div>
            </DashboardCard>

            <DashboardCard title="Monthly attendance vs 90% target">
                <div
                    class="flex h-[185px] w-full items-center justify-center rounded border border-dashed border-border bg-muted/40 text-sm text-muted-foreground"
                >
                    [Bar Chart Placeholder]
                </div>
            </DashboardCard>
        </div>
    </div>
</template>
