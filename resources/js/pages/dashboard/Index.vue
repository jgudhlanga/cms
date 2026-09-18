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
import { Head, router, usePage } from '@inertiajs/vue3';
import { School } from 'lucide-vue-next';
import { storeToRefs } from 'pinia';
import { computed, defineAsyncComponent, ref, watch } from 'vue';

import TabSkeleton from './tabs/TabSkeleton.vue';

// Each tab, with its charts, downloads the first time it is opened.
const AcademicTab = defineAsyncComponent(() => import('./tabs/AcademicTab.vue'));
const AttendanceTab = defineAsyncComponent(() => import('./tabs/AttendanceTab.vue'));
const EnrolmentsTab = defineAsyncComponent(() => import('./tabs/EnrolmentsTab.vue'));
const ExaminationsTab = defineAsyncComponent(() => import('./tabs/ExaminationsTab.vue'));
const FinanceTab = defineAsyncComponent(() => import('./tabs/FinanceTab.vue'));
const HostelTab = defineAsyncComponent(() => import('./tabs/HostelTab.vue'));
const OverviewTab = defineAsyncComponent(() => import('./tabs/OverviewTab.vue'));
const StaffTab = defineAsyncComponent(() => import('./tabs/StaffTab.vue'));
const TeachingTab = defineAsyncComponent(() => import('./tabs/TeachingTab.vue'));

const breadcrumbs: BreadcrumbItemInterface[] = [{ transChoiceKey: 'dashboard' }];

interface Props {
    auth: AuthObject;
    errors: object;
    // Tab data is undefined until its tab has been loaded (see TAB_PROP_KEYS), and null when not permitted.
    departmentDistribution?: DepartmentDistribution[];
    levelDistribution?: LevelDistribution[];
    dailyDistribution?: DailyDistribution[];
    enrolmentSummary?: EnrolmentSummary;
    overviewDashboard?: OverviewDashboard | null;
    hostelDashboard?: HostelDashboard | null;
    financeDashboard?: FinanceDashboard | null;
    staffDashboard?: StaffDashboard | null;
    academicDashboard?: AcademicDashboard | null;
    teachingDashboard?: LecturerDashboard | null;
    academicCalendar: AcademicCalendar;
    academicContextSubtitle: string;
    intakePeriods: IntakePeriod[];
    intakePeriod: IntakePeriod;
    visibleTabs: string[];
    activeTab: string | null;
    dashboardTitle: string;
    moduleEnabled: boolean;
    filters?: ExaminationDashboardFiltersState | null;
    filterOptions?: ExaminationFilterOptions | null;
    statusCounts?: ExaminationStatusCounts | null;
    statusLabels?: ExaminationStatusLabels | null;
    chartLabels?: ExaminationChartLabels | null;
    totalCandidates?: number | null;
    passRate?: number | null;
    onlineViewedCount?: number | null;
    onlineViewedRate?: number | null;
    comparison?: ExaminationComparison | null;
}

const props = defineProps<Props>();

const { activeTab: storedActiveTab } = storeToRefs(useDashboardStore());
const intakePeriodModel = ref<SelectOption | null>(null);
const page = usePage();

const showTab = (tab: string) => props.visibleTabs.includes(tab);

/** The ?tab= value, when it names a tab this user is allowed to see. */
const tabFromUrl = (): string | null => {
    const tab = new URLSearchParams(page.url.split('?')[1] ?? '').get('tab');

    return tab !== null && showTab(tab) ? tab : null;
};

/** Keeps every other query param (intake period, exam filters) intact while swapping the tab. */
const urlForTab = (tab: string): string => {
    const [path, query] = page.url.split('?');
    const params = new URLSearchParams(query ?? '');
    params.set('tab', tab);

    return `${path}?${params.toString()}`;
};

// An explicit ?tab= wins so shared links and refreshes open the right tab; otherwise resume the last one used.
const activeTab = ref(
    tabFromUrl()
        ?? (showTab(storedActiveTab.value)
            ? storedActiveTab.value
            : (props.activeTab ?? props.visibleTabs[0] ?? 'overview')),
);

