<script setup lang="ts">
import DashboardCard from '../components/DashboardCard.vue';
import MetricCard from '../components/MetricCard.vue';
import { 
    Users, CalendarCheck, BarChart3, Coins, BookOpen, 
    Building, Bed, AlertTriangle, TrendingUp, TrendingDown 
} from 'lucide-vue-next';
</script>

<template>
    <div class="mt-4 flex flex-col gap-4">
        <!-- Top Metrics Row 1 -->
        <div class="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-4">
            <MetricCard title="Total students" value="6,840" subtext="+7.2% vs 2024" trend="up">
                <template #icon><Users class="h-4 w-4" /></template>
                <template #trendIcon><TrendingUp class="h-3 w-3" /></template>
            </MetricCard>
            <MetricCard title="Attendance rate" value="88.4%" subtext="-1.6% this week" trend="down">
                <template #icon><CalendarCheck class="h-4 w-4" /></template>
                <template #trendIcon><TrendingDown class="h-3 w-3" /></template>
            </MetricCard>
            <MetricCard title="Overall pass rate" value="74.8%" subtext="+3.1% vs Sem 1" trend="up">
                <template #icon><BarChart3 class="h-4 w-4" /></template>
                <template #trendIcon><TrendingUp class="h-3 w-3" /></template>
            </MetricCard>
            <MetricCard title="Fee collection" value="79%" subtext="USD 186k outstanding" trend="warning">
                <template #icon><Coins class="h-4 w-4" /></template>
            </MetricCard>
        </div>

        <!-- Top Metrics Row 2 -->
        <div class="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-4">
            <MetricCard title="Programmes" value="38" subtext="Cert, Dip & HND" trend="neutral">
                <template #icon><BookOpen class="h-4 w-4" /></template>
            </MetricCard>
            <MetricCard title="Departments" value="12" subtext="Across 4 schools" trend="neutral">
                <template #icon><Building class="h-4 w-4" /></template>
            </MetricCard>
            <MetricCard title="Hostel occupancy" value="91%" subtext="38 beds available" trend="warning">
                <template #icon><Bed class="h-4 w-4" /></template>
            </MetricCard>
            <MetricCard title="At-risk students" value="312" subtext="Failing 2+ modules" trend="warning">
                <template #icon><AlertTriangle class="h-4 w-4" /></template>
            </MetricCard>
        </div>

        <!-- Two Column Layout -->
        <div class="grid grid-cols-1 gap-4 lg:grid-cols-2">
            <DashboardCard title="Priority alerts">
                <div class="flex flex-col gap-0">
                    <div class="flex gap-3 border-b border-gray-100 py-2 last:border-0">
                        <div class="mt-1.5 h-2 w-2 shrink-0 rounded-full bg-rose-500"></div>
                        <div>
                            <div class="text-sm leading-snug text-gray-900">
                                Engineering Technology has a 34% module failure rate — highest across all departments
                            </div>
                            <div class="mt-0.5 text-xs text-gray-500">Updated 3 hrs ago</div>
                        </div>
                    </div>
                    <div class="flex gap-3 border-b border-gray-100 py-2 last:border-0">
                        <div class="mt-1.5 h-2 w-2 shrink-0 rounded-full bg-rose-500"></div>
                        <div>
                            <div class="text-sm leading-snug text-gray-900">
                                Chitepo Hostel Block C has a burst pipe — 48 students temporarily displaced
                            </div>
                            <div class="mt-0.5 text-xs text-gray-500">Updated 1 hr ago</div>
                        </div>
                    </div>
                    <div class="flex gap-3 border-b border-gray-100 py-2 last:border-0">
                        <div class="mt-1.5 h-2 w-2 shrink-0 rounded-full bg-amber-500"></div>
                        <div>
                            <div class="text-sm leading-snug text-gray-900">
                                162 students flagged for chronic absenteeism — welfare officer review required
                            </div>
                            <div class="mt-0.5 text-xs text-gray-500">Updated today</div>
                        </div>
                    </div>
                    <div class="flex gap-3 border-b border-gray-100 py-2 last:border-0">
                        <div class="mt-1.5 h-2 w-2 shrink-0 rounded-full bg-amber-500"></div>
                        <div>
                            <div class="text-sm leading-snug text-gray-900">USD 186k in outstanding fees — 14% of students on payment plan</div>
                            <div class="mt-0.5 text-xs text-gray-500">Updated 2 days ago</div>
                        </div>
                    </div>
                    <div class="flex gap-3 border-b border-gray-100 py-2 last:border-0">
                        <div class="mt-1.5 h-2 w-2 shrink-0 rounded-full bg-blue-500"></div>
                        <div>
                            <div class="text-sm leading-snug text-gray-900">
                                2026 January intake open — 428 applications received, deadline in 18 days
                            </div>
                            <div class="mt-0.5 text-xs text-gray-500">Updated today</div>
                        </div>
                    </div>
                    <div class="flex gap-3 border-b border-gray-100 py-2 last:border-0">
                        <div class="mt-1.5 h-2 w-2 shrink-0 rounded-full bg-emerald-500"></div>
                        <div>
                            <div class="text-sm leading-snug text-gray-900">Industrial attachment placements up 12% — 3 new MOU partners signed</div>
                            <div class="mt-0.5 text-xs text-gray-500">Updated 1 week ago</div>
                        </div>
                    </div>
                </div>
            </DashboardCard>

            <DashboardCard title="Enrolment by department">
                <div class="mt-1 flex flex-col gap-2">
                    <div class="flex items-center gap-2">
                        <div class="w-32 shrink-0 truncate text-xs text-gray-900">Engineering Tech</div>
                        <div class="h-2 flex-1 overflow-hidden rounded-sm bg-gray-100">
                            <div class="h-2 rounded-sm bg-blue-500" style="width: 100%"></div>
                        </div>
                        <div class="w-12 text-right text-xs text-gray-500">1.2k</div>
                    </div>
                    <div class="flex items-center gap-2">
                        <div class="w-32 shrink-0 truncate text-xs text-gray-900">ICT</div>
                        <div class="h-2 flex-1 overflow-hidden rounded-sm bg-gray-100">
                            <div class="h-2 rounded-sm bg-emerald-500" style="width: 87%"></div>
                        </div>
                        <div class="w-12 text-right text-xs text-gray-500">1.1k</div>
                    </div>
                    <div class="flex items-center gap-2">
                        <div class="w-32 shrink-0 truncate text-xs text-gray-900">Commerce & Mgt</div>
                        <div class="h-2 flex-1 overflow-hidden rounded-sm bg-gray-100">
                            <div class="h-2 rounded-sm bg-indigo-500" style="width: 79%"></div>
                        </div>
                        <div class="w-12 text-right text-xs text-gray-500">980</div>
                    </div>
                    <div class="flex items-center gap-2">
                        <div class="w-32 shrink-0 truncate text-xs text-gray-900">Applied Sciences</div>
                        <div class="h-2 flex-1 overflow-hidden rounded-sm bg-gray-100">
                            <div class="h-2 rounded-sm bg-pink-500" style="width: 61%"></div>
                        </div>
                        <div class="w-12 text-right text-xs text-gray-500">760</div>
                    </div>
                    <div class="flex items-center gap-2">
                        <div class="w-32 shrink-0 truncate text-xs text-gray-900">Built Environment</div>
                        <div class="h-2 flex-1 overflow-hidden rounded-sm bg-gray-100">
                            <div class="h-2 rounded-sm bg-orange-500" style="width: 52%"></div>
                        </div>
                        <div class="w-12 text-right text-xs text-gray-500">640</div>
                    </div>
                    <div class="flex items-center gap-2">
                        <div class="w-32 shrink-0 truncate text-xs text-gray-900">Automotive</div>
                        <div class="h-2 flex-1 overflow-hidden rounded-sm bg-gray-100">
                            <div class="h-2 rounded-sm bg-orange-600" style="width: 42%"></div>
                        </div>
                        <div class="w-12 text-right text-xs text-gray-500">520</div>
                    </div>
                    <div class="flex items-center gap-2">
                        <div class="w-32 shrink-0 truncate text-xs text-gray-900">Fashion & Design</div>
                        <div class="h-2 flex-1 overflow-hidden rounded-sm bg-gray-100">
                            <div class="h-2 rounded-sm bg-teal-400" style="width: 31%"></div>
                        </div>
                        <div class="w-12 text-right text-xs text-gray-500">380</div>
                    </div>
                    <div class="flex items-center gap-2">
                        <div class="w-32 shrink-0 truncate text-xs text-gray-900">Hospitality</div>
                        <div class="h-2 flex-1 overflow-hidden rounded-sm bg-gray-100">
                            <div class="h-2 rounded-sm bg-indigo-300" style="width: 25%"></div>
                        </div>
                        <div class="w-12 text-right text-xs text-gray-500">310</div>
                    </div>
                    <div class="flex items-center gap-2">
                        <div class="w-32 shrink-0 truncate text-xs text-gray-900">Other (4)</div>
                        <div class="h-2 flex-1 overflow-hidden rounded-sm bg-gray-100">
                            <div class="h-2 rounded-sm bg-gray-400" style="width: 75%"></div>
                        </div>
                        <div class="w-12 text-right text-xs text-gray-500">930</div>
                    </div>
                </div>
            </DashboardCard>
        </div>
    </div>
</template>
