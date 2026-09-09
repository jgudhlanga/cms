<script setup lang="ts">
import BaseAlert from '@/components/core/alert/BaseAlert.vue';
import { BaseButton, IconButton } from '@/components/core/button';
import { BaseCheckbox } from '@/components/core/form';
import DepartmentCourseComboSelect from '@/components/core/form/combobox/DepartmentCourseComboSelect.vue';
import DepartmentLevelComboSelect from '@/components/core/form/combobox/DepartmentLevelComboSelect.vue';
import ModeOfStudyComboSelect from '@/components/core/form/combobox/ModeOfStudyComboSelect.vue';
import BaseInput from '@/components/core/form/text/BaseInput.vue';
import PageContainer from '@/components/core/page/PageContainer.vue';
import Empty from '@/components/core/util/Empty.vue';
import { useReconciliationImportSelection } from '@/composables/institution/useReconciliationImportSelection';
import { useSemesterReconciliationImport } from '@/composables/institution/useSemesterReconciliationImport';
import { ButtonSize } from '@/enums/buttons';
import { ColorVariant } from '@/enums/colors';
import { TypeVariant } from '@/enums/type-variants';
import { IconName } from '@/lib/icons';
import type { InstitutionDepartment } from '@/types/institution';
import type { BreadcrumbItemInterface } from '@/types/ui';
import type { SelectOption } from '@/types/utils';
import { Head, useForm } from '@inertiajs/vue3';
import { trans } from 'laravel-vue-i18n';
import { computed, ref, watch } from 'vue';

const props = defineProps<{
    department: InstitutionDepartment;
    calendarYear: number;
    modeOfStudyId: number | null;
}>();

const departmentId = String(props.department.id ?? '');
const departmentTitle = props.department.attributes?.department ?? '';

const calendarYear = ref(props.calendarYear);
const modeOfStudyId = ref<number | null>(props.modeOfStudyId);
const departmentLevelId = ref<number | null>(null);
const departmentCourseId = ref<number | null>(null);

const filterForm = useForm({
    mode_of_study_id: props.modeOfStudyId,
    department_level_id: null as number | null,
    department_course_id: null as number | null,
});

const selectedMode = ref<SelectOption | null>(
    props.modeOfStudyId ? { value: props.modeOfStudyId, label: '' } : null,
);
const selectedLevel = ref<SelectOption | null>(null);
const selectedCourse = ref<SelectOption | null>(null);

watch(selectedMode, (next) => {
    modeOfStudyId.value = next?.value ? Number(next.value) : null;
});

watch(selectedLevel, (next) => {
    departmentLevelId.value = next?.value ? Number(next.value) : null;
    selectedCourse.value = null;
    departmentCourseId.value = null;
});

watch(selectedCourse, (next) => {
    departmentCourseId.value = next?.value ? Number(next.value) : null;
});

const fileInput = ref<HTMLInputElement | null>(null);
const fileFormKey = ref(0);

const {
    fileError,
    previewLoading,
    preview,
    previewError,
    processLoading,
    processError,
    templateUrl,
    previewRows,
    extrasRows,
    previewSummaryLabel,
    canRunPreview,
    cancelImport,
    onFileChange,
    runPreview,
    removePreviewRow,
    confirmRectify,
    checkboxSkipTitle,
    statusLabel,
    statusClass,
} = useSemesterReconciliationImport(
    departmentId,
    calendarYear,
    modeOfStudyId,
    departmentLevelId,
    departmentCourseId,
);

const {
    selectAllModel,
    selectedCount,
    selectedRows,
    isRowSelected,
    setRowSelected,
    clearSelection,
    pruneSelectionToVisibleRows,
} = useReconciliationImportSelection(previewRows);

watch(previewRows, () => {
    pruneSelectionToVisibleRows();
});

const rectifyButtonLabel = computed(() =>
    trans('trans.department_semester_reconciliation_rectify', {
        count: String(selectedCount.value),
    }),
);

