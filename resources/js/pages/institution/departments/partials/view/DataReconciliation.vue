<script setup lang="ts">
import { useUtils } from '@/composables/core/useUtils';
import HttpService from '@/services/http.service';
import type { DepartmentReconciliationCounts } from '@/types/department-reconciliation';
import { InstitutionDepartment } from '@/types/institution';
import { ChevronRight, FileSpreadsheet, RefreshCw } from 'lucide-vue-next';
import { trans } from 'laravel-vue-i18n';
import { computed, onMounted, ref } from 'vue';

const props = defineProps<{
    department: InstitutionDepartment;
}>();

const { navigateTo, getQueryParams } = useUtils();

const counts = ref<DepartmentReconciliationCounts>({
    enrolledThisYear: 0,
    enrolledThisPeriod: 0,
    calendarYear: Number(getQueryParams().academic_year ?? new Date().getFullYear()),
    studyPosition: null,
});

const studyPosition = computed(() => counts.value.studyPosition ?? null);

const studyPositionTiles = computed(() => {
    const summary = studyPosition.value;

    if (!summary) {
        return [];
    }

    return [
        { key: 'in-scope', label: trans('students.study_position_department_in_scope'), value: summary.inScope, tone: 'text-foreground' },
        { key: 'confirmed', label: trans('students.study_position_state_confirmed'), value: summary.confirmed, tone: 'text-emerald-700 dark:text-emerald-400' },
        { key: 'unconfirmed', label: trans('students.study_position_state_unconfirmed'), value: summary.unconfirmed, tone: 'text-red-700 dark:text-red-400' },
        { key: 'follow-up', label: trans('students.study_position_state_follow_up'), value: summary.followUp, tone: 'text-red-700 dark:text-red-400' },
        { key: 'needs-review', label: trans('students.study_position_state_needs_review'), value: summary.needsReview, tone: 'text-amber-700 dark:text-amber-400' },
    ];
});

const departmentId = computed(() => String(props.department.id ?? ''));

const queryContext = computed(() => {
    const query = getQueryParams();
    const params: Record<string, string | number> = {};

    if (query.academic_year) {
        params.academic_year = String(query.academic_year);
        params.calendar_year = String(query.academic_year);
    }

    if (query.mode_of_study_id) {
        params.mode_of_study_id = String(query.mode_of_study_id);
    }

    return params;
});

const loadCounts = async (): Promise<void> => {
    const params = new URLSearchParams();
    const year = String(queryContext.value.calendar_year ?? queryContext.value.academic_year ?? new Date().getFullYear());
    params.set('calendar_year', year);

    if (queryContext.value.mode_of_study_id) {
        params.set('mode_of_study_id', String(queryContext.value.mode_of_study_id));
    }

    counts.value = (await HttpService.get(
        `${route('department-data-reconciliation.counts', { department: departmentId.value })}?${params.toString()}`,
    )) as DepartmentReconciliationCounts;
};

onMounted(() => {
    void loadCounts();
});

const tools = computed(() => [
    {
        key: 'enrolment-vs-class-list',
        icon: FileSpreadsheet,
        title: trans('trans.department_enrolment_vs_class_list'),
        description: trans('trans.department_enrolment_vs_class_list_description'),
        href: route('department-data-reconciliation.enrolment-vs-class-list', {
            department: departmentId.value,
            ...queryContext.value,
        }),
        count: counts.value.enrolledThisYear,
        countLabel: trans('trans.department_reconciliation_enrolled_this_year'),
        alertCount: 0,
        alertLabel: '',
    },
    {
        key: 'semester-reconciliation',
        icon: RefreshCw,
        title: trans('trans.department_semester_reconciliation'),
        description: trans('trans.department_semester_reconciliation_description'),
        href: route('department-data-reconciliation.semester-reconciliation', {
            department: departmentId.value,
            ...queryContext.value,
        }),
        count: counts.value.enrolledThisPeriod,
        countLabel: trans('trans.department_reconciliation_enrolled_this_period'),
        // Students still to confirm their study position: the import is the quickest way to settle them.
        alertCount: studyPosition.value ? studyPosition.value.unconfirmed + studyPosition.value.followUp : 0,
        alertLabel: trans('students.study_position_state_unconfirmed'),
    },
]);

const sectionLabelClass = 'text-[0.63rem] font-semibold uppercase tracking-[0.12em] text-muted-foreground';
</script>

<template>
    <div class="w-full min-w-0 space-y-6 pt-2">
        <section v-if="studyPosition" class="space-y-2">
            <h2 :class="sectionLabelClass">
                {{ trans('students.study_position_department_title', { period: studyPosition.periodLabel }) }}
            </h2>
            <div class="grid grid-cols-2 gap-2 sm:grid-cols-5">
                <div v-for="tile in studyPositionTiles" :key="tile.key" class="rounded-lg border border-border bg-card px-3 py-2">
                    <p :class="['text-lg leading-none font-semibold tabular-nums', tile.tone]">
                        {{ tile.value.toLocaleString() }}
                    </p>
                    <p class="mt-1 text-[11px] text-muted-foreground">{{ tile.label }}</p>
                </div>
            </div>
        </section>

        <section class="space-y-2">
            <h2 :class="sectionLabelClass">{{ trans('trans.department_reconciliation_tools') }}</h2>

            <div class="divide-y divide-border rounded-xl border border-border bg-card">
                <button
                    v-for="item in tools"
                    :key="item.key"
                    type="button"
                    class="flex w-full cursor-pointer items-center justify-between gap-3 p-4 text-left transition-colors hover:bg-muted/50"
                    @click="navigateTo(item.href)"
                >
                    <div class="flex min-w-0 flex-1 items-start gap-3">
                        <span class="mt-0.5 flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-muted text-muted-foreground">
                            <component :is="item.icon" class="h-4 w-4" />
                        </span>
                        <div class="min-w-0">
                            <p class="text-sm font-medium text-foreground">{{ item.title }}</p>
                            <p class="text-xs text-muted-foreground">{{ item.description }}</p>
                        </div>
                    </div>

                    <div class="flex shrink-0 items-center gap-3">
                        <span
                            v-if="item.alertCount > 0"
                            class="rounded-full bg-red-500/10 px-2 py-0.5 text-[11px] font-medium text-red-700 dark:text-red-400"
                            :title="item.alertLabel"
                        >
                            {{ item.alertCount }}
                        </span>
                        <span
                            v-if="item.count > 0"
                            class="rounded-full bg-primary/10 px-2 py-0.5 text-[11px] font-medium text-primary"
                            :title="item.countLabel"
                        >
                            {{ item.count }}
                        </span>
                        <ChevronRight class="h-4 w-4 text-muted-foreground" />
                    </div>
                </button>
            </div>
        </section>
    </div>
</template>
