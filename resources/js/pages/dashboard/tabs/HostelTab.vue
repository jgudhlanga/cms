<script setup lang="ts">
import { AlertTriangle, Bed, Building, Coins, DoorOpen, UserCheck } from 'lucide-vue-next';
import DashboardCard from '../components/DashboardCard.vue';
import MetricCard from '../components/MetricCard.vue';
</script>

<template>
    <div class="mt-4 flex flex-col gap-4">
        <div class="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-4">
            <MetricCard title="Total bed capacity" value="420" subtext="Across 4 blocks" trend="neutral">
                <template #icon><Bed class="h-4 w-4" /></template>
            </MetricCard>
            <MetricCard title="Currently occupied" value="382" subtext="91% occupancy" trend="warning">
                <template #icon><UserCheck class="h-4 w-4" /></template>
            </MetricCard>
            <MetricCard title="Beds available" value="38" subtext="After Block C incident" trend="neutral">
                <template #icon><DoorOpen class="h-4 w-4" /></template>
            </MetricCard>
            <MetricCard title="Hostel fee collection" value="83%" subtext="USD 14.2k outstanding" trend="warning">
                <template #icon><Coins class="h-4 w-4" /></template>
            </MetricCard>
        </div>

        <div class="grid grid-cols-1 gap-4 lg:grid-cols-2">
            <DashboardCard title="Occupancy by hostel block">
                <div class="mt-1 flex flex-col gap-0">
                    <div class="flex items-center gap-3 border-b border-gray-100 py-3 last:border-0">
                        <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-blue-50 text-blue-600">
                            <Building class="h-5 w-5" />
                        </div>
                        <div class="flex-1">
                            <div class="text-[13px] font-medium text-gray-900">Block A — Male</div>
                            <div class="text-xs text-gray-500">Full occupancy · No issues reported</div>
                            <div class="mt-1.5 h-1.5 overflow-hidden rounded-sm bg-gray-100">
                                <div class="h-1.5 rounded-sm bg-blue-500" style="width: 100%"></div>
                            </div>
                        </div>
                        <div class="w-16 text-right">
                            <div class="text-[13px] font-medium text-gray-900">120/120</div>
                            <div class="mt-0.5 text-xs text-gray-500">
                                <span class="inline-block rounded-full bg-emerald-100 px-2 py-0.5 text-[10px] text-emerald-700">100%</span>
                            </div>
                        </div>
                    </div>

                    <div class="flex items-center gap-3 border-b border-gray-100 py-3 last:border-0">
                        <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-pink-50 text-pink-700">
                            <Building class="h-5 w-5" />
                        </div>
                        <div class="flex-1">
                            <div class="text-[13px] font-medium text-gray-900">Block B — Female</div>
                            <div class="text-xs text-gray-500">2 rooms under maintenance</div>
                            <div class="mt-1.5 h-1.5 overflow-hidden rounded-sm bg-gray-100">
                                <div class="h-1.5 rounded-sm bg-pink-500" style="width: 93%"></div>
                            </div>
                        </div>
                        <div class="w-16 text-right">
                            <div class="text-[13px] font-medium text-gray-900">112/120</div>
                            <div class="mt-0.5 text-xs text-gray-500">
                                <span class="inline-block rounded-full bg-emerald-100 px-2 py-0.5 text-[10px] text-emerald-700">93%</span>
                            </div>
                        </div>
                    </div>

                    <div class="flex items-center gap-3 border-b border-gray-100 py-3 last:border-0">
                        <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-amber-50 text-amber-700">
                            <AlertTriangle class="h-5 w-5" />
                        </div>
                        <div class="flex-1">
                            <div class="text-[13px] font-medium text-gray-900">Block C — Male (Chitepo)</div>
                            <div class="text-xs text-rose-600">Burst pipe — 48 students displaced</div>
                            <div class="mt-1.5 h-1.5 overflow-hidden rounded-sm bg-gray-100">
                                <div class="h-1.5 rounded-sm bg-amber-500" style="width: 60%"></div>
                            </div>
                        </div>
                        <div class="w-16 text-right">
                            <div class="text-[13px] font-medium text-gray-900">72/120</div>
                            <div class="mt-0.5 text-xs text-gray-500">
                                <span class="inline-block rounded-full bg-amber-100 px-2 py-0.5 text-[10px] text-amber-700">60%</span>
                            </div>
                        </div>
                    </div>

                    <div class="flex items-center gap-3 border-b border-gray-100 py-3 last:border-0">
                        <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-emerald-50 text-emerald-700">
                            <Building class="h-5 w-5" />
                        </div>
                        <div class="flex-1">
                            <div class="text-[13px] font-medium text-gray-900">Block D — Female</div>
                            <div class="text-xs text-gray-500">Waiting list active (12 students)</div>
                            <div class="mt-1.5 h-1.5 overflow-hidden rounded-sm bg-gray-100">
                                <div class="h-1.5 rounded-sm bg-emerald-500" style="width: 65%"></div>
                            </div>
                        </div>
                        <div class="w-16 text-right">
                            <div class="text-[13px] font-medium text-gray-900">78/120</div>
                            <div class="mt-0.5 text-xs text-gray-500">
                                <span class="inline-block rounded-full bg-amber-100 px-2 py-0.5 text-[10px] text-amber-700">65%</span>
                            </div>
                        </div>
                    </div>
                </div>
            </DashboardCard>

            <DashboardCard title="Occupancy trend (past 6 months)">
                <div
                    class="flex h-[185px] w-full items-center justify-center rounded border border-dashed border-gray-200 bg-gray-50 text-sm text-gray-500"
                >
                    [Line Chart Placeholder]
                </div>

                <div class="mt-6 mb-2 text-xs font-medium tracking-wider text-gray-500 uppercase">Gender split — hostel residents</div>
                <div class="mt-1 mb-2 flex items-center gap-2">
                    <div class="w-24 shrink-0 text-xs text-gray-900">Male</div>
                    <div class="h-1.5 flex-1 overflow-hidden rounded-sm bg-gray-100">
                        <div class="h-1.5 rounded-sm bg-blue-500" style="width: 51%"></div>
                    </div>
                    <div class="w-8 text-right text-xs text-gray-500">192</div>
                </div>
                <div class="flex items-center gap-2">
                    <div class="w-24 shrink-0 text-xs text-gray-900">Female</div>
                    <div class="h-1.5 flex-1 overflow-hidden rounded-sm bg-gray-100">
                        <div class="h-1.5 rounded-sm bg-pink-500" style="width: 49%"></div>
                    </div>
                    <div class="w-8 text-right text-xs text-gray-500">190</div>
                </div>
            </DashboardCard>
        </div>

        <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
            <DashboardCard title="Maintenance & facilities">
                <div class="mt-2 flex flex-col gap-0">
                    <div class="flex items-center justify-between border-b border-gray-100 py-1.5 last:border-0">
                        <span class="text-xs text-gray-500">Open maintenance requests</span>
                        <span class="text-xs font-medium text-gray-900">18</span>
                    </div>
                    <div class="flex items-center justify-between border-b border-gray-100 py-1.5 last:border-0">
                        <span class="text-xs text-gray-500">Urgent / safety-critical</span>
                        <span class="text-xs font-medium text-gray-900"
                            ><span class="inline-block rounded-full bg-rose-100 px-2 py-0.5 text-[10px] text-rose-700">4</span></span
                        >
                    </div>
                    <div class="flex items-center justify-between border-b border-gray-100 py-1.5 last:border-0">
                        <span class="text-xs text-gray-500">Resolved this month</span>
                        <span class="text-xs font-medium text-gray-900">27</span>
                    </div>
                    <div class="flex items-center justify-between border-b border-gray-100 py-1.5 last:border-0">
                        <span class="text-xs text-gray-500">Avg resolution time</span>
                        <span class="text-xs font-medium text-gray-900">3.2 days</span>
                    </div>
                    <div class="flex items-center justify-between border-b border-gray-100 py-1.5 last:border-0">
                        <span class="text-xs text-gray-500">Planned maintenance (Aug)</span>
                        <span class="text-xs font-medium text-gray-900">Block A plumbing</span>
                    </div>
                </div>
            </DashboardCard>

            <DashboardCard title="Hostel fees & payments">
                <div class="mt-2 flex flex-col gap-0">
                    <div class="flex items-center justify-between border-b border-gray-100 py-1.5 last:border-0">
                        <span class="text-xs text-gray-500">Semester fee per student</span>
                        <span class="text-xs font-medium text-gray-900">USD 180</span>
                    </div>
                    <div class="flex items-center justify-between border-b border-gray-100 py-1.5 last:border-0">
                        <span class="text-xs text-gray-500">Fully paid</span>
                        <span class="text-xs font-medium text-gray-900">272 (71%)</span>
                    </div>
                    <div class="flex items-center justify-between border-b border-gray-100 py-1.5 last:border-0">
                        <span class="text-xs text-gray-500">Partial / payment plan</span>
                        <span class="text-xs font-medium text-gray-900">74 (19%)</span>
                    </div>
                    <div class="flex items-center justify-between border-b border-gray-100 py-1.5 last:border-0">
                        <span class="text-xs text-gray-500">In arrears (&gt;30 days)</span>
                        <span class="text-xs font-medium text-gray-900"
                            ><span class="inline-block rounded-full bg-amber-100 px-2 py-0.5 text-[10px] text-amber-700">36 (9%)</span></span
                        >
                    </div>
                    <div class="flex items-center justify-between border-b border-gray-100 py-1.5 last:border-0">
                        <span class="text-xs text-gray-500">Risk of eviction</span>
                        <span class="text-xs font-medium text-gray-900"
                            ><span class="inline-block rounded-full bg-rose-100 px-2 py-0.5 text-[10px] text-rose-700">12</span></span
                        >
                    </div>
                </div>
            </DashboardCard>

            <DashboardCard title="Disciplinary — hostel">
                <div class="mt-2 flex flex-col gap-0">
                    <div class="flex items-center justify-between border-b border-gray-100 py-1.5 last:border-0">
                        <span class="text-xs text-gray-500">Active warnings issued</span>
                        <span class="text-xs font-medium text-gray-900">14</span>
                    </div>
                    <div class="flex items-center justify-between border-b border-gray-100 py-1.5 last:border-0">
                        <span class="text-xs text-gray-500">Noise / curfew violations</span>
                        <span class="text-xs font-medium text-gray-900">9</span>
                    </div>
                    <div class="flex items-center justify-between border-b border-gray-100 py-1.5 last:border-0">
                        <span class="text-xs text-gray-500">Substance-related incidents</span>
                        <span class="text-xs font-medium text-gray-900">3</span>
                    </div>
                    <div class="flex items-center justify-between border-b border-gray-100 py-1.5 last:border-0">
                        <span class="text-xs text-gray-500">Evictions (semester to date)</span>
                        <span class="text-xs font-medium text-gray-900">2</span>
                    </div>
                    <div class="flex items-center justify-between border-b border-gray-100 py-1.5 last:border-0">
                        <span class="text-xs text-gray-500">Pending disciplinary hearings</span>
                        <span class="text-xs font-medium text-gray-900">4</span>
                    </div>
                </div>
            </DashboardCard>
        </div>
    </div>
</template>
