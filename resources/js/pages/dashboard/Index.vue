<script setup lang="ts">
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { AcademicDashboard, DailyDistribution, DepartmentDistribution, EnrolmentSummary, FinanceDashboard, HostelDashboard, LevelDistribution, OverviewDashboard, StaffDashboard } from '@/types/dashboard';
import { AcademicCalendar } from '@/types/academic-calendar';
import { AuthObject } from '@/types/data-pagination';
import { IntakePeriod } from '@/types/institution';
import { LecturerDashboard } from '@/types/lecturer';
import type {
    ExaminationChartLabels,
    ExaminationComparison,
    ExaminationDashboardFiltersState,
    ExaminationFilterOptions,
    ExaminationStatusCounts,
    ExaminationStatusLabels,
} from '@/types/examinations';
import { BreadcrumbItemInterface } from '@/types/ui';
import { SelectOption } from '@/types/utils';
import { useDashboardStore } from '@/store/dashboard/useDashboardStore';
import { Head, router } from '@inertiajs/vue3';
import { School } from 'lucide-vue-next';
import { storeToRefs } from 'pinia';
import { computed, ref, watch } from 'vue';

import AcademicTab from './tabs/AcademicTab.vue';
import AttendanceTab from './tabs/AttendanceTab.vue';
import EnrolmentsTab from './tabs/EnrolmentsTab.vue';
import ExaminationsTab from './tabs/ExaminationsTab.vue';
import FinanceTab from './tabs/FinanceTab.vue';
import HostelTab from './tabs/HostelTab.vue';
import OverviewTab from './tabs/OverviewTab.vue';
import StaffTab from './tabs/StaffTab.vue';
import TeachingTab from './tabs/TeachingTab.vue';

const breadcrumbs: BreadcrumbItemInterface[] = [{ transChoiceKey: 'dashboard' }];

