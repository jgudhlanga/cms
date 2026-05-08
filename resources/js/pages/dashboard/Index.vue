<script setup lang="ts">
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { DailyDistribution, DepartmentDistribution, LevelDistribution } from '@/types/dasboard';
import { AuthObject } from '@/types/data-pagination';
import { IntakePeriod } from '@/types/institution';
import { BreadcrumbItemInterface } from '@/types/ui';
import { SelectOption } from '@/types/utils';
import { Head, router } from '@inertiajs/vue3';
import { School } from 'lucide-vue-next';
import { onMounted, ref } from 'vue';

// Tab Components
import AcademicTab from './tabs/AcademicTab.vue';
import AttendanceTab from './tabs/AttendanceTab.vue';
import EnrolmentsTab from './tabs/EnrolmentsTab.vue';
import FinanceTab from './tabs/FinanceTab.vue';
import HostelTab from './tabs/HostelTab.vue';
import OverviewTab from './tabs/OverviewTab.vue';
import StaffTab from './tabs/StaffTab.vue';

const breadcrumbs: BreadcrumbItemInterface[] = [{ transChoiceKey: 'dashboard' }];

interface Props {
    auth: AuthObject;
    errors: object;
    departmentDistribution: DepartmentDistribution[];
    levelDistribution: LevelDistribution[];
    dailyDistribution: DailyDistribution[];
    intakePeriods: IntakePeriod[];
    intakePeriod: IntakePeriod;
}

const props = defineProps<Props>();

const intakePeriodModel = ref<SelectOption | null>(null);

onMounted(() => {
    if (props.intakePeriod) {
        intakePeriodModel.value = { value: Number(props.intakePeriod.id), label: props.intakePeriod.attributes.name };
    }
});

const handleFilterChange = (option: SelectOption) => {
    router.get(
        window.location.pathname,
        {
            intake_period_id: String(option.value),
        },
        {
            preserveState: true,
            preserveScroll: true,
        },
    );
};
</script>

<template>
    <Head :title="$tChoice('trans.dashboard', 2)" />
    <PageContainer :breadcrumbs="breadcrumbs">
        <div class="flex w-full flex-col">
            <!-- Topbar -->
            <div class="mb-4 flex items-center justify-between border-b border-gray-200 pb-4">
                <div>
                    <h1 class="flex items-center gap-2 text-base font-medium text-gray-900">
                        <School class="h-5 w-5 text-gray-500" />
                        {{ $t('dashboard.principals_dashboard') }}
                    </h1>
                    <p class="mt-0.5 text-[11px] text-gray-500">
                        {{ $t('dashboard.ministry_subtitle') }}
                    </p>
                </div>
                <div class="flex items-center gap-2">
                    <span class="flex items-center gap-1.5 rounded-full bg-emerald-100 px-2 py-0.5 text-[11px] text-emerald-800">
                        <span class="h-1.5 w-1.5 rounded-full bg-emerald-600"></span> {{ $t('dashboard.live') }}
                    </span>
                    <select
                        class="rounded border border-gray-300 px-2 py-1 text-xs focus:border-emerald-500 focus:ring-emerald-500"
                        aria-label="Select semester"
                    >
                        <option>{{ $t('dashboard.sem_2_2025') }}</option>
                        <option>{{ $t('dashboard.sem_1_2025') }}</option>
                        <option>{{ $t('dashboard.sem_2_2024') }}</option>
                    </select>
                </div>
            </div>

            <!-- Tabs Layout -->
            <Tabs defaultValue="overview" class="w-full">
                <TabsList class="flex h-auto w-fit flex-wrap justify-start rounded-md bg-gray-100/80 p-1">
                    <TabsTrigger value="overview" class="px-3 py-1.5 text-xs data-[state=active]:bg-white data-[state=active]:shadow-sm"
                        >{{ $t('dashboard.overview') }}</TabsTrigger
                    >
                    <TabsTrigger value="academic" class="px-3 py-1.5 text-xs data-[state=active]:bg-white data-[state=active]:shadow-sm"
                        >{{ $t('trans.academic') }}</TabsTrigger
                    >
                    <TabsTrigger value="enrolments" class="px-3 py-1.5 text-xs data-[state=active]:bg-white data-[state=active]:shadow-sm"
                        >{{ $tChoice('trans.enrolment', 2) }}</TabsTrigger
                    >
                    <TabsTrigger value="attendance" class="px-3 py-1.5 text-xs data-[state=active]:bg-white data-[state=active]:shadow-sm"
                        >{{ $t('dashboard.attendance') }}</TabsTrigger
                    >
                    <TabsTrigger value="staff" class="px-3 py-1.5 text-xs data-[state=active]:bg-white data-[state=active]:shadow-sm"
                        >{{ $t('trans.staff') }}</TabsTrigger
                    >
                    <TabsTrigger value="finance" class="px-3 py-1.5 text-xs data-[state=active]:bg-white data-[state=active]:shadow-sm"
                        >{{ $tChoice('trans.finance', 2) }}</TabsTrigger
                    >
                    <TabsTrigger value="hostel" class="px-3 py-1.5 text-xs data-[state=active]:bg-white data-[state=active]:shadow-sm"
                        >{{ $t('dashboard.hostel') }}</TabsTrigger
                    >
                </TabsList>

                <!-- Tab Contents -->
                <TabsContent value="overview" class="mt-0">
                    <OverviewTab />
                </TabsContent>

                <TabsContent value="academic" class="mt-0">
                    <AcademicTab />
                </TabsContent>

                <TabsContent value="enrolments" class="mt-0">
                    <EnrolmentsTab
                        :department-distribution="departmentDistribution"
                        :level-distribution="levelDistribution"
                        :daily-distribution="dailyDistribution"
                        :intake-periods="intakePeriods"
                        v-model:intakePeriodModel="intakePeriodModel"
                        :handle-filter-change="handleFilterChange"
                    />
                </TabsContent>

                <TabsContent value="attendance" class="mt-0">
                    <AttendanceTab />
                </TabsContent>

                <TabsContent value="staff" class="mt-0">
                    <StaffTab />
                </TabsContent>

                <TabsContent value="finance" class="mt-0">
                    <FinanceTab />
                </TabsContent>

                <TabsContent value="hostel" class="mt-0">
                    <HostelTab />
                </TabsContent>
            </Tabs>
        </div>
    </PageContainer>
</template>
