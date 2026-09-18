<script setup lang="ts">
import { CalendarCheck, CalendarX, Clock, UserMinus } from 'lucide-vue-next';
import DashboardCard from '../components/DashboardCard.vue';
import DataRow from '../components/DataRow.vue';
import MetricCard from '../components/MetricCard.vue';
import StatLine from '../components/StatLine.vue';
import type { Tone } from '../components/tones';

// Placeholder figures: attendance capture is not wired to the backend yet.
const departmentAttendance: Array<{ name: string; rate: number }> = [
    { name: 'Hospitality', rate: 93 },
    { name: 'Commerce & Mgt', rate: 92 },
    { name: 'Fashion & Design', rate: 91 },
    { name: 'Applied Sciences', rate: 90 },
    { name: 'Built Environment', rate: 89 },
    { name: 'ICT', rate: 87 },
    { name: 'Automotive', rate: 85 },
    { name: 'Engineering Tech', rate: 83 },
];

const absenceTypes: Array<{ label: string; percent: number }> = [
    { label: 'Unauthorised', percent: 38 },
    { label: 'Authorised', percent: 29 },
    { label: 'Medical', percent: 22 },
    { label: 'Other', percent: 11 },
];

const attendanceTone = (rate: number): Tone => {
    if (rate >= 90) return 'emerald';
    if (rate >= 86) return 'orange';

    return 'rose';
};
</script>

<template>
    <div class="mt-3 flex flex-col gap-2.5">
        <div class="grid grid-cols-2 gap-2 sm:grid-cols-4">
            <MetricCard
                tone="emerald"
                title="Today's attendance"
                value="88.4%"
                subtext="6,047 present"
                badge="Target 90%"
                trend="neutral"
            >
                <template #icon><CalendarCheck class="h-3.5 w-3.5" /></template>
            </MetricCard>
            <MetricCard tone="rose" title="Absent today" value="793" subtext="11.6% of students" trend="down">
                <template #icon><CalendarX class="h-3.5 w-3.5" /></template>
            </MetricCard>
            <MetricCard
                tone="amber"
                title="Chronic absentees"
                value="162"
                subtext="Missing 10%+ days"
                trend="warning"
            >
                <template #icon><UserMinus class="h-3.5 w-3.5" /></template>
            </MetricCard>
            <MetricCard
                tone="slate"
                title="Late arrivals (week)"
                value="184"
                subtext="Mostly 08:00 sessions"
                trend="neutral"
            >
                <template #icon><Clock class="h-3.5 w-3.5" /></template>
            </MetricCard>
        </div>

        <div class="grid grid-cols-1 gap-2.5 lg:grid-cols-12">
            <div class="lg:col-span-7">
                <DashboardCard title="Weekly attendance trend vs target (90%)">
                    <div
                        class="flex h-40 w-full items-center justify-center rounded-lg border border-dashed border-border/70 bg-muted/20 text-[11px] text-muted-foreground"
                    >
                        Line chart placeholder
                    </div>
                </DashboardCard>
            </div>
            <div class="lg:col-span-5">
                <DashboardCard title="Attendance by department">
                    <div class="flex flex-col gap-2">
                        <DataRow
                            v-for="row in departmentAttendance"
                            :key="row.name"
                            :label="row.name"
                            :value="`${row.rate}%`"
                            :percent="row.rate"
                            :tone="attendanceTone(row.rate)"
                            label-width-class="w-32"
                            value-width-class="w-10"
                        />
                    </div>
                </DashboardCard>
            </div>
        </div>

        <div class="grid grid-cols-1 gap-2.5 lg:grid-cols-2">
            <DashboardCard title="Absence type breakdown">
                <div class="flex flex-col gap-0.5">
                    <StatLine
                        v-for="row in absenceTypes"
                        :key="row.label"
                        :label="row.label"
                        :value="`${row.percent}%`"
                    />
                </div>
            </DashboardCard>

            <DashboardCard title="Monthly attendance vs 90% target">
                <div
                    class="flex h-40 w-full items-center justify-center rounded-lg border border-dashed border-border/70 bg-muted/20 text-[11px] text-muted-foreground"
                >
                    Bar chart placeholder
                </div>
            </DashboardCard>
        </div>
    </div>
</template>