// The server sends only the active tab's data with the page; other tabs load the first time they open.
const TAB_PROP_KEYS: Record<string, Array<keyof Props>> = {
    overview: ['overviewDashboard'],
    academic: ['academicDashboard', 'teachingDashboard'],
    enrolments: ['departmentDistribution', 'levelDistribution', 'dailyDistribution', 'enrolmentSummary'],
    staff: ['staffDashboard'],
    finance: ['financeDashboard'],
    hostel: ['hostelDashboard'],
    examinations: [
        'filters',
        'filterOptions',
        'statusCounts',
        'statusLabels',
        'chartLabels',
        'totalCandidates',
        'passRate',
        'onlineViewedCount',
        'onlineViewedRate',
        'comparison',
    ],
};

const isTabLoaded = (tab: string): boolean => (TAB_PROP_KEYS[tab] ?? []).every((key) => props[key] !== undefined);

const loadingTab = ref<string | null>(null);

watch(
    activeTab,
    (tab) => {
        storedActiveTab.value = tab;

        if (!isTabLoaded(tab) && loadingTab.value !== tab) {
            // Requesting ?tab= fetches this tab's props and moves the address bar in one go.
            loadingTab.value = tab;
            router.get(
                urlForTab(tab),
                {},
                {
                    // activeTab rides along so the server-resolved tab never goes stale behind a partial load.
                    only: [...(TAB_PROP_KEYS[tab] ?? []), 'activeTab'] as string[],
                    preserveState: true,
                    preserveScroll: true,
                    replace: true,
                    onFinish: () => {
                        if (loadingTab.value === tab) {
                            loadingTab.value = null;
                        }
                    },
                },
            );

            return;
        }

        if (tabFromUrl() !== tab) {
            // Props are already client-side, so keep the URL shareable without a round trip.
            router.replace({ url: urlForTab(tab), preserveState: true, preserveScroll: true });
        }
    },
    { immediate: true },
);

// A filter change or browser back/forward can land on a different tab server-side; follow it.
watch(
    () => props.activeTab,
    (tab) => {
        if (tab !== null && showTab(tab) && tab !== activeTab.value) {
            activeTab.value = tab;
        }
    },
);

const examinationExtraQuery = computed(() => ({
    tab: 'examinations',
    intake_period_id: props.intakePeriod?.id ? String(props.intakePeriod.id) : undefined,
    academic_calendar_id: props.academicCalendar?.id ? String(props.academicCalendar.id) : undefined,
}));

