import { errorAlert, successAlert, warningAlert, warningDialog } from '@/lib/alerts';
import customAxios from '@/services/http-init';
import type {
    SemesterReconciliationPreview,
    SemesterReconciliationPreviewRow,
    SemesterReconciliationPreviewSummary,
    SemesterReconciliationProcessResult,
    SemesterReconciliationStatus,
} from '@/types/department-reconciliation';
import { trans } from 'laravel-vue-i18n';
import { computed, ref, type Ref } from 'vue';

// Legacy .xls is not readable by the server-side parser; offering it only produced a 500.
const ACCEPTED_EXTENSIONS = ['.xlsx', '.csv', '.ods'];

const buildSummaryFromRows = (
    rows: SemesterReconciliationPreviewRow[],
    extrasCount: number,
): SemesterReconciliationPreviewSummary => {
    const summary: SemesterReconciliationPreviewSummary = {
        total: rows.length,
        matched: 0,
        mismatch: 0,
        notEnrolled: 0,
        wrongPlace: 0,
        invalid: 0,
        selectable: 0,
        extras: extrasCount,
    };

    for (const row of rows) {
        switch (row.status) {
            case 'matched':
                summary.matched++;
                break;
            case 'mismatch':
                summary.mismatch++;
                break;
            case 'not_enrolled':
                summary.notEnrolled++;
                break;
            case 'wrong_place':
                summary.wrongPlace++;
                break;
            default:
                summary.invalid++;
                break;
        }

        if (row.isSelectable) {
            summary.selectable++;
        }
    }

    return summary;
};

