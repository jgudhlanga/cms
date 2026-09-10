<script setup lang="ts">
import PageContainer from '@/components/core/page/PageContainer.vue';
import BaseAlert from '@/components/core/alert/BaseAlert.vue';
import { BaseButton } from '@/components/core/button';
import { BaseCheckbox } from '@/components/core/form';
import Empty from '@/components/core/util/Empty.vue';
import { useEnrolmentProgressionImport } from '@/composables/academicCalendars/useEnrolmentProgressionImport';
import { ButtonSize } from '@/enums/buttons';
import { ColorVariant } from '@/enums/colors';
import { TypeVariant } from '@/enums/type-variants';
import type { AcademicCalendar, ClassConfig } from '@/types/academic-calendar';
import type { DepartmentCourse, DepartmentLevel } from '@/types/department-meta-data';
import type { InstitutionDepartment, ModeOfStudy } from '@/types/institution';
import type { Link } from '@/types/ui';
import { Head } from '@inertiajs/vue3';
import { computed, toRefs } from 'vue';

const props = defineProps<{
    department: InstitutionDepartment;
    academicCalendar: AcademicCalendar;
    course: DepartmentCourse;
    level: DepartmentLevel;
    mode: ModeOfStudy;
    classConfig: ClassConfig;
    classConfigQuery: Record<string, string>;
    action: 'complete-level' | 'advance-phase';
    actionLabel: string;
    academicCalendarClassId?: number | null;
    academicCalendarClassName?: string | null;
    importResult?: { processed: number; skipped: number } | null;
}>();

const { department, academicCalendar, classConfigQuery, action, academicCalendarClassId } = toRefs(props);

const departmentClassesUrl = computed(() =>
    route('academic-calendars.department-classes', {
        institution_department: String(department.value.id),
        calendar_year: String(academicCalendar.value.attributes.calendarYear),
        ...classConfigQuery.value,
    }),
);

const classViewUrl = computed(() => {
    if (!academicCalendarClassId.value) {
        return null;
    }

    return route('academic-calendars.department-classes.show', {
        institution_department: String(department.value.id),
        calendar_year: String(academicCalendar.value.attributes.calendarYear),
        academic_calendar_class: String(academicCalendarClassId.value),
        ...classConfigQuery.value,
    });
});

const templateUrl = computed(() =>
    route('academic-calendars.department-classes.progression-import.template', {
        institution_department: String(department.value.id),
        calendar_year: String(academicCalendar.value.attributes.calendarYear),
        action: action.value,
        ...classConfigQuery.value,
    }),
);

const previewUrl = computed(() =>
    route('academic-calendars.department-classes.progression-import.preview', {
        institution_department: String(department.value.id),
        calendar_year: String(academicCalendar.value.attributes.calendarYear),
        action: action.value,
    }),
);

const processUrl = computed(() =>
    route('academic-calendars.department-classes.progression-import.process', {
        institution_department: String(department.value.id),
        calendar_year: String(academicCalendar.value.attributes.calendarYear),
        action: action.value,
    }),
);

const {
    fileError,
    previewLoading,
    previewError,
    processLoading,
    previewRows,
    previewSummaryLabel,
    canRunPreview,
    selectAllEligibleModel,
    selectedEligibleRows,
    onFileChange,
    runPreview,
    isRowSelected,
    setRowSelected,
    processSelected,
    cancelImport,
} = useEnrolmentProgressionImport({
    templateUrl: templateUrl.value,
    previewUrl: previewUrl.value,
    processUrl: processUrl.value,
    classConfigQuery: classConfigQuery.value,
    academicCalendarClassId: academicCalendarClassId.value,
});

const breadcrumbs = computed<Link[]>(() => {
    const crumbs: Link[] = [
        { transChoiceKey: 'institution', transChoiceKeyIndex: 1, href: route('institution.index') },
        {
            title: props.department.attributes?.name ?? '',
            href: route('institution-departments.show', String(props.department.id)),
        },
        {
            title: String(props.academicCalendar.attributes.calendarYear),
            href: departmentClassesUrl.value,
        },
    ];

    if (classViewUrl.value && props.academicCalendarClassName) {
        crumbs.push({
            title: props.academicCalendarClassName,
            href: classViewUrl.value,
        });
    }

    crumbs.push({
        title: props.actionLabel,
    });

    return crumbs;
});
</script>