const hasExaminationDashboard = computed(
    () =>
        props.filters != null
        && props.filterOptions != null
        && props.statusCounts != null
        && props.statusLabels != null
        && props.chartLabels != null
        && props.totalCandidates != null,
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
            tab: activeTab.value,
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
            <div
                class="mb-3 flex flex-wrap items-center justify-between gap-2 rounded-xl border border-border/60 bg-linear-to-r from-muted/60 via-muted/25 to-transparent px-3 py-2.5"
            >
                <div class="flex items-center gap-2.5">
                    <div class="rounded-lg bg-primary/10 p-1.5 text-primary">
                        <School class="h-4 w-4" />
                    </div>
                    <div class="min-w-0">
                        <h1 class="truncate text-sm font-semibold tracking-tight text-foreground">
                            {{ dashboardTitle }}
                        </h1>
                        <p class="mt-0.5 truncate text-[11px] text-muted-foreground">
                            {{ academicContextSubtitle }}
                        </p>
                    </div>
                </div>
            </div>

            <Tabs v-model="activeTab" class="w-full">
                <TabsList class="flex h-auto w-fit flex-wrap justify-start rounded-lg bg-muted/80 p-0.5">
                    <TabsTrigger
                        v-if="showTab('overview')"
                        value="overview"
                        class="rounded-md px-2.5 py-1 text-[11px] font-medium tracking-tight data-[state=active]:bg-card data-[state=active]:shadow-sm"
                    >
                        {{ $t('dashboard.overview') }}
                    </TabsTrigger>
                    <TabsTrigger
                        v-if="showTab('academic')"
                        value="academic"
                        class="rounded-md px-2.5 py-1 text-[11px] font-medium tracking-tight data-[state=active]:bg-card data-[state=active]:shadow-sm"
                    >
                        {{ $t('trans.academic') }}
                    </TabsTrigger>
                    <TabsTrigger
                        v-if="showTab('enrolments')"
                        value="enrolments"
                        class="rounded-md px-2.5 py-1 text-[11px] font-medium tracking-tight data-[state=active]:bg-card data-[state=active]:shadow-sm"
                    >
                        {{ $tChoice('trans.enrolment', 2) }}
                    </TabsTrigger>
                    <TabsTrigger
                        v-if="showTab('attendance')"
                        value="attendance"
                        class="rounded-md px-2.5 py-1 text-[11px] font-medium tracking-tight data-[state=active]:bg-card data-[state=active]:shadow-sm"
                    >
                        {{ $t('dashboard.attendance') }}
                    </TabsTrigger>
                    <TabsTrigger
                        v-if="showTab('staff')"
                        value="staff"
                        class="rounded-md px-2.5 py-1 text-[11px] font-medium tracking-tight data-[state=active]:bg-card data-[state=active]:shadow-sm"
                    >
                        {{ $t('trans.staff') }}
                    </TabsTrigger>
                    <TabsTrigger
                        v-if="showTab('finance')"
                        value="finance"
                        class="rounded-md px-2.5 py-1 text-[11px] font-medium tracking-tight data-[state=active]:bg-card data-[state=active]:shadow-sm"
                    >
                        {{ $tChoice('trans.finance', 2) }}
                    </TabsTrigger>
                    <TabsTrigger
                        v-if="showTab('hostel')"
                        value="hostel"
                        class="rounded-md px-2.5 py-1 text-[11px] font-medium tracking-tight data-[state=active]:bg-card data-[state=active]:shadow-sm"
                    >
                        {{ $t('dashboard.hostel') }}
                    </TabsTrigger>
                    <TabsTrigger
                        v-if="showTab('examinations')"
                        value="examinations"
                        class="rounded-md px-2.5 py-1 text-[11px] font-medium tracking-tight data-[state=active]:bg-card data-[state=active]:shadow-sm"
                    >
                        {{ $t('dashboard.exams') }}
                    </TabsTrigger>
                </TabsList>

                <TabsContent v-if="showTab('overview')" value="overview" class="mt-0">
                    <TabSkeleton v-if="!isTabLoaded('overview')" />
                    <OverviewTab
                        v-else-if="overviewDashboard"
                        :overview-dashboard="overviewDashboard"
                        :visible-tabs="visibleTabs"
                        :intake-period-name="intakePeriod?.attributes?.name ?? null"
                    />
                </TabsContent>

                <TabsContent v-if="showTab('academic')" value="academic" class="mt-0">
                    <TabSkeleton v-if="!isTabLoaded('academic')" />
                    <div v-else class="space-y-3">
                        <AcademicTab v-if="academicDashboard" :academic-dashboard="academicDashboard" />
                        <TeachingTab v-if="teachingDashboard" :teaching-dashboard="teachingDashboard" />
                    </div>
                </TabsContent>

                <TabsContent v-if="showTab('enrolments')" value="enrolments" class="mt-0">
                    <TabSkeleton v-if="!isTabLoaded('enrolments') || !enrolmentSummary" />
                    <EnrolmentsTab
                        v-else
                        :department-distribution="departmentDistribution ?? []"
                        :level-distribution="levelDistribution ?? []"
                        :daily-distribution="dailyDistribution ?? []"
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
                    <TabSkeleton v-if="!isTabLoaded('staff')" />
                    <StaffTab v-else-if="staffDashboard" :staff-dashboard="staffDashboard" />
                </TabsContent>

                <TabsContent v-if="showTab('finance')" value="finance" class="mt-0">
                    <TabSkeleton v-if="!isTabLoaded('finance')" />
                    <FinanceTab v-else-if="financeDashboard" :finance-dashboard="financeDashboard" />
                </TabsContent>

                <TabsContent v-if="showTab('hostel')" value="hostel" class="mt-0">
                    <TabSkeleton v-if="!isTabLoaded('hostel')" />
                    <HostelTab v-else-if="hostelDashboard" :hostel-dashboard="hostelDashboard" />
                </TabsContent>

                <TabsContent v-if="showTab('examinations')" value="examinations" class="mt-0">
                    <TabSkeleton v-if="!isTabLoaded('examinations')" />
                    <ExaminationsTab
                        v-else-if="hasExaminationDashboard"
                        :filters="filters!"
                        :filter-options="filterOptions!"
                        :status-counts="statusCounts!"
                        :status-labels="statusLabels!"
                        :chart-labels="chartLabels!"
                        :total-candidates="totalCandidates!"
                        :pass-rate="passRate ?? null"
                        :online-viewed-count="onlineViewedCount!"
                        :online-viewed-rate="onlineViewedRate ?? null"
                        :comparison="comparison ?? null"
                        :extra-query="examinationExtraQuery"
                    />
                </TabsContent>
            </Tabs>
        </div>
    </PageContainer>
</template>
