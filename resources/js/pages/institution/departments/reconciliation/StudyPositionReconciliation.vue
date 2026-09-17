<script setup lang="ts">
import BaseAlert from '@/components/core/alert/BaseAlert.vue';
import { BaseButton } from '@/components/core/button';
import { BaseCheckbox } from '@/components/core/form';
import DepartmentCourseComboSelect from '@/components/core/form/combobox/DepartmentCourseComboSelect.vue';
import DepartmentLevelComboSelect from '@/components/core/form/combobox/DepartmentLevelComboSelect.vue';
import ModeOfStudyComboSelect from '@/components/core/form/combobox/ModeOfStudyComboSelect.vue';
import BaseInput from '@/components/core/form/text/BaseInput.vue';
import BaseModal from '@/components/core/modal/BaseModal.vue';
import PageContainer from '@/components/core/page/PageContainer.vue';
import Empty from '@/components/core/util/Empty.vue';
import { useStudyPositionReconciliation } from '@/composables/institution/useStudyPositionReconciliation';
import { ButtonSize } from '@/enums/buttons';
import { ColorVariant } from '@/enums/colors';
import { TextFieldType } from '@/enums/inputs';
import { SizeVariant } from '@/enums/sizes';
import { TypeVariant } from '@/enums/type-variants';
import { APP_MODULE_KEYS } from '@/lib/constants';
import { useModalStore } from '@/store/core/useModalStore';
import type { StudyPositionState } from '@/types/study-position';
import type { InstitutionDepartment } from '@/types/institution';
import type { BreadcrumbItemInterface } from '@/types/ui';
import type { SelectOption } from '@/types/utils';
import { Head, useForm } from '@inertiajs/vue3';
import { trans } from 'laravel-vue-i18n';
import { onMounted, ref, watch } from 'vue';

const props = defineProps<{
    department: InstitutionDepartment;
    calendarYear: number;
    modeOfStudyId: number | null;
    initialState: StudyPositionState;
}>();

const departmentId = String(props.department.id ?? '');
const departmentTitle = props.department.attributes?.department ?? '';

const state = ref<StudyPositionState>(props.initialState);
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

const {
    loading,
    loadError,
    rows,
    confirmableRows,
    periodLabel,
    total,
    truncated,
    confirmLoading,
    selectedRowIds,
    selectAllModel,
    isRowSelected,
    setRowSelected,
    clearSelection,
    load,
    submitConfirm,
} = useStudyPositionReconciliation(departmentId, state, modeOfStudyId, departmentLevelId, departmentCourseId);

onMounted(() => void load());
watch([state, modeOfStudyId, departmentLevelId, departmentCourseId], () => void load());

const stateTabs: Array<{ value: StudyPositionState; label: () => string }> = [
    { value: 'unconfirmed', label: () => trans('students.study_position_state_unconfirmed') },
    { value: 'follow_up', label: () => trans('students.study_position_state_follow_up') },
    { value: 'needs_review', label: () => trans('students.study_position_state_needs_review') },
];

const { openModal, closeModal } = useModalStore();
const MODAL_KEY = APP_MODULE_KEYS.department_study_position_bulk_confirm;
const reason = ref('');
const reasonError = ref<string | null>(null);

const openConfirmModal = (): void => {
    reason.value = '';
    reasonError.value = null;
    openModal(MODAL_KEY);
};

const handleConfirm = async (): Promise<void> => {
    if (reason.value.trim().length < 10) {
        reasonError.value = trans('students.study_position_reason_placeholder');

        return;
    }

    const succeeded = await submitConfirm(reason.value.trim());

    if (succeeded) {
        closeModal(MODAL_KEY);
        clearSelection();
    }
};

const onSelectAllChange = (value: boolean): void => {
    selectAllModel.value = value;
};

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
            academic_year: props.calendarYear,
        }),
    },
    { transKey: 'students.study_position_department_title_bare' },
];

const backUrl = route('institution-departments.show', {
    department: departmentId,
    tab: 'data_reconciliation',
    academic_year: props.calendarYear,
});

const rowNoteClass = (row: { answer: string | null; syncNote: string | null }): string =>
    row.syncNote ? 'text-amber-700 dark:text-amber-400' : 'text-muted-foreground';
</script>

