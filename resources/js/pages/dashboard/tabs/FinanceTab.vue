<script setup lang="ts">
import { Banknote, Coins, Gift, PieChart, TrendingUp } from 'lucide-vue-next';
import DashboardCard from '../components/DashboardCard.vue';
import MetricCard from '../components/MetricCard.vue';
</script>

<template>
    <div class="mt-4 flex flex-col gap-4">
        <div class="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-4">
            <MetricCard title="Fee collection rate" value="79%" subtext="USD 186k outstanding" trend="warning">
                <template #icon><Coins class="h-4 w-4" /></template>
            </MetricCard>
            <MetricCard title="Fees collected (sem)" value="USD 714k" subtext="+6% vs Sem 1" trend="up">
                <template #icon><Banknote class="h-4 w-4" /></template>
                <template #trendIcon><TrendingUp class="h-3 w-3" /></template>
            </MetricCard>
            <MetricCard title="Budget utilised" value="58%" subtext="USD 2.9M of 5.0M" trend="neutral">
                <template #icon><PieChart class="h-4 w-4" /></template>
            </MetricCard>
            <MetricCard title="Bursary recipients" value="384" subtext="5.6% of students" trend="neutral">
                <template #icon><Gift class="h-4 w-4" /></template>
            </MetricCard>
        </div>

        <div class="grid grid-cols-1 gap-4 lg:grid-cols-2">
            <DashboardCard title="Fee collection by department">
                <div class="mt-1 flex flex-col gap-2">
                    <div class="flex items-center gap-2">
                        <div class="w-32 shrink-0 truncate text-xs text-gray-900">Commerce & Mgt</div>
                        <div class="h-2 flex-1 overflow-hidden rounded-sm bg-gray-100">
                            <div class="h-2 rounded-sm bg-emerald-500" style="width: 91%"></div>
                        </div>
                        <div class="w-12 text-right text-xs text-gray-500">91%</div>
                    </div>
                    <div class="flex items-center gap-2">
                        <div class="w-32 shrink-0 truncate text-xs text-gray-900">Hospitality</div>
                        <div class="h-2 flex-1 overflow-hidden rounded-sm bg-gray-100">
                            <div class="h-2 rounded-sm bg-emerald-500" style="width: 89%"></div>
                        </div>
                        <div class="w-12 text-right text-xs text-gray-500">89%</div>
                    </div>
                    <div class="flex items-center gap-2">
                        <div class="w-32 shrink-0 truncate text-xs text-gray-900">Applied Sciences</div>
                        <div class="h-2 flex-1 overflow-hidden rounded-sm bg-gray-100">
                            <div class="h-2 rounded-sm bg-emerald-500" style="width: 85%"></div>
                        </div>
                        <div class="w-12 text-right text-xs text-gray-500">85%</div>
                    </div>
                    <div class="flex items-center gap-2">
                        <div class="w-32 shrink-0 truncate text-xs text-gray-900">Built Environment</div>
                        <div class="h-2 flex-1 overflow-hidden rounded-sm bg-gray-100">
                            <div class="h-2 rounded-sm bg-orange-400" style="width: 82%"></div>
                        </div>
                        <div class="w-12 text-right text-xs text-gray-500">82%</div>
                    </div>
                    <div class="flex items-center gap-2">
                        <div class="w-32 shrink-0 truncate text-xs text-gray-900">ICT</div>
                        <div class="h-2 flex-1 overflow-hidden rounded-sm bg-gray-100">
                            <div class="h-2 rounded-sm bg-orange-400" style="width: 79%"></div>
                        </div>
                        <div class="w-12 text-right text-xs text-gray-500">79%</div>
                    </div>
                    <div class="flex items-center gap-2">
                        <div class="w-32 shrink-0 truncate text-xs text-gray-900">Automotive</div>
                        <div class="h-2 flex-1 overflow-hidden rounded-sm bg-gray-100">
                            <div class="h-2 rounded-sm bg-orange-400" style="width: 75%"></div>
                        </div>
                        <div class="w-12 text-right text-xs text-gray-500">75%</div>
                    </div>
                    <div class="flex items-center gap-2">
                        <div class="w-32 shrink-0 truncate text-xs text-gray-900">Engineering Tech</div>
                        <div class="h-2 flex-1 overflow-hidden rounded-sm bg-gray-100">
                            <div class="h-2 rounded-sm bg-rose-500" style="width: 72%"></div>
                        </div>
                        <div class="w-12 text-right text-xs text-gray-500">72%</div>
                    </div>
                    <div class="flex items-center gap-2">
                        <div class="w-32 shrink-0 truncate text-xs text-gray-900">Fashion & Design</div>
                        <div class="h-2 flex-1 overflow-hidden rounded-sm bg-gray-100">
                            <div class="h-2 rounded-sm bg-rose-500" style="width: 68%"></div>
                        </div>
                        <div class="w-12 text-right text-xs text-gray-500">68%</div>
                    </div>
                </div>
            </DashboardCard>

            <DashboardCard title="Budget utilisation by category">
                <div
                    class="flex h-[185px] w-full items-center justify-center rounded border border-dashed border-gray-200 bg-gray-50 text-sm text-gray-500"
                >
                    [Horizontal Bar Chart Placeholder]
                </div>
            </DashboardCard>
        </div>

        <div class="grid grid-cols-1 gap-4 lg:grid-cols-2">
            <DashboardCard title="Monthly fee collection vs target">
                <div
                    class="flex h-[185px] w-full items-center justify-center rounded border border-dashed border-gray-200 bg-gray-50 text-sm text-gray-500"
                >
                    [Bar Chart Placeholder]
                </div>
            </DashboardCard>

            <DashboardCard title="Outstanding fees & bursary summary">
                <div class="mt-2 flex flex-col gap-0">
                    <div class="flex items-center justify-between border-b border-gray-100 py-2 last:border-0">
                        <span class="text-xs text-gray-500">Students fully paid</span>
                        <span class="text-xs font-medium text-gray-900">4,482 (66%)</span>
                    </div>
                    <div class="flex items-center justify-between border-b border-gray-100 py-2 last:border-0">
                        <span class="text-xs text-gray-500">Students on payment plan</span>
                        <span class="text-xs font-medium text-gray-900">958 (14%)</span>
                    </div>
                    <div class="flex items-center justify-between border-b border-gray-100 py-2 last:border-0">
                        <span class="text-xs text-gray-500">Students in arrears (&gt;60 days)</span>
                        <span class="text-xs font-medium text-gray-900">312 (4.6%)</span>
                    </div>
                    <div class="flex items-center justify-between border-b border-gray-100 py-2 last:border-0">
                        <span class="text-xs text-gray-500">Students at risk of deregistration</span>
                        <span class="text-xs font-medium text-gray-900">148</span>
                    </div>
                    <div class="flex items-center justify-between border-b border-gray-100 py-2 last:border-0">
                        <span class="text-xs text-gray-500">Govt-sponsored students (HEXCO)</span>
                        <span class="text-xs font-medium text-gray-900">1,240 (18%)</span>
                    </div>
                    <div class="flex items-center justify-between border-b border-gray-100 py-2 last:border-0">
                        <span class="text-xs text-gray-500">Bursary recipients</span>
                        <span class="text-xs font-medium text-gray-900">384</span>
                    </div>
                    <div class="flex items-center justify-between border-b border-gray-100 py-2 last:border-0">
                        <span class="text-xs text-gray-500">Total bursary value (sem)</span>
                        <span class="text-xs font-medium text-gray-900">USD 76,800</span>
                    </div>
                    <div class="flex items-center justify-between border-b border-gray-100 py-2 last:border-0">
                        <span class="text-xs text-gray-500">Hostel fee collection rate</span>
                        <span class="text-xs font-medium text-gray-900">83%</span>
                    </div>
                </div>
            </DashboardCard>
        </div>
    </div>
</template>
