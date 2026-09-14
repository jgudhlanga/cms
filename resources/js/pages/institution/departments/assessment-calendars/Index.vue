<script setup lang="ts">
import { BaseButton } from '@/components/core/button';
import PageContainer from '@/components/core/page/PageContainer.vue';
import Empty from '@/components/core/util/Empty.vue';
import { useCustomConfirmDialog } from '@/composables/core/useCustomConfirmDialog';
import { ButtonSize } from '@/enums/buttons';
import { ColorVariant } from '@/enums/colors';
import { openModal } from '@/lib/alerts';
import { APP_MODULE_KEYS } from '@/lib/constants';
import {
    departmentCalendarActions,
    formatIsoDate,
    formatIsoDateRange,
    windowStatusBadgeClass,
} from '@/lib/departmentAssessmentCalendars';
import type {
    DepartmentAssessmentCalendarPermissions,
    DepartmentAssessmentCalendarRow,
} from '@/types/department-assessment-calendars';
import type { InstitutionDepartment } from '@/types/institution';
import type { Link as BreadcrumbLink } from '@/types/ui';
import { Head, Link, router } from '@inertiajs/vue3';
import { trans } from 'laravel-vue-i18n';
import { computed } from 'vue';
import CreateEdit from './partials/CreateEdit.vue';

const props = defineProps<{
    department: InstitutionDepartment;
    calendarYear: number;
    rows: DepartmentAssessmentCalendarRow[];
    can: DepartmentAssessmentCalendarPermissions;
}>();

const { open: openConfirm } = useCustomConfirmDialog();

const departmentId = computed(() => String(props.department.id));
const departmentName = computed(
    () => props.department.attributes?.department ?? props.department.attributes?.departmentCode ?? '',
);
const departmentShowUrl = computed(() => route('institution-departments.show', departmentId.value));

const breadcrumbs = computed<Array<BreadcrumbLink>>(() => [
    { transChoiceKey: 'institution', transChoiceKeyIndex: 1, href: route('institution.index') },
    {
        transChoiceKey: 'department',
        href: route('institution-departments.index', { is_academic: props.department.attributes?.isAcademic }),
    },
    { title: String(props.department.attributes?.departmentCode ?? departmentName.value), href: departmentShowUrl.value },
    { title: trans('academic_calendar.department_assessment_calendar_title') },
]);

const yearUrl = (year: number): string =>
    route('department-assessment-calendars.index', { department: departmentId.value, calendar_year: String(year) });

const openEditor = (row: DepartmentAssessmentCalendarRow) => {
    openModal({ name: APP_MODULE_KEYS.department_assessment_calendars, edit: row });
};

const revert = async (row: DepartmentAssessmentCalendarRow) => {
    const departmentCalendar = row.departmentCalendar;

    if (!departmentCalendar) {
        return;
    }

    const confirmed = await openConfirm({
        title: trans('academic_calendar.department_assessment_calendar_revert'),
        message: trans('academic_calendar.department_assessment_calendar_revert_confirm', {
            assessment: row.assessmentTypeName,
            start: formatIsoDate(row.globalStartDate),
            end: formatIsoDate(row.globalEndDate),
        }),
        note: '',
        confirmText: trans('academic_calendar.department_assessment_calendar_revert'),
        cancelText: trans('trans.cancel'),
    });

    if (!confirmed) {
        return;
    }

    router.delete(
        route('department-assessment-calendars.destroy', {
            department: departmentId.value,
            department_assessment_calendar: String(departmentCalendar.id),
        }),
        { preserveScroll: true },
    );
};

const restore = (row: DepartmentAssessmentCalendarRow) => {
    if (!row.departmentCalendar) {
        return;
    }

    router.put(
        route('department-assessment-calendars.restore', {
            department: departmentId.value,
            department_assessment_calendar: String(row.departmentCalendar.id),
        }),
        {},
        { preserveScroll: true },
    );
};
</script>

