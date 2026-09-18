import { errorAlert, successAlert, warningAlert } from '@/lib/alerts';
import customAxios from '@/services/http-init';
import type { StudyPositionState } from '@/types/study-position';
import { trans } from 'laravel-vue-i18n';
import { computed, ref, type Ref } from 'vue';

export type StudyPositionReconciliationRow = {
    enrolmentId: number;
    studentId: number;
    studentNumber: string | null;
    studentName: string | null;
    department: string | null;
    level: string | null;
    course: string | null;
    modeOfStudy: string | null;
    systemPhase: string | null;
    canConfirmOnRecord: boolean;
    answer: string | null;
    answerPhase: string | null;
    source: string | null;
    syncNote: string | null;
};

type ListResponse = {
    periodLabel: string | null;
    total: number;
    truncated: boolean;
    rows: StudyPositionReconciliationRow[];
};

type ConfirmResult = {
    summary: { requested: number; confirmed: number; skipped: number };
    rows: Array<{ enrolmentId: number; status: 'confirmed' | 'skipped'; reason?: string }>;
};

export const useStudyPositionReconciliation = (
    departmentId: string | number,
    state: Ref<StudyPositionState>,
    modeOfStudyId: Ref<number | null>,
    departmentLevelId: Ref<number | null>,
    departmentCourseId: Ref<number | null>,
) => {
    const loading = ref(false);
    const loadError = ref<string | null>(null);
    const rows = ref<StudyPositionReconciliationRow[]>([]);
    const periodLabel = ref<string | null>(null);
    const total = ref(0);
    const truncated = ref(false);

    const confirmLoading = ref(false);
    const selectedRowIds = ref<number[]>([]);
    const lastResult = ref<ConfirmResult | null>(null);

    const listUrl = computed(() =>
        route('department-data-reconciliation.study-position.list', { department: departmentId }),
    );
    const confirmUrl = computed(() =>
        route('department-data-reconciliation.study-position.confirm', { department: departmentId }),
    );

    const confirmableRows = computed(() => rows.value.filter((row) => row.canConfirmOnRecord));

    const selectAllModel = computed({
        get: (): boolean => {
            const selectable = confirmableRows.value;

            return selectable.length > 0 && selectable.every((row) => selectedRowIds.value.includes(row.enrolmentId));
        },
        set: (checked: boolean) => {
            selectedRowIds.value = checked ? confirmableRows.value.map((row) => row.enrolmentId) : [];
        },
    });

    const isRowSelected = (enrolmentId: number): boolean => selectedRowIds.value.includes(enrolmentId);

    const setRowSelected = (enrolmentId: number, checked: boolean): void => {
        if (checked) {
            if (!selectedRowIds.value.includes(enrolmentId)) {
                selectedRowIds.value = [...selectedRowIds.value, enrolmentId];
            }

            return;
        }

        selectedRowIds.value = selectedRowIds.value.filter((id) => id !== enrolmentId);
    };

    const clearSelection = (): void => {
        selectedRowIds.value = [];
    };

    const load = async (): Promise<void> => {
        loading.value = true;
        loadError.value = null;
        clearSelection();
        lastResult.value = null;

        try {
            const response = await customAxios('').get<ListResponse>(listUrl.value, {
                params: {
                    state: state.value,
                    mode_of_study_id: modeOfStudyId.value ?? undefined,
                    department_level_id: departmentLevelId.value ?? undefined,
                    department_course_id: departmentCourseId.value ?? undefined,
                },
            });

            rows.value = response.data.rows;
            periodLabel.value = response.data.periodLabel;
            total.value = response.data.total;
            truncated.value = response.data.truncated;
        } catch {
            loadError.value = trans('trans.department_reconciliation_import_preview_failed');
            errorAlert(loadError.value);
        } finally {
            loading.value = false;
        }
    };

    const submitConfirm = async (reason: string): Promise<boolean> => {
        if (selectedRowIds.value.length === 0 || confirmLoading.value) {
            return false;
        }

        confirmLoading.value = true;

        try {
            const response = await customAxios('').post<ConfirmResult>(confirmUrl.value, {
                enrolment_ids: selectedRowIds.value,
                reason,
            });

            lastResult.value = response.data;
            const { confirmed, skipped } = response.data.summary;
            const message = trans('students.study_position_department_confirm_result', {
                confirmed: String(confirmed),
                skipped: String(skipped),
            });

            if (confirmed === 0) {
                warningAlert(message);
            } else {
                successAlert(message);
            }

            await load();

            return true;
        } catch (caught) {
            const responseData = (caught as { response?: { data?: { message?: string } } }).response?.data;
            errorAlert(responseData?.message ?? trans('trans.department_semester_reconciliation_process_failed'));

            return false;
        } finally {
            confirmLoading.value = false;
        }
    };

    return {
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
        lastResult,
    };
};
