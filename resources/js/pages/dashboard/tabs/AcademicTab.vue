<script setup lang="ts">
import { Award, Check, TrendingDown, TrendingUp, UserMinus, X } from 'lucide-vue-next';
import DashboardCard from '../components/DashboardCard.vue';
import MetricCard from '../components/MetricCard.vue';
</script>

<template>
    <div class="mt-4 flex flex-col gap-4">
        <div class="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-4">
            <MetricCard title="Pass rate" value="74.8%" subtext="+3.1% vs Sem 1" trend="up">
                <template #icon><Check class="h-4 w-4" /></template>
                <template #trendIcon><TrendingUp class="h-3 w-3" /></template>
            </MetricCard>
            <MetricCard title="Fail rate" value="25.2%" subtext="Down from 28.3%" trend="down">
                <template #icon><X class="h-4 w-4" /></template>
                <template #trendIcon><TrendingDown class="h-3 w-3" /></template>
            </MetricCard>
            <MetricCard title="Distinctions" value="9.4%" subtext="+1.2% vs Sem 1" trend="up">
                <template #icon><Award class="h-4 w-4" /></template>
                <template #trendIcon><TrendingUp class="h-3 w-3" /></template>
            </MetricCard>
            <MetricCard title="Academic probation" value="218" subtext="3.2% of students" trend="warning">
                <template #icon><UserMinus class="h-4 w-4" /></template>
            </MetricCard>
        </div>

        <div class="grid grid-cols-1 gap-4 lg:grid-cols-12">
            <div class="lg:col-span-7">
                <DashboardCard title="Grade distribution">
                    <div
                        class="flex h-[185px] w-full items-center justify-center rounded border border-dashed border-gray-200 bg-gray-50 text-sm text-gray-500"
                    >
                        [Bar Chart Placeholder: Distinction 9%, Merit 18%, Pass 48%, Fail 25%]
                    </div>
                </DashboardCard>
            </div>
            <div class="lg:col-span-5">
                <DashboardCard title="Pass rate by department">
                    <div class="mt-1 flex flex-col gap-2">
                        <div class="flex items-center gap-2">
                            <div class="w-32 shrink-0 truncate text-xs text-gray-900">Hospitality</div>
                            <div class="h-2 flex-1 overflow-hidden rounded-sm bg-gray-100">
                                <div class="h-2 rounded-sm bg-emerald-500" style="width: 88%"></div>
                            </div>
                            <div class="w-12 text-right text-xs text-gray-500">88%</div>
                        </div>
                        <div class="flex items-center gap-2">
                            <div class="w-32 shrink-0 truncate text-xs text-gray-900">Fashion & Design</div>
                            <div class="h-2 flex-1 overflow-hidden rounded-sm bg-gray-100">
                                <div class="h-2 rounded-sm bg-emerald-500" style="width: 86%"></div>
                            </div>
                            <div class="w-12 text-right text-xs text-gray-500">86%</div>
                        </div>
                        <div class="flex items-center gap-2">
                            <div class="w-32 shrink-0 truncate text-xs text-gray-900">Commerce & Mgt</div>
                            <div class="h-2 flex-1 overflow-hidden rounded-sm bg-gray-100">
                                <div class="h-2 rounded-sm bg-emerald-500" style="width: 82%"></div>
                            </div>
                            <div class="w-12 text-right text-xs text-gray-500">82%</div>
                        </div>
                        <div class="flex items-center gap-2">
                            <div class="w-32 shrink-0 truncate text-xs text-gray-900">Applied Sciences</div>
                            <div class="h-2 flex-1 overflow-hidden rounded-sm bg-gray-100">
                                <div class="h-2 rounded-sm bg-orange-400" style="width: 78%"></div>
                            </div>
                            <div class="w-12 text-right text-xs text-gray-500">78%</div>
                        </div>
                        <div class="flex items-center gap-2">
                            <div class="w-32 shrink-0 truncate text-xs text-gray-900">ICT</div>
                            <div class="h-2 flex-1 overflow-hidden rounded-sm bg-gray-100">
                                <div class="h-2 rounded-sm bg-orange-400" style="width: 76%"></div>
                            </div>
                            <div class="w-12 text-right text-xs text-gray-500">76%</div>
                        </div>
                        <div class="flex items-center gap-2">
                            <div class="w-32 shrink-0 truncate text-xs text-gray-900">Built Environment</div>
                            <div class="h-2 flex-1 overflow-hidden rounded-sm bg-gray-100">
                                <div class="h-2 rounded-sm bg-orange-400" style="width: 73%"></div>
                            </div>
                            <div class="w-12 text-right text-xs text-gray-500">73%</div>
                        </div>
                        <div class="flex items-center gap-2">
                            <div class="w-32 shrink-0 truncate text-xs text-gray-900">Automotive</div>
                            <div class="h-2 flex-1 overflow-hidden rounded-sm bg-gray-100">
                                <div class="h-2 rounded-sm bg-rose-500" style="width: 69%"></div>
                            </div>
                            <div class="w-12 text-right text-xs text-gray-500">69%</div>
                        </div>
                        <div class="flex items-center gap-2">
                            <div class="w-32 shrink-0 truncate text-xs text-gray-900">Engineering Tech</div>
                            <div class="h-2 flex-1 overflow-hidden rounded-sm bg-gray-100">
                                <div class="h-2 rounded-sm bg-rose-500" style="width: 64%"></div>
                            </div>
                            <div class="w-12 text-right text-xs text-gray-500">64%</div>
                        </div>
                    </div>
                </DashboardCard>
            </div>
        </div>

        <div class="grid grid-cols-1 gap-4 lg:grid-cols-2">
            <DashboardCard title="Module failure hotspots">
                <table class="w-full table-fixed border-collapse text-left text-sm">
                    <thead>
                        <tr>
                            <th class="w-[42%] border-b border-gray-100 pb-2 text-xs font-medium text-gray-500">Module</th>
                            <th class="w-[18%] border-b border-gray-100 pb-2 text-xs font-medium text-gray-500">Enrolled</th>
                            <th class="w-[14%] border-b border-gray-100 pb-2 text-xs font-medium text-gray-500">Failing</th>
                            <th class="w-[26%] border-b border-gray-100 pb-2 text-xs font-medium text-gray-500">Rate</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr class="border-b border-gray-100 last:border-0">
                            <td class="truncate py-2 text-gray-900">Engineering Maths 2</td>
                            <td class="py-2 text-gray-900">480</td>
                            <td class="py-2 text-gray-900">163</td>
                            <td class="py-2"><span class="inline-block rounded-full bg-rose-100 px-2 py-0.5 text-[10px] text-rose-700">34%</span></td>
                        </tr>
                        <tr class="border-b border-gray-100 last:border-0">
                            <td class="truncate py-2 text-gray-900">Circuit Theory</td>
                            <td class="py-2 text-gray-900">310</td>
                            <td class="py-2 text-gray-900">90</td>
                            <td class="py-2"><span class="inline-block rounded-full bg-rose-100 px-2 py-0.5 text-[10px] text-rose-700">29%</span></td>
                        </tr>
                        <tr class="border-b border-gray-100 last:border-0">
                            <td class="truncate py-2 text-gray-900">Accounting 1</td>
                            <td class="py-2 text-gray-900">520</td>
                            <td class="py-2 text-gray-900">130</td>
                            <td class="py-2">
                                <span class="inline-block rounded-full bg-amber-100 px-2 py-0.5 text-[10px] text-amber-700">25%</span>
                            </td>
                        </tr>
                        <tr class="border-b border-gray-100 last:border-0">
                            <td class="truncate py-2 text-gray-900">Technical Drawing</td>
                            <td class="py-2 text-gray-900">260</td>
                            <td class="py-2 text-gray-900">60</td>
                            <td class="py-2">
                                <span class="inline-block rounded-full bg-amber-100 px-2 py-0.5 text-[10px] text-amber-700">23%</span>
                            </td>
                        </tr>
                        <tr class="border-b border-gray-100 last:border-0">
                            <td class="truncate py-2 text-gray-900">Computer Networks</td>
                            <td class="py-2 text-gray-900">390</td>
                            <td class="py-2 text-gray-900">74</td>
                            <td class="py-2">
                                <span class="inline-block rounded-full bg-amber-100 px-2 py-0.5 text-[10px] text-amber-700">19%</span>
                            </td>
                        </tr>
                        <tr class="border-b border-gray-100 last:border-0">
                            <td class="truncate py-2 text-gray-900">Business English</td>
                            <td class="py-2 text-gray-900">640</td>
                            <td class="py-2 text-gray-900">96</td>
                            <td class="py-2">
                                <span class="inline-block rounded-full bg-amber-100 px-2 py-0.5 text-[10px] text-amber-700">15%</span>
                            </td>
                        </tr>
                        <tr class="border-b border-gray-100 last:border-0">
                            <td class="truncate py-2 text-gray-900">ICT Fundamentals</td>
                            <td class="py-2 text-gray-900">710</td>
                            <td class="py-2 text-gray-900">85</td>
                            <td class="py-2">
                                <span class="inline-block rounded-full bg-emerald-100 px-2 py-0.5 text-[10px] text-emerald-700">12%</span>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </DashboardCard>

            <DashboardCard title="Industrial attachment status">
                <div
                    class="mb-2 flex h-[160px] w-full items-center justify-center rounded border border-dashed border-gray-200 bg-gray-50 text-sm text-gray-500"
                >
                    [Donut Chart Placeholder]
                </div>
                <div class="mt-2 flex flex-wrap gap-3">
                    <div class="flex items-center gap-1.5 text-xs text-gray-500">
                        <div class="h-2.5 w-2.5 rounded-sm bg-emerald-500"></div>
                        Placed 58%
                    </div>
                    <div class="flex items-center gap-1.5 text-xs text-gray-500">
                        <div class="h-2.5 w-2.5 rounded-sm bg-amber-500"></div>
                        Awaiting 27%
                    </div>
                    <div class="flex items-center gap-1.5 text-xs text-gray-500">
                        <div class="h-2.5 w-2.5 rounded-sm bg-gray-400"></div>
                        Exempt 15%
                    </div>
                </div>
            </DashboardCard>
        </div>
    </div>
</template>
