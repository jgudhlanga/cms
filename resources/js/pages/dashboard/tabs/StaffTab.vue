<script setup lang="ts">
import { Clock, UserCheck, Users, UserX } from 'lucide-vue-next';
import DashboardCard from '../components/DashboardCard.vue';
import MetricCard from '../components/MetricCard.vue';
</script>

<template>
    <div class="mt-4 flex flex-col gap-4">
        <div class="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-4">
            <MetricCard title="Total staff" value="318" subtext="214 academic, 104 admin" trend="neutral">
                <template #icon><Users class="h-4 w-4" /></template>
            </MetricCard>
            <MetricCard title="Present today" value="304" subtext="95.6% attendance" trend="neutral">
                <template #icon><UserCheck class="h-4 w-4" /></template>
            </MetricCard>
            <MetricCard title="On leave today" value="14" subtext="8 approved, 6 sick" trend="neutral">
                <template #icon><UserX class="h-4 w-4" /></template>
            </MetricCard>
            <MetricCard title="Unfilled sessions" value="11" subtext="This week" trend="warning">
                <template #icon><Clock class="h-4 w-4" /></template>
            </MetricCard>
        </div>

        <div class="grid grid-cols-1 gap-4 lg:grid-cols-2">
            <DashboardCard title="Student-to-lecturer ratio by department">
                <div class="mt-1 flex flex-col gap-2">
                    <div class="flex items-center gap-2">
                        <div class="w-32 shrink-0 truncate text-xs text-gray-900">Engineering Tech</div>
                        <div class="h-2 flex-1 overflow-hidden rounded-sm bg-gray-100">
                            <div class="h-2 rounded-sm bg-rose-500" style="width: 100%"></div>
                        </div>
                        <div class="w-12 text-right text-[11px] text-gray-500">1:28</div>
                    </div>
                    <div class="flex items-center gap-2">
                        <div class="w-32 shrink-0 truncate text-xs text-gray-900">ICT</div>
                        <div class="h-2 flex-1 overflow-hidden rounded-sm bg-gray-100">
                            <div class="h-2 rounded-sm bg-rose-500" style="width: 86%"></div>
                        </div>
                        <div class="w-12 text-right text-[11px] text-gray-500">1:24</div>
                    </div>
                    <div class="flex items-center gap-2">
                        <div class="w-32 shrink-0 truncate text-xs text-gray-900">Applied Sciences</div>
                        <div class="h-2 flex-1 overflow-hidden rounded-sm bg-gray-100">
                            <div class="h-2 rounded-sm bg-orange-400" style="width: 75%"></div>
                        </div>
                        <div class="w-12 text-right text-[11px] text-gray-500">1:21</div>
                    </div>
                    <div class="flex items-center gap-2">
                        <div class="w-32 shrink-0 truncate text-xs text-gray-900">Commerce & Mgt</div>
                        <div class="h-2 flex-1 overflow-hidden rounded-sm bg-gray-100">
                            <div class="h-2 rounded-sm bg-orange-400" style="width: 68%"></div>
                        </div>
                        <div class="w-12 text-right text-[11px] text-gray-500">1:19</div>
                    </div>
                    <div class="flex items-center gap-2">
                        <div class="w-32 shrink-0 truncate text-xs text-gray-900">Automotive</div>
                        <div class="h-2 flex-1 overflow-hidden rounded-sm bg-gray-100">
                            <div class="h-2 rounded-sm bg-orange-400" style="width: 61%"></div>
                        </div>
                        <div class="w-12 text-right text-[11px] text-gray-500">1:17</div>
                    </div>
                    <div class="flex items-center gap-2">
                        <div class="w-32 shrink-0 truncate text-xs text-gray-900">Hospitality</div>
                        <div class="h-2 flex-1 overflow-hidden rounded-sm bg-gray-100">
                            <div class="h-2 rounded-sm bg-emerald-500" style="width: 50%"></div>
                        </div>
                        <div class="w-12 text-right text-[11px] text-gray-500">1:14</div>
                    </div>
                    <div class="flex items-center gap-2">
                        <div class="w-32 shrink-0 truncate text-xs text-gray-900">Fashion & Design</div>
                        <div class="h-2 flex-1 overflow-hidden rounded-sm bg-gray-100">
                            <div class="h-2 rounded-sm bg-emerald-500" style="width: 43%"></div>
                        </div>
                        <div class="w-12 text-right text-[11px] text-gray-500">1:12</div>
                    </div>
                </div>
            </DashboardCard>

            <DashboardCard title="Staff breakdown by category">
                <div
                    class="mb-2 flex h-[160px] w-full items-center justify-center rounded border border-dashed border-gray-200 bg-gray-50 text-sm text-gray-500"
                >
                    [Donut Chart Placeholder]
                </div>
                <div class="mt-2 mb-4 flex flex-wrap gap-3">
                    <div class="flex items-center gap-1.5 text-xs text-gray-500">
                        <div class="h-2.5 w-2.5 rounded-sm bg-blue-500"></div>
                        Academic 67%
                    </div>
                    <div class="flex items-center gap-1.5 text-xs text-gray-500">
                        <div class="h-2.5 w-2.5 rounded-sm bg-indigo-500"></div>
                        Admin 20%
                    </div>
                    <div class="flex items-center gap-1.5 text-xs text-gray-500">
                        <div class="h-2.5 w-2.5 rounded-sm bg-gray-400"></div>
                        Support 13%
                    </div>
                </div>

                <div class="flex flex-col gap-0">
                    <div class="flex items-center justify-between border-b border-gray-100 py-1.5 last:border-0">
                        <span class="text-xs text-gray-500">Full-time lecturers</span>
                        <span class="text-xs font-medium text-gray-900">168</span>
                    </div>
                    <div class="flex items-center justify-between border-b border-gray-100 py-1.5 last:border-0">
                        <span class="text-xs text-gray-500">Part-time / sessional</span>
                        <span class="text-xs font-medium text-gray-900">46</span>
                    </div>
                    <div class="flex items-center justify-between border-b border-gray-100 py-1.5 last:border-0">
                        <span class="text-xs text-gray-500">Staff with postgrad qualifications</span>
                        <span class="text-xs font-medium text-gray-900">124 (58%)</span>
                    </div>
                    <div class="flex items-center justify-between border-b border-gray-100 py-1.5 last:border-0">
                        <span class="text-xs text-gray-500">Staff on study leave</span>
                        <span class="text-xs font-medium text-gray-900">9</span>
                    </div>
                </div>
            </DashboardCard>
        </div>

        <div class="grid grid-cols-1 gap-4 lg:grid-cols-2">
            <DashboardCard title="Gender split — academic staff">
                <div class="mt-1 mb-2 flex items-center gap-2">
                    <div class="w-24 shrink-0 text-xs text-gray-900">Male</div>
                    <div class="h-1.5 flex-1 overflow-hidden rounded-sm bg-gray-100">
                        <div class="h-1.5 rounded-sm bg-blue-500" style="width: 68%"></div>
                    </div>
                    <div class="w-8 text-right text-xs text-gray-500">68%</div>
                </div>
                <div class="mb-4 flex items-center gap-2">
                    <div class="w-24 shrink-0 text-xs text-gray-900">Female</div>
                    <div class="h-1.5 flex-1 overflow-hidden rounded-sm bg-gray-100">
                        <div class="h-1.5 rounded-sm bg-pink-500" style="width: 32%"></div>
                    </div>
                    <div class="w-8 text-right text-xs text-gray-500">32%</div>
                </div>

                <div class="mt-4 mb-2 text-xs font-medium tracking-wider text-gray-500 uppercase">Workshops & labs over capacity</div>
                <table class="w-full table-fixed border-collapse text-left text-sm">
                    <thead>
                        <tr>
                            <th class="w-[36%] border-b border-gray-100 pb-2 text-xs font-medium text-gray-500">Room</th>
                            <th class="w-[32%] border-b border-gray-100 pb-2 text-xs font-medium text-gray-500">Department</th>
                            <th class="w-[16%] border-b border-gray-100 pb-2 text-xs font-medium text-gray-500">Cap</th>
                            <th class="w-[16%] border-b border-gray-100 pb-2 text-xs font-medium text-gray-500">Now</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr class="border-b border-gray-100 last:border-0">
                            <td class="truncate py-2 text-gray-900">Eng Workshop B</td>
                            <td class="py-2 text-gray-900">Engineering</td>
                            <td class="py-2 text-gray-900">30</td>
                            <td class="py-2"><span class="inline-block rounded-full bg-rose-100 px-2 py-0.5 text-[10px] text-rose-700">46</span></td>
                        </tr>
                        <tr class="border-b border-gray-100 last:border-0">
                            <td class="truncate py-2 text-gray-900">ICT Lab 3</td>
                            <td class="py-2 text-gray-900">ICT</td>
                            <td class="py-2 text-gray-900">25</td>
                            <td class="py-2"><span class="inline-block rounded-full bg-rose-100 px-2 py-0.5 text-[10px] text-rose-700">36</span></td>
                        </tr>
                        <tr class="border-b border-gray-100 last:border-0">
                            <td class="truncate py-2 text-gray-900">Auto Workshop</td>
                            <td class="py-2 text-gray-900">Automotive</td>
                            <td class="py-2 text-gray-900">20</td>
                            <td class="py-2">
                                <span class="inline-block rounded-full bg-amber-100 px-2 py-0.5 text-[10px] text-amber-700">27</span>
                            </td>
                        </tr>
                        <tr class="border-b border-gray-100 last:border-0">
                            <td class="truncate py-2 text-gray-900">Accounting Lab</td>
                            <td class="py-2 text-gray-900">Commerce</td>
                            <td class="py-2 text-gray-900">40</td>
                            <td class="py-2">
                                <span class="inline-block rounded-full bg-amber-100 px-2 py-0.5 text-[10px] text-amber-700">51</span>
                            </td>
                        </tr>
                        <tr class="border-b border-gray-100 last:border-0">
                            <td class="truncate py-2 text-gray-900">Science Lab 1</td>
                            <td class="py-2 text-gray-900">Applied Sci</td>
                            <td class="py-2 text-gray-900">24</td>
                            <td class="py-2">
                                <span class="inline-block rounded-full bg-amber-100 px-2 py-0.5 text-[10px] text-amber-700">29</span>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </DashboardCard>

            <DashboardCard title="Monthly staff attendance trend">
                <div
                    class="flex h-[185px] w-full items-center justify-center rounded border border-dashed border-gray-200 bg-gray-50 text-sm text-gray-500"
                >
                    [Line Chart Placeholder]
                </div>
            </DashboardCard>
        </div>
    </div>
</template>
