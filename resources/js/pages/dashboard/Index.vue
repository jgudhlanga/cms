<script setup lang="ts">
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { DailyDistribution, DepartmentDistribution, EnrolmentSummary, HostelDashboard, LevelDistribution } from '@/types/dasboard';
import { AcademicCalendar } from '@/types/academic-calendar';
import { AuthObject } from '@/types/data-pagination';
import { IntakePeriod } from '@/types/institution';
import { BreadcrumbItemInterface } from '@/types/ui';
import { SelectOption } from '@/types/utils';
import { useDashboardStore } from '@/store/dashboard/useDashboardStore';
import { Head, router } from '@inertiajs/vue3';
import { School } from 'lucide-vue-next';
import { storeToRefs } from 'pinia';
import { computed, onMounted, ref } from 'vue';

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
    enrolmentSummary: EnrolmentSummary;
    hostelDashboard: HostelDashboard | null;
    academicCalendar: AcademicCalendar;
    academicCalendars: AcademicCalendar[];
    academicContextSubtitle: string;
    intakePeriods: IntakePeriod[];
    intakePeriod: IntakePeriod;
    visibleTabs: string[];
    dashboardTitle: string;
    moduleEnabled: boolean;
}

const props = defineProps<Props>();

const { activeTab } = storeToRefs(useDashboardStore());
const intakePeriodModel = ref<SelectOption | null>(null);

const defaultTab = computed(() => props.visibleTabs[0] ?? 'overview');

const resolvedActiveTab = computed({
    get: () => (props.visibleTabs.includes(activeTab.value) ? activeTab.value : defaultTab.value),
    set: (value: string) => {
        activeTab.value = value;
    },
});

const showTab = (tab: string) => props.visibleTabs.includes(tab);

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
            academic_calendar_id: String(props.academicCalendar.id),
        },
        {
            preserveState: true,
            preserveScroll: true,
        },
    );
};

const handleAcademicCalendarChange = (event: Event) => {
    const target = event.target as HTMLSelectElement;
    const params: Record<string, string> = {
        academic_calendar_id: target.value,
    };

    if (props.intakePeriod?.id) {
        params.intake_period_id = String(props.intakePeriod.id);
    }

    router.get(window.location.pathname, params, {
        preserveState: true,
        preserveScroll: true,
    });
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
                        {{ dashboardTitle }}
                    </h1>
                    <p class="mt-0.5 text-[11px] text-gray-500">
                        {{ academicContextSubtitle }}
                    </p>
                </div>
                <div class="flex items-center gap-2">
                    <span class="flex items-center gap-1.5 rounded-full bg-emerald-100 px-2 py-0.5 text-[11px] text-emerald-800">
                        <span class="h-1.5 w-1.5 rounded-full bg-emerald-600"></span> {{ $t('dashboard.live') }}
                    </span>
                    <select
                        v-if="academicCalendars.length > 0"
                        class="rounded border border-gray-300 px-2 py-1 text-xs focus:border-emerald-500 focus:ring-emerald-500"
                        :aria-label="$tChoice('academic_calendar.academic_calendar', 1)"
                        :value="String(academicCalendar.id)"
                        @change="handleAcademicCalendarChange"
                    >
                        <option v-for="calendar in academicCalendars" :key="calendar.id" :value="String(calendar.id)">
                            {{ calendar.attributes.shortLabel }}
                        </option>
                    </select>
                </div>
            </div>

            <!-- Tabs Layout -->
            <Tabs v-model="resolvedActiveTab" class="w-full">
                <TabsList class="flex h-auto w-fit flex-wrap justify-start rounded-md bg-gray-100/80 p-1">
                    <TabsTrigger
                        v-if="showTab('overview')"
                        value="overview"
                        class="px-3 py-1.5 text-xs data-[state=active]:bg-white data-[state=active]:shadow-sm"
                    >
                        {{ $t('dashboard.overview') }}
                    </TabsTrigger>
                    <TabsTrigger
                        v-if="showTab('academic')"
                        value="academic"
                        class="px-3 py-1.5 text-xs data-[state=active]:bg-white data-[state=active]:shadow-sm"
                    >
                        {{ $t('trans.academic') }}
                    </TabsTrigger>
                    <TabsTrigger
                        v-if="showTab('enrolments')"
                        value="enrolments"
                        class="px-3 py-1.5 text-xs data-[state=active]:bg-white data-[state=active]:shadow-sm"
                    >
                        {{ $tChoice('trans.enrolment', 2) }}
                    </TabsTrigger>
                    <TabsTrigger
                        v-if="showTab('attendance')"
                        value="attendance"
                        class="px-3 py-1.5 text-xs data-[state=active]:bg-white data-[state=active]:shadow-sm"
                    >
                        {{ $t('dashboard.attendance') }}
                    </TabsTrigger>
                    <TabsTrigger
                        v-if="showTab('staff')"
                        value="staff"
                        class="px-3 py-1.5 text-xs data-[state=active]:bg-white data-[state=active]:shadow-sm"
                    >
                        {{ $t('trans.staff') }}
                    </TabsTrigger>
                    <TabsTrigger
                        v-if="showTab('finance')"
                        value="finance"
                        class="px-3 py-1.5 text-xs data-[state=active]:bg-white data-[state=active]:shadow-sm"
                    >
                        {{ $tChoice('trans.finance', 2) }}
                    </TabsTrigger>
                    <TabsTrigger
                        v-if="showTab('hostel')"
                        value="hostel"
                        class="px-3 py-1.5 text-xs data-[state=active]:bg-white data-[state=active]:shadow-sm"
                    >
                        {{ $t('dashboard.hostel') }}
                    </TabsTrigger>
                </TabsList>

                <!-- Tab Contents -->
                <TabsContent v-if="showTab('overview')" value="overview" class="mt-0">
                    <OverviewTab />
                </TabsContent>

                <TabsContent v-if="showTab('academic')" value="academic" class="mt-0">
                    <AcademicTab />
                </TabsContent>

                <TabsContent v-if="showTab('enrolments')" value="enrolments" class="mt-0">
                    <EnrolmentsTab
                        :department-distribution="departmentDistribution"
                        :level-distribution="levelDistribution"
                        :daily-distribution="dailyDistribution"
                        :enrolment-summary="enrolmentSummary"
                        :intake-periods="intakePeriods"
                        v-model:intakePeriodModel="intakePeriodModel"
                        :handle-filter-change="handleFilterChange"
                    />
                </TabsContent>

                <TabsContent v-if="showTab('attendance')" value="attendance" class="mt-0">
                    <AttendanceTab />
                </TabsContent>

                <TabsContent v-if="showTab('staff')" value="staff" class="mt-0">
                    <StaffTab />
                </TabsContent>

                <TabsContent v-if="showTab('finance')" value="finance" class="mt-0">
                    <FinanceTab />
                </TabsContent>

                <TabsContent v-if="showTab('hostel')" value="hostel" class="mt-0">
                    <HostelTab v-if="hostelDashboard" :hostel-dashboard="hostelDashboard" />
                </TabsContent>
            </Tabs>
        </div>
    </PageContainer>
</template>