const breadcrumbs: BreadcrumbItemInterface[] = [
    { transChoiceKey: 'institution', transChoiceKeyIndex: 1, href: route('institution.index') },
    {
        transChoiceKey: 'department',
        href: route('institution-departments.index', {
            is_academic: props.department.attributes?.isAcademic,
        }),
    },
    {
        title: departmentTitle,
        href: route('institution-departments.show', {
            department: departmentId,
            tab: 'data_reconciliation',
            academic_year: calendarYear.value,
            ...(modeOfStudyId.value ? { mode_of_study_id: modeOfStudyId.value } : {}),
        }),
    },
    { transKey: 'trans.department_semester_reconciliation' },
];

const backUrl = route('institution-departments.show', {
    department: departmentId,
    tab: 'data_reconciliation',
    academic_year: calendarYear.value,
    ...(modeOfStudyId.value ? { mode_of_study_id: modeOfStudyId.value } : {}),
});

const onSelectAllChange = (value: boolean): void => {
    selectAllModel.value = value;
};

const onRowSelectChange = (rowNumber: number, value: boolean): void => {
    setRowSelected(rowNumber, value);
};

const resetFileForm = (): void => {
    fileFormKey.value++;
    fileInput.value = null;
};

const handleCancel = (): void => {
    clearSelection();
    cancelImport();
    resetFileForm();
};

const handlePreview = (): void => {
    clearSelection();
    void runPreview();
};

const handleRectify = (): void => {
    confirmRectify(selectedRows.value, clearSelection);
};

const rowHighlightClass = (status: string): string => {
    if (status === 'wrong_place') {
        return 'bg-amber-50/80';
    }

    if (status === 'mismatch') {
        return 'bg-sky-50/60';
    }

    return '';
};
</script>