interface Props {
    auth: AuthObject;
    errors: object;
    departmentDistribution: DepartmentDistribution[];
    levelDistribution: LevelDistribution[];
    dailyDistribution: DailyDistribution[];
    enrolmentSummary: EnrolmentSummary;
    overviewDashboard: OverviewDashboard | null;
    hostelDashboard: HostelDashboard | null;
    financeDashboard: FinanceDashboard | null;
    staffDashboard: StaffDashboard | null;
    academicDashboard: AcademicDashboard | null;
    teachingDashboard: LecturerDashboard | null;
    academicCalendar: AcademicCalendar;
    academicContextSubtitle: string;
    intakePeriods: IntakePeriod[];
    intakePeriod: IntakePeriod;
    visibleTabs: string[];
    dashboardTitle: string;
    moduleEnabled: boolean;
    filters: ExaminationDashboardFiltersState | null;
    filterOptions: ExaminationFilterOptions | null;
    statusCounts: ExaminationStatusCounts | null;
    statusLabels: ExaminationStatusLabels | null;
    chartLabels: ExaminationChartLabels | null;
    totalCandidates: number | null;
    passRate: number | null;
    onlineViewedCount: number | null;
    onlineViewedRate: number | null;
    comparison: ExaminationComparison | null;
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

const examinationExtraQuery = computed(() => ({
    intake_period_id: props.intakePeriod?.id ? String(props.intakePeriod.id) : undefined,
    academic_calendar_id: props.academicCalendar?.id ? String(props.academicCalendar.id) : undefined,
}));

const hasExaminationDashboard = computed(
    () =>
        props.filters !== null
        && props.filterOptions !== null
        && props.statusCounts !== null
        && props.statusLabels !== null
        && props.chartLabels !== null
        && props.totalCandidates !== null,
);

watch(
    () => props.intakePeriod,
    (period) => {
        if (period) {
            intakePeriodModel.value = { value: Number(period.id), label: period.attributes.name };
        }
    },
    { immediate: true },
);

const handleFilterChange = (option: SelectOption) => {
    router.get(
        window.location.pathname,
        {
            intake_period_id: String(option.value),
            academic_calendar_id: String(props.academicCalendar.id),
            session: props.filters?.session ?? undefined,
            discipline: props.filters?.discipline ?? undefined,
            subject_code: props.filters?.subject_code ?? undefined,
            compare_session: props.filters?.compare_session ?? undefined,
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
            <div class="mb-4 border-b border-border pb-4">
                <h1 class="flex items-center gap-2 text-base font-medium text-foreground">
                    <School class="h-5 w-5 text-muted-foreground" />
                    {{ dashboardTitle }}
                </h1>
                <p class="mt-0.5 text-[11px] text-muted-foreground">
                    {{ academicContextSubtitle }}
                </p>
            </div>

            <Tabs v-model="resolvedActiveTab" class="w-full">
                <TabsList class="flex h-auto w-fit flex-wrap justify-start rounded-md bg-muted/80 p-1">
                    <TabsTrigger
                        v-if="showTab('overview')"
                        value="overview"
                        class="px-3 py-1.5 text-xs data-[state=active]:shadow-sm"
                    >
                        {{ $t('dashboard.overview') }}
                    </TabsTrigger>
                    <TabsTrigger
                        v-if="showTab('academic')"
                        value="academic"
                        class="px-3 py-1.5 text-xs data-[state=active]:shadow-sm"
                    >
                        {{ $t('trans.academic') }}
                    </TabsTrigger>
                    <TabsTrigger
                        v-if="showTab('enrolments')"
                        value="enrolments"
                        class="px-3 py-1.5 text-xs data-[state=active]:shadow-sm"
                    >
                        {{ $tChoice('trans.enrolment', 2) }}
                    </TabsTrigger>
                    <TabsTrigger
                        v-if="showTab('attendance')"
                        value="attendance"
                        class="px-3 py-1.5 text-xs data-[state=active]:shadow-sm"
                    >
                        {{ $t('dashboard.attendance') }}
                    </TabsTrigger>
                    <TabsTrigger
                        v-if="showTab('staff')"
                        value="staff"
                        class="px-3 py-1.5 text-xs data-[state=active]:shadow-sm"
                    >
                        {{ $t('trans.staff') }}
                    </TabsTrigger>
                    <TabsTrigger
                        v-if="showTab('finance')"
                        value="finance"
                        class="px-3 py-1.5 text-xs data-[state=active]:shadow-sm"
                    >
                        {{ $tChoice('trans.finance', 2) }}
                    </TabsTrigger>
                    <TabsTrigger
                        v-if="showTab('hostel')"
                        value="hostel"
                        class="px-3 py-1.5 text-xs data-[state=active]:shadow-sm"
                    >
                        {{ $t('dashboard.hostel') }}
                    </TabsTrigger>
                    <TabsTrigger
                        v-if="showTab('examinations')"
                        value="examinations"
                        class="px-3 py-1.5 text-xs data-[state=active]:shadow-sm"
                    >
                        {{ $t('dashboard.exams') }}
                    </TabsTrigger>
                </TabsList>

                <TabsContent v-if="showTab('overview')" value="overview" class="mt-0">
                    <OverviewTab
                        v-if="overviewDashboard"
                        :overview-dashboard="overviewDashboard"
                        :visible-tabs="visibleTabs"
                    />
                </TabsContent>

                <TabsContent v-if="showTab('academic')" value="academic" class="mt-0">
                    <div class="space-y-3">
                        <AcademicTab v-if="academicDashboard" :academic-dashboard="academicDashboard" />
                        <TeachingTab v-if="teachingDashboard" :teaching-dashboard="teachingDashboard" />
                    </div>
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
                    <StaffTab v-if="staffDashboard" :staff-dashboard="staffDashboard" />
                </TabsContent>

                <TabsContent v-if="showTab('finance')" value="finance" class="mt-0">
                    <FinanceTab v-if="financeDashboard" :finance-dashboard="financeDashboard" />
                </TabsContent>

                <TabsContent v-if="showTab('hostel')" value="hostel" class="mt-0">
                    <HostelTab v-if="hostelDashboard" :hostel-dashboard="hostelDashboard" />
                </TabsContent>

                <TabsContent v-if="showTab('examinations')" value="examinations" class="mt-0">
                    <ExaminationsTab
                        v-if="hasExaminationDashboard"
                        :filters="filters!"
                        :filter-options="filterOptions!"
                        :status-counts="statusCounts!"
                        :status-labels="statusLabels!"
                        :chart-labels="chartLabels!"
                        :total-candidates="totalCandidates!"
                        :pass-rate="passRate"
                        :online-viewed-count="onlineViewedCount!"
                        :online-viewed-rate="onlineViewedRate"
                        :comparison="comparison"
                        :extra-query="examinationExtraQuery"
                    />
                </TabsContent>
            </Tabs>
        </div>
    </PageContainer>
</template>