export const useSemesterReconciliationImport = (
    departmentId: string | number,
    calendarYear: Ref<number>,
    modeOfStudyId: Ref<number | null>,
    departmentLevelId: Ref<number | null>,
    departmentCourseId: Ref<number | null>,
) => {
    const selectedFile = ref<File | null>(null);
    const fileError = ref<string | null>(null);
    const previewLoading = ref(false);
    const preview = ref<SemesterReconciliationPreview | null>(null);
    const previewError = ref<string | null>(null);
    const processLoading = ref(false);
    const processError = ref<string | null>(null);
    const processResult = ref<SemesterReconciliationProcessResult | null>(null);

    const templateUrl = computed(() =>
        route('department-data-reconciliation.semester-reconciliation.template', {
            department: departmentId,
        }),
    );
    const previewUrl = computed(() =>
        route('department-data-reconciliation.semester-reconciliation.preview', {
            department: departmentId,
        }),
    );
    const processUrl = computed(() =>
        route('department-data-reconciliation.semester-reconciliation.process', {
            department: departmentId,
        }),
    );

    const previewRows = computed(() => preview.value?.rows ?? []);
    const extrasRows = computed(() => preview.value?.extras ?? []);

    const previewSummaryLabel = computed((): string | null => {
        if (!preview.value) {
            return null;
        }

        const {
            total,
            matched,
            mismatch,
            notEnrolled,
            wrongPlace,
            invalid,
            selectable,
            extras,
        } = preview.value.summary;

        return trans('trans.department_semester_reconciliation_preview_summary', {
            total: String(total),
            matched: String(matched),
            mismatch: String(mismatch),
            notEnrolled: String(notEnrolled),
            wrongPlace: String(wrongPlace),
            invalid: String(invalid),
            selectable: String(selectable),
            extras: String(extras),
        });
    });

    const canRunPreview = computed((): boolean => {
        return selectedFile.value !== null
            && fileError.value === null
            && !previewLoading.value
            && !processLoading.value;
    });

    const isAcceptedFile = (file: File): boolean => {
        const name = file.name.toLowerCase();

        return ACCEPTED_EXTENSIONS.some((extension) => name.endsWith(extension));
    };

    const resetPreviewState = (): void => {
        preview.value = null;
        previewError.value = null;
        processResult.value = null;
        processError.value = null;
    };

    const cancelImport = (): void => {
        selectedFile.value = null;
        fileError.value = null;
        previewError.value = null;
        processError.value = null;
        processResult.value = null;
        resetPreviewState();
    };

    const onFileChange = (event: Event, fileInput: HTMLInputElement | null): void => {
        const target = event.target as HTMLInputElement;
        const file = target.files?.[0] ?? null;

        selectedFile.value = file;
        fileError.value = null;
        resetPreviewState();

        if (file !== null && !isAcceptedFile(file)) {
            fileError.value = trans('trans.department_reconciliation_import_invalid_file_type');
            selectedFile.value = null;

            if (fileInput) {
                fileInput.value = '';
            }
        }
    };

    const runPreview = async (): Promise<void> => {
        if (!selectedFile.value || fileError.value) {
            return;
        }

        previewLoading.value = true;
        previewError.value = null;
        processResult.value = null;
        processError.value = null;
        preview.value = null;

        const formData = new FormData();
        formData.append('file', selectedFile.value);
        formData.append('calendar_year', String(calendarYear.value));

        if (modeOfStudyId.value) {
            formData.append('mode_of_study_id', String(modeOfStudyId.value));
        }

        if (departmentLevelId.value) {
            formData.append('department_level_id', String(departmentLevelId.value));
        }

        if (departmentCourseId.value) {
            formData.append('department_course_id', String(departmentCourseId.value));
        }

        try {
            const response = await customAxios('').post<SemesterReconciliationPreview>(previewUrl.value, formData, {
                headers: { 'Content-Type': 'multipart/form-data' },
            });

            preview.value = response.data;
        } catch (caught) {
            const responseData = (caught as {
                response?: { data?: { message?: string; errors?: Record<string, string[]> } };
            }).response?.data;

            const message =
                responseData?.errors?.file?.[0]
                ?? responseData?.message
                ?? trans('trans.department_reconciliation_import_preview_failed');

            previewError.value = message;
            errorAlert(message);
        } finally {
            previewLoading.value = false;
        }
    };

    const removePreviewRow = (rowNumber: number): void => {
        if (!preview.value) {
            return;
        }

        const rows = preview.value.rows.filter((row) => row.rowNumber !== rowNumber);

        preview.value = {
            rows,
            extras: preview.value.extras,
            summary: buildSummaryFromRows(rows, preview.value.extras.length),
        };
    };

    const checkboxSkipTitle = (row: SemesterReconciliationPreviewRow): string | undefined => {
        return row.skipReasons[0];
    };

    const submitRectify = async (rows: SemesterReconciliationPreviewRow[]): Promise<boolean> => {
        if (rows.length === 0 || processLoading.value) {
            return false;
        }

        processLoading.value = true;
        processError.value = null;
        processResult.value = null;

        const payload = {
            rows: rows.map((row) => ({
                rowNumber: row.rowNumber,
                studentEnrolmentId: row.studentEnrolmentId as number,
                programmeSemesterId: row.programmeSemesterId as number,
            })),
        };

        try {
            const response = await customAxios('').post<SemesterReconciliationProcessResult>(processUrl.value, payload);
            processResult.value = response.data;

            const message = trans('trans.department_semester_reconciliation_process_success', {
                moved: String(response.data.summary.moved),
                skipped: String(response.data.summary.skipped),
            });

            // A run where nothing moved is not a success, however green the toast looks.
            if (response.data.summary.moved === 0) {
                warningAlert(message);
            } else {
                successAlert(message);
            }

            await runPreview();

            return true;
        } catch (caught) {
            const responseData = (caught as {
                response?: { data?: { message?: string; errors?: Record<string, string[]> } };
            }).response?.data;

            const message =
                responseData?.message
                ?? trans('trans.department_semester_reconciliation_process_failed');

            processError.value = message;
            errorAlert(message);

            return false;
        } finally {
            processLoading.value = false;
        }
    };

    const confirmRectify = (rows: SemesterReconciliationPreviewRow[], onSuccess?: () => void): void => {
        if (rows.length === 0) {
            return;
        }

        warningDialog(
            () => {
                void submitRectify(rows).then((succeeded) => {
                    if (succeeded) {
                        onSuccess?.();
                    }
                });

                return true;
            },
            trans('trans.department_semester_reconciliation_rectify_confirm', {
                count: String(rows.length),
            }),
            trans('trans.warning'),
            trans('trans.department_semester_reconciliation_rectify', {
                count: String(rows.length),
            }),
        );
    };

    const statusLabel = (status: SemesterReconciliationStatus): string => {
        const keys: Record<SemesterReconciliationStatus, string> = {
            matched: 'trans.department_semester_reconciliation_status_matched',
            mismatch: 'trans.department_semester_reconciliation_status_mismatch',
            not_enrolled: 'trans.department_semester_reconciliation_status_not_enrolled',
            wrong_place: 'trans.department_semester_reconciliation_status_wrong_place',
            invalid: 'trans.department_semester_reconciliation_status_invalid',
        };

        return trans(keys[status]);
    };

    const statusClass = (status: SemesterReconciliationStatus): string => {
        switch (status) {
            case 'matched':
                return 'text-green-700';
            case 'mismatch':
                return 'text-sky-700';
            case 'wrong_place':
                return 'text-amber-700';
            case 'invalid':
                return 'text-destructive';
            default:
                return 'text-muted-foreground';
        }
    };

    /**
     * The server already returns a reason for every skipped row; without this the operator only
     * ever saw an aggregate count and had no way to tell why rows were rejected.
     */
    const processSkipReasons = computed<Array<{ reason: string; rowNumbers: number[] }>>(() => {
        const grouped = new Map<string, number[]>();

        for (const row of processResult.value?.rows ?? []) {
            if (row.status !== 'skipped') {
                continue;
            }

            const reason = row.reason ?? trans('trans.department_semester_reconciliation_process_row_failed');
            const existing = grouped.get(reason);

            if (existing) {
                existing.push(row.rowNumber);
            } else {
                grouped.set(reason, [row.rowNumber]);
            }
        }

        return [...grouped.entries()].map(([reason, rowNumbers]) => ({ reason, rowNumbers }));
    });

    return {
        selectedFile,
        fileError,
        previewLoading,
        preview,
        previewError,
        processLoading,
        processError,
        processResult,
        processSkipReasons,
        templateUrl,
        previewRows,
        extrasRows,
        previewSummaryLabel,
        canRunPreview,
        cancelImport,
        onFileChange,
        runPreview,
        removePreviewRow,
        checkboxSkipTitle,
        confirmRectify,
        statusLabel,
        statusClass,
    };
};