<template>
    <Head :title="actionLabel" />
    <PageContainer :breadcrumbs="breadcrumbs">
        <div class="w-full min-w-0 space-y-4">
            <div>
                <h1 class="text-xl font-semibold">
                    {{ $t('academic_calendar.progression_import_title', { action: actionLabel }) }}
                </h1>
                <p class="mt-1 text-sm text-muted-foreground">
                    {{ $t('academic_calendar.progression_import_description') }}
                </p>
            </div>

            <BaseAlert
                v-if="importResult"
                :type="TypeVariant.success"
                :description="
                    $t('academic_calendar.progression_import_summary', {
                        total: String(importResult.processed + importResult.skipped),
                        eligible: String(importResult.processed),
                        skipped: String(importResult.skipped),
                    })
                "
            />

            <div
                class="flex flex-col gap-4 rounded-lg border border-border p-3 md:flex-row md:items-end md:justify-between"
            >
                <a :href="templateUrl" class="inline-flex shrink-0" target="_blank" rel="noopener noreferrer">
                    <BaseButton type="button" :variant="ColorVariant.primary_outline" :size="ButtonSize.sm">
                        {{ $t('academic_calendar.progression_import_download_template') }}
                    </BaseButton>
                </a>

                <div class="flex min-w-0 flex-1 flex-col gap-2 sm:flex-row sm:items-end">
                    <div class="min-w-0 flex-1">
                        <label class="mb-1 block text-xs font-medium text-muted-foreground" for="progression-import-file">
                            {{ $t('academic_calendar.progression_import_upload') }}
                        </label>
                        <input
                            id="progression-import-file"
                            type="file"
                            accept=".xlsx,.xls,.csv"
                            class="block w-full text-sm"
                            @change="onFileChange"
                        />
                        <p v-if="fileError" class="mt-1 text-xs text-destructive">{{ fileError }}</p>
                    </div>
                    <BaseButton
                        type="button"
                        :variant="ColorVariant.primary"
                        :size="ButtonSize.sm"
                        :disabled="!canRunPreview || previewLoading"
                        @click="runPreview"
                    >
                        {{ $t('academic_calendar.progression_import_preview') }}
                    </BaseButton>
                    <BaseButton
                        type="button"
                        :variant="ColorVariant.secondary"
                        :size="ButtonSize.sm"
                        @click="cancelImport"
                    >
                        {{ $t('trans.cancel') }}
                    </BaseButton>
                </div>
            </div>

            <BaseAlert v-if="previewError" :type="TypeVariant.danger" :description="previewError" />
            <BaseAlert v-else-if="previewSummaryLabel" :type="TypeVariant.info" :description="previewSummaryLabel" />

            <div v-if="previewRows.length > 0" class="overflow-x-auto rounded-lg border border-border">
                <table class="j-table w-full">
                    <thead class="j-thead">
                        <tr>
                            <th class="j-th w-10">
                                <BaseCheckbox
                                    v-model="selectAllEligibleModel"
                                    input-id="progression_import_select_all"
                                    :label="''"
                                />
                            </th>
                            <th class="j-th text-left">#</th>
                            <th class="j-th text-left">{{ $tChoice('trans.student_number', 1) }}</th>
                            <th class="j-th text-left">{{ $tChoice('trans.name', 1) }}</th>
                            <th class="j-th text-left">{{ $t('trans.status') }}</th>
                            <th class="j-th text-left">{{ $t('trans.phase') }}</th>
                            <th class="j-th text-left">{{ $t('trans.result') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="row in previewRows" :key="row.rowNumber" class="j-tr">
                            <td class="j-td">
                                <BaseCheckbox
                                    v-if="row.eligible"
                                    :model-value="isRowSelected(row.rowNumber)"
                                    :input-id="`progression_import_row_${row.rowNumber}`"
                                    :label="''"
                                    @update:model-value="(value: boolean) => setRowSelected(row.rowNumber, value)"
                                />
                            </td>
                            <td class="j-td">{{ row.rowNumber }}</td>
                            <td class="j-td">{{ row.studentNumber }}</td>
                            <td class="j-td">{{ row.studentName || '—' }}</td>
                            <td class="j-td">{{ row.status || '—' }}</td>
                            <td class="j-td">{{ row.phaseLabel || '—' }}</td>
                            <td class="j-td">
                                <span v-if="row.eligible" class="text-emerald-700 dark:text-emerald-400">
                                    {{ $t('academic_calendar.progression_import_eligible') }}
                                </span>
                                <span v-else class="text-muted-foreground">
                                    {{ row.skipReason || $t('academic_calendar.progression_import_skipped') }}
                                </span>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <Empty v-else-if="previewSummaryLabel" :message="$t('academic_calendar.progression_import_empty')" />

            <div v-if="previewRows.length > 0" class="flex justify-end">
                <BaseButton
                    type="button"
                    :variant="ColorVariant.success"
                    :size="ButtonSize.sm"
                    :disabled="selectedEligibleRows.length === 0 || processLoading"
                    @click="processSelected"
                >
                    {{ $t('academic_calendar.progression_import_process') }}
                    ({{ selectedEligibleRows.length }})
                </BaseButton>
            </div>
        </div>
    </PageContainer>
</template>