<template>
    <Head :title="$t('academic_calendar.department_assessment_calendar_title')" />
    <PageContainer :breadcrumbs="breadcrumbs" :back-url="departmentShowUrl">
        <template #backNavigationLeading>
            <div>
                <h1 class="text-lg font-semibold">
                    {{ $t('academic_calendar.department_assessment_calendar_title') }}
                    <span class="font-normal text-muted-foreground">· {{ departmentName }}</span>
                </h1>
                <p class="mt-1 max-w-3xl text-sm text-muted-foreground">
                    {{ $t('academic_calendar.department_assessment_calendar_description') }}
                </p>
            </div>
        </template>

        <div class="space-y-4">
            <nav :aria-label="$t('academic_calendar.department_assessment_calendar_year_nav')" class="flex items-center gap-2 text-sm">
                <Link :href="yearUrl(calendarYear - 1)" class="rounded-md px-2 py-1 text-primary hover:underline">
                    {{ calendarYear - 1 }}
                </Link>
                <span aria-current="page" class="rounded-md bg-muted px-2 py-1 font-semibold">{{ calendarYear }}</span>
                <Link :href="yearUrl(calendarYear + 1)" class="rounded-md px-2 py-1 text-primary hover:underline">
                    {{ calendarYear + 1 }}
                </Link>
            </nav>

            <Empty
                v-if="rows.length === 0"
                :message="$t('academic_calendar.department_assessment_calendar_empty', { year: String(calendarYear) })"
            />

            <div v-else class="overflow-x-auto rounded-md border border-border">
                <table class="w-full text-left text-sm">
                    <caption class="sr-only">
                        {{ $t('academic_calendar.department_assessment_calendar_title') }} · {{ departmentName }} · {{ calendarYear }}
                    </caption>
                    <thead>
                        <tr class="border-b border-border bg-muted/40 text-muted-foreground">
                            <th scope="col" class="px-3 py-2 font-medium">{{ $tChoice('trans.assessment_type', 1) }}</th>
                            <th scope="col" class="px-3 py-2 font-medium">
                                {{ $t('academic_calendar.department_assessment_calendar_college_window') }}
                            </th>
                            <th scope="col" class="px-3 py-2 font-medium">
                                {{ $t('academic_calendar.department_assessment_calendar_department_window') }}
                            </th>
                            <th scope="col" class="px-3 py-2 font-medium">
                                {{ $t('academic_calendar.department_assessment_calendar_effective_window') }}
                            </th>
                            <th scope="col" class="px-3 py-2 text-right font-medium">
                                <span class="sr-only">{{ $tChoice('trans.action', 2) }}</span>
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr
                            v-for="row in rows"
                            :key="row.globalCalendarId"
                            class="border-b border-border/60 align-top last:border-0"
                        >
                            <th scope="row" class="px-3 py-2.5 font-medium text-foreground">
                                {{ row.assessmentTypeName }}
                                <span class="block text-xs font-normal text-muted-foreground">
                                    {{ row.academicCalendarLabel }}
                                    <template v-if="row.modesOfStudy.length"> · {{ row.modesOfStudy.join(', ') }}</template>
                                </span>
                            </th>
                            <td class="px-3 py-2.5">{{ formatIsoDateRange(row.globalStartDate, row.globalEndDate) }}</td>
                            <td class="px-3 py-2.5">
                                <template v-if="row.departmentCalendar && !row.departmentCalendar.trashed">
                                    {{ formatIsoDateRange(row.departmentCalendar.startDate, row.departmentCalendar.endDate) }}
                                    <span v-if="row.departmentCalendar.notes" class="mt-0.5 block text-xs text-muted-foreground">
                                        {{ row.departmentCalendar.notes }}
                                    </span>
                                </template>
                                <span v-else class="text-muted-foreground">
                                    {{ $t('academic_calendar.department_assessment_calendar_using_global') }}
                                </span>
                            </td>
                            <td class="px-3 py-2.5">
                                <span
                                    :class="windowStatusBadgeClass(row.status)"
                                    class="inline-flex rounded-full px-2 py-0.5 text-xs font-medium"
                                >
                                    {{ row.statusLabel }}
                                </span>
                                <span class="mt-0.5 block text-xs text-muted-foreground">
                                    {{ formatIsoDateRange(row.effectiveStartDate, row.effectiveEndDate) }}
                                </span>
                            </td>
                            <td class="px-3 py-2.5">
                                <div class="flex flex-wrap justify-end gap-2">
                                    <template v-for="action in departmentCalendarActions(row, can)" :key="action">
                                        <BaseButton
                                            v-if="action === 'set' || action === 'edit'"
                                            type="button"
                                            :variant="ColorVariant.primary"
                                            :size="ButtonSize.xs"
                                            classes="rounded-full"
                                            @click="openEditor(row)"
                                        >
                                            {{
                                                $t(
                                                    action === 'edit'
                                                        ? 'academic_calendar.department_assessment_calendar_edit_dates'
                                                        : 'academic_calendar.department_assessment_calendar_set_dates',
                                                )
                                            }}
                                            <span class="sr-only">: {{ row.assessmentTypeName }}</span>
                                        </BaseButton>
                                        <BaseButton
                                            v-else-if="action === 'revert'"
                                            type="button"
                                            :variant="ColorVariant.primary_outline"
                                            :size="ButtonSize.xs"
                                            classes="rounded-full"
                                            @click="revert(row)"
                                        >
                                            {{ $t('academic_calendar.department_assessment_calendar_revert') }}
                                            <span class="sr-only">: {{ row.assessmentTypeName }}</span>
                                        </BaseButton>
                                        <BaseButton
                                            v-else-if="action === 'restore'"
                                            type="button"
                                            :variant="ColorVariant.primary_outline"
                                            :size="ButtonSize.xs"
                                            classes="rounded-full"
                                            @click="restore(row)"
                                        >
                                            {{ $t('academic_calendar.department_assessment_calendar_restore') }}
                                            <span class="sr-only">: {{ row.assessmentTypeName }}</span>
                                        </BaseButton>
                                    </template>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <CreateEdit :department-id="department.id!" />
    </PageContainer>
</template>