<template>
    <Head :title="$t('students.study_position_department_title_bare')" />

    <PageContainer :breadcrumbs="breadcrumbs" :back-url="backUrl">
        <template #backNavigationLeading>
            <div>
                <h2 class="text-lg font-semibold">{{ $t('students.study_position_department_title_bare') }}</h2>
                <p class="text-sm text-muted-foreground">{{ departmentTitle }}</p>
            </div>
        </template>

        <div class="w-full min-w-0 space-y-4">
            <BaseAlert
                :type="TypeVariant.info"
                :description="$t('students.study_position_department_page_description')"
            />

            <div class="flex flex-wrap items-center gap-1.5 border-b border-border pb-2">
                <button
                    v-for="tab in stateTabs"
                    :key="tab.value"
                    type="button"
                    class="rounded-full px-3 py-1.5 text-sm font-medium transition-colors"
                    :class="
                        state === tab.value
                            ? 'bg-primary text-primary-foreground'
                            : 'text-muted-foreground hover:bg-muted'
                    "
                    @click="state = tab.value"
                >
                    {{ tab.label() }}
                </button>
            </div>

            <div class="grid gap-3 sm:grid-cols-3">
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

            <div class="flex flex-wrap items-center justify-between gap-2">
                <p class="text-sm text-muted-foreground">
                    {{ $t('students.study_position_department_list_summary', { period: periodLabel ?? '', total: String(total) }) }}
                </p>

                <div v-if="selectedRowIds.length > 0" class="flex items-center gap-2">
                    <BaseButton
                        type="button"
                        :variant="ColorVariant.primary"
                        :size="ButtonSize.sm"
                        :processing="confirmLoading"
                        :disabled="confirmLoading"
                        @click="openConfirmModal"
                    >
                        {{ $t('students.study_position_department_confirm_action', { count: String(selectedRowIds.length) }) }}
                    </BaseButton>
                    <BaseButton
                        type="button"
                        :variant="ColorVariant.shade_outline"
                        :size="ButtonSize.sm"
                        :disabled="confirmLoading"
                        @click="clearSelection"
                    >
                        {{ $t('trans.clear_selection') }}
                    </BaseButton>
                </div>
            </div>

            <p v-if="truncated" class="text-sm text-amber-700 dark:text-amber-400">
                {{ $t('students.study_position_department_list_truncated', { shown: String(rows.length), total: String(total) }) }}
            </p>
            <p v-if="loadError" class="text-sm text-destructive">{{ loadError }}</p>

            <Empty
                v-if="!loading && rows.length === 0"
                :description="$t('students.study_position_department_list_empty')"
            />

            <div v-else class="overflow-x-auto rounded-md border border-border">
                <table class="min-w-full text-sm">
                    <thead>
                        <tr class="border-b bg-muted/30 text-left text-xs uppercase text-muted-foreground">
                            <th class="px-3 py-2">
                                <BaseCheckbox
                                    input-id="study-position-select-all"
                                    label=""
                                    :model-value="selectAllModel"
                                    :disabled="confirmableRows.length === 0 || confirmLoading || loading"
                                    @update:model-value="onSelectAllChange"
                                />
                            </th>
                            <th class="px-3 py-2">{{ $t('trans.maintenance_sponsored_students_import_column_student_number') }}</th>
                            <th class="px-3 py-2">{{ $tChoice('trans.name', 1) }}</th>
                            <th class="px-3 py-2">{{ $tChoice('trans.level', 1) }}</th>
                            <th class="px-3 py-2">{{ $tChoice('trans.course', 1) }}</th>
                            <th class="px-3 py-2">{{ $tChoice('trans.mode_of_study', 1) }}</th>
                            <th class="px-3 py-2">{{ $t('students.study_position_modal_on_record') }}</th>
                            <th class="px-3 py-2">{{ $t('students.study_position_department_column_answer') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="row in rows" :key="row.enrolmentId" class="border-b align-top">
                            <td class="px-3 py-2">
                                <BaseCheckbox
                                    :input-id="`study-position-row-${row.enrolmentId}`"
                                    label=""
                                    :model-value="isRowSelected(row.enrolmentId)"
                                    :disabled="!row.canConfirmOnRecord || confirmLoading"
                                    :title="!row.canConfirmOnRecord ? $t('students.study_position_records_missing') : undefined"
                                    @update:model-value="(value: boolean) => setRowSelected(row.enrolmentId, value)"
                                />
                            </td>
                            <td class="px-3 py-2 font-mono">{{ row.studentNumber ?? '—' }}</td>
                            <td class="px-3 py-2">
                                <a
                                    :href="route('students.show', row.studentId)"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    class="text-primary underline-offset-2 hover:underline"
                                >
                                    {{ row.studentName ?? '—' }}
                                </a>
                            </td>
                            <td class="px-3 py-2">{{ row.level ?? '—' }}</td>
                            <td class="px-3 py-2">{{ row.course ?? '—' }}</td>
                            <td class="px-3 py-2">{{ row.modeOfStudy ?? '—' }}</td>
                            <td class="px-3 py-2">{{ row.systemPhase ?? '—' }}</td>
                            <td class="px-3 py-2">
                                <span v-if="row.answer">{{ row.answerPhase ?? row.answer }}</span>
                                <span v-else class="text-muted-foreground">—</span>
                                <p v-if="row.syncNote" :class="['mt-0.5 text-xs', rowNoteClass(row)]">{{ row.syncNote }}</p>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <BaseModal
            :name="MODAL_KEY"
            :title="$t('students.study_position_department_confirm_action', { count: String(selectedRowIds.length) })"
            :size="SizeVariant.sm"
            cancel-btn-text="trans.cancel"
            :on-form-action="handleConfirm"
            :show-action-button="false"
        >
            <template #body>
                <div class="grid grid-cols-1 gap-3">
                    <p class="text-sm text-muted-foreground">
                        {{ $t('students.study_position_department_confirm_help', { count: String(selectedRowIds.length) }) }}
                    </p>
                    <BaseInput
                        input-id="department_study_position_bulk_reason"
                        v-model="reason"
                        :type="TextFieldType.textarea"
                        rows="3"
                        :label="$t('students.study_position_reason_label')"
                        :placeholder="$t('students.study_position_reason_placeholder')"
                        :is-required="true"
                        :error="reasonError ?? undefined"
                        @input="reasonError = null"
                    />
                </div>
            </template>
            <template #action-button>
                <!-- No explicit type: defaults to submit, so this triggers the form's @submit -> handleConfirm. -->
                <BaseButton
                    :variant="ColorVariant.primary"
                    :processing="confirmLoading"
                    :disabled="confirmLoading"
                    :size="ButtonSize.lg"
                >
                    {{ $t('trans.save') }}
                </BaseButton>
            </template>
        </BaseModal>
    </PageContainer>
</template>