<template>
    <Head :title="$t('trans.department_semester_reconciliation')" />

    <PageContainer :breadcrumbs="breadcrumbs" :back-url="backUrl">
        <template #backNavigationLeading>
            <div>
                <h2 class="text-lg font-semibold">{{ $t('trans.department_semester_reconciliation') }}</h2>
                <p class="text-sm text-muted-foreground">{{ departmentTitle }}</p>
            </div>
        </template>

        <div class="w-full min-w-0 space-y-4">
            <BaseAlert
                :type="TypeVariant.info"
                :description="$t('trans.department_semester_reconciliation_page_description')"
            />

            <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                <BaseInput
                    :model-value="String(calendarYear)"
                    input-id="semester-reconciliation-calendar-year"
                    name="calendar_year"
                    type="number"
                    :label="$t('trans.department_reconciliation_calendar_year')"
                    @update:model-value="(value: string | number) => (calendarYear = Number(value) || calendarYear)"
                />
                <ModeOfStudyComboSelect
                    v-model="selectedMode"
                    :form="filterForm"
                    :institution-department-id="departmentId"
                    :label="$tChoice('trans.mode_of_study', 1)"
                />
                <DepartmentLevelComboSelect
                    v-model="selectedLevel"
                    :form="filterForm"
                    :institution-department-id="departmentId"
                    :restrict-to-selected-level="false"
                    :label="$tChoice('trans.level', 1)"
                />
                <DepartmentCourseComboSelect
                    v-model="selectedCourse"
                    :form="filterForm"
                    :department-level-id="String(departmentLevelId ?? '')"
                />
            </div>

            <div
                class="flex flex-col gap-4 rounded-lg border border-border p-3 md:flex-row md:items-end md:justify-between"
            >
                <a
                    :href="templateUrl"
                    class="inline-flex shrink-0"
                    target="_blank"
                    rel="noopener noreferrer"
                >
                    <BaseButton
                        type="button"
                        :variant="ColorVariant.primary_outline"
                        :size="ButtonSize.sm"
                    >
                        {{ $t('trans.department_reconciliation_download_template') }}
                    </BaseButton>
                </a>

                <div :key="fileFormKey" class="min-w-0 flex-1 space-y-2 md:max-w-xl">
                    <label class="text-xs font-bold uppercase text-muted-foreground" for="semester-reconciliation-file">
                        {{ $t('trans.department_reconciliation_select_file') }}
                    </label>
                    <div class="flex flex-col gap-2 sm:flex-row sm:items-center">
                        <input
                            id="semester-reconciliation-file"
                            ref="fileInput"
                            type="file"
                            accept=".xlsx,.xls,.csv,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet,application/vnd.ms-excel,text/csv"
                            class="block min-w-0 flex-1 text-sm text-muted-foreground file:mr-4 file:rounded-md file:border-0 file:bg-secondary file:px-4 file:py-2 file:text-sm file:font-medium"
                            :disabled="processLoading"
                            @change="onFileChange($event, fileInput)"
                        />
                        <BaseButton
                            type="button"
                            :variant="ColorVariant.primary_outline"
                            :size="ButtonSize.sm"
                            class="shrink-0"
                            :processing="previewLoading"
                            :disabled="!canRunPreview"
                            @click="handlePreview"
                        >
                            {{ $t('trans.department_reconciliation_preview') }}
                        </BaseButton>
                    </div>
                    <p v-if="fileError" class="text-sm text-destructive">{{ fileError }}</p>
                    <p v-if="previewError" class="text-sm text-destructive">{{ previewError }}</p>
                    <p v-if="processError" class="text-sm text-destructive">{{ processError }}</p>
                </div>
            </div>

            <div v-if="preview" class="space-y-4 rounded-lg border border-border p-4">
                <div class="flex flex-wrap items-center justify-between gap-2">
                    <div class="space-y-1">
                        <h3 class="text-sm font-semibold">{{ $t('trans.department_reconciliation_preview') }}</h3>
                        <p v-if="previewSummaryLabel" class="text-sm text-muted-foreground">
                            {{ previewSummaryLabel }}
                        </p>
                    </div>
                    <div class="flex flex-wrap items-center gap-2">
                        <template v-if="selectedCount > 0">
                            <BaseButton
                                type="button"
                                :variant="ColorVariant.primary"
                                :size="ButtonSize.sm"
                                :processing="processLoading"
                                :disabled="processLoading"
                                @click="handleRectify"
                            >
                                {{ rectifyButtonLabel }}
                            </BaseButton>
                            <BaseButton
                                type="button"
                                :variant="ColorVariant.secondary"
                                :size="ButtonSize.sm"
                                :disabled="processLoading"
                                :title="trans('trans.clear_selection')"
                                @click="clearSelection"
                            />
                        </template>
                        <BaseButton
                            type="button"
                            :variant="ColorVariant.shade_outline"
                            :size="ButtonSize.sm"
                            :disabled="processLoading"
                            @click="handleCancel"
                        >
                            {{ $t('trans.cancel') }}
                        </BaseButton>
                    </div>
                </div>

                <Empty
                    v-if="previewRows.length === 0"
                    :description="$t('trans.department_reconciliation_no_rows')"
                />

                <div v-else class="overflow-x-auto rounded-md border border-border">
                    <table class="min-w-full text-sm">
                        <thead>
                            <tr class="border-b bg-muted/30 text-left text-xs uppercase text-muted-foreground">
                                <th class="px-3 py-2">
                                    <BaseCheckbox
                                        input-id="semester-reconciliation-select-all"
                                        label=""
                                        :model-value="selectAllModel"
                                        :disabled="previewRows.every((row) => !row.isSelectable) || processLoading"
                                        @update:model-value="onSelectAllChange"
                                    />
                                </th>
                                <th class="px-3 py-2">{{ $t('trans.maintenance_sponsored_students_import_column_student_number') }}</th>
                                <th class="px-3 py-2">{{ $tChoice('trans.name', 1) }}</th>
                                <th class="px-3 py-2">{{ $t('trans.department_reconciliation_column_file_level') }}</th>
                                <th class="px-3 py-2">{{ $t('trans.department_reconciliation_column_file_course') }}</th>
                                <th class="px-3 py-2">{{ $t('trans.department_reconciliation_column_file_phase') }}</th>
                                <th class="px-3 py-2">{{ $t('trans.department_reconciliation_column_system_phase') }}</th>
                                <th class="px-3 py-2">{{ $t('trans.department_reconciliation_column_system_level') }}</th>
                                <th class="px-3 py-2">{{ $t('trans.department_reconciliation_column_system_course') }}</th>
                                <th class="px-3 py-2">{{ $t('trans.maintenance_sponsored_students_import_column_match_status') }}</th>
                                <th class="px-3 py-2 w-12"></th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr
                                v-for="row in previewRows"
                                :key="row.rowNumber"
                                class="border-b align-top"
                                :class="[rowHighlightClass(row.status), { 'bg-muted/20': !row.isSelectable && row.status !== 'wrong_place' && row.status !== 'mismatch' }]"
                            >
                                <td class="px-3 py-2">
                                    <BaseCheckbox
                                        :input-id="`semester-reconciliation-row-${row.rowNumber}`"
                                        label=""
                                        :model-value="isRowSelected(row.rowNumber)"
                                        :disabled="!row.isSelectable || processLoading"
                                        :title="checkboxSkipTitle(row)"
                                        @update:model-value="(value: boolean) => onRowSelectChange(row.rowNumber, value)"
                                    />
                                </td>
                                <td class="px-3 py-2 font-mono">{{ row.studentNumber ?? '—' }}</td>
                                <td class="px-3 py-2">{{ row.studentName ?? '—' }}</td>
                                <td class="px-3 py-2">{{ row.fileLevel ?? '—' }}</td>
                                <td class="px-3 py-2">{{ row.fileCourse ?? '—' }}</td>
                                <td class="px-3 py-2">{{ row.filePhase ?? '—' }}</td>
                                <td class="px-3 py-2">{{ row.systemPhase ?? '—' }}</td>
                                <td class="px-3 py-2">{{ row.systemLevel ?? '—' }}</td>
                                <td class="px-3 py-2">{{ row.systemCourse ?? '—' }}</td>
                                <td class="px-3 py-2">
                                    <span :class="statusClass(row.status)">{{ statusLabel(row.status) }}</span>
                                    <p
                                        v-for="(error, index) in row.errors"
                                        :key="`${row.rowNumber}-error-${index}`"
                                        class="mt-1 text-xs text-muted-foreground"
                                    >
                                        {{ error }}
                                    </p>
                                    <p v-if="row.highlight" class="mt-1 text-xs font-medium text-amber-800">
                                        {{ row.highlight }}
                                    </p>
                                </td>
                                <td class="px-3 py-2">
                                    <IconButton
                                        :icon="IconName.trash"
                                        tone="header-danger"
                                        :aria-label="trans('trans.remove')"
                                        :disabled="processLoading"
                                        @click="removePreviewRow(row.rowNumber)"
                                    />
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div v-if="extrasRows.length > 0" class="space-y-2">
                    <h3 class="text-sm font-semibold">{{ $t('trans.department_reconciliation_extras_title') }}</h3>
                    <p class="text-xs text-muted-foreground">{{ $t('trans.department_reconciliation_extras_help') }}</p>
                    <div class="overflow-x-auto rounded-md border border-amber-200 bg-amber-50/40">
                        <table class="min-w-full text-sm">
                            <thead>
                                <tr class="border-b text-left text-xs uppercase text-muted-foreground">
                                    <th class="px-3 py-2">{{ $t('trans.maintenance_sponsored_students_import_column_student_number') }}</th>
                                    <th class="px-3 py-2">{{ $tChoice('trans.name', 1) }}</th>
                                    <th class="px-3 py-2">{{ $tChoice('trans.level', 1) }}</th>
                                    <th class="px-3 py-2">{{ $tChoice('trans.course', 1) }}</th>
                                    <th class="px-3 py-2">{{ $t('trans.department_reconciliation_column_system_phase') }}</th>
                                    <th class="px-3 py-2">{{ $t('trans.department_reconciliation_column_highlight') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="extra in extrasRows" :key="extra.studentEnrolmentId" class="border-b">
                                    <td class="px-3 py-2 font-mono">{{ extra.studentNumber }}</td>
                                    <td class="px-3 py-2">{{ extra.studentName ?? '—' }}</td>
                                    <td class="px-3 py-2">{{ extra.level ?? '—' }}</td>
                                    <td class="px-3 py-2">{{ extra.course ?? '—' }}</td>
                                    <td class="px-3 py-2">{{ extra.systemPhase ?? '—' }}</td>
                                    <td class="px-3 py-2 text-amber-800">{{ extra.highlight ?? '—' }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </PageContainer>
</template>
