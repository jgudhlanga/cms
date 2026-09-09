import { errorAlert, successAlert, warningDialog } from '@/lib/alerts';
import customAxios from '@/services/http-init';
import type {
    EnrolmentVsClassListPreview,
    EnrolmentVsClassListPreviewRow,
    EnrolmentVsClassListPreviewSummary,
    EnrolmentVsClassListProcessResult,
    EnrolmentVsClassListStatus,
} from '@/types/department-reconciliation';
import { trans } from 'laravel-vue-i18n';
import { computed, ref, type Ref } from 'vue';

const ACCEPTED_EXTENSIONS = ['.xlsx', '.xls', '.csv'];

const buildSummaryFromRows = (
    rows: EnrolmentVsClassListPreviewRow[],
    extrasCount: number,
): EnrolmentVsClassListPreviewSummary => {
    const summary: EnrolmentVsClassListPreviewSummary = {
        total: rows.length,
        matched: 0,
        elevate: 0,
        wrongPlace: 0,
        inAdmissions: 0,
        notFound: 0,
        invalid: 0,
        selectable: 0,
        extras: extrasCount,
    };

    for (const row of rows) {
        switch (row.status) {
            case 'matched':
                summary.matched++;
                break;
            case 'elevate':
                summary.elevate++;
                break;
            case 'wrong_place':
                summary.wrongPlace++;
                break;
            case 'in_admissions':
                summary.inAdmissions++;
                break;
            case 'not_found':
                summary.notFound++;
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

export const useEnrolmentVsClassListImport = (
    departmentId: string | number,
    calendarYear: Ref<number>,
    modeOfStudyId: Ref<number | null>,
    departmentLevelId: Ref<number | null>,
    departmentCourseId: Ref<number | null>,
) => {
    const selectedFile = ref<File | null>(null);
    const fileError = ref<string | null>(null);
    const previewLoading = ref(false);
    const preview = ref<EnrolmentVsClassListPreview | null>(null);
    const previewError = ref<string | null>(null);
    const processLoading = ref(false);
    const processError = ref<string | null>(null);
    const processResult = ref<EnrolmentVsClassListProcessResult | null>(null);

    const templateUrl = computed(() =>
        route('department-data-reconciliation.enrolment-vs-class-list.template', {
            department: departmentId,
        }),
    );
    const previewUrl = computed(() =>
        route('department-data-reconciliation.enrolment-vs-class-list.preview', {
            department: departmentId,
        }),
    );
    const processUrl = computed(() =>
        route('department-data-reconciliation.enrolment-vs-class-list.process', {
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
            elevate,
            wrongPlace,
            inAdmissions,
            notFound,
            invalid,
            selectable,
            extras,
        } = preview.value.summary;

        return trans('trans.department_enrolment_vs_class_list_preview_summary', {
            total: String(total),
            matched: String(matched),
            elevate: String(elevate),
            wrongPlace: String(wrongPlace),
            inAdmissions: String(inAdmissions),
            notFound: String(notFound),
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
            const response = await customAxios('').post<EnrolmentVsClassListPreview>(previewUrl.value, formData, {
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

    const checkboxSkipTitle = (row: EnrolmentVsClassListPreviewRow): string | undefined => {
        return row.skipReasons[0];
    };

    const submitElevate = async (rows: EnrolmentVsClassListPreviewRow[]): Promise<boolean> => {
        if (rows.length === 0 || processLoading.value) {
            return false;
        }

        processLoading.value = true;
        processError.value = null;
        processResult.value = null;

        const payload = {
            calendar_year: calendarYear.value,
            rows: rows.map((row) => ({
                rowNumber: row.rowNumber,
                studentApplicationId: row.studentApplicationId as number,
            })),
        };

        try {
            const response = await customAxios('').post<EnrolmentVsClassListProcessResult>(processUrl.value, payload);
            processResult.value = response.data;

            successAlert(
                trans('trans.department_enrolment_vs_class_list_process_success', {
                    moved: String(response.data.summary.moved),
                    skipped: String(response.data.summary.skipped),
                }),
            );

            await runPreview();

            return true;
        } catch (caught) {
            const responseData = (caught as {
                response?: { data?: { message?: string; errors?: Record<string, string[]> } };
            }).response?.data;

            const message =
                responseData?.message
                ?? trans('trans.department_enrolment_vs_class_list_process_failed');

            processError.value = message;
            errorAlert(message);

            return false;
        } finally {
            processLoading.value = false;
        }
    };

    const confirmElevate = (rows: EnrolmentVsClassListPreviewRow[], onSuccess?: () => void): void => {
        if (rows.length === 0) {
            return;
        }

        warningDialog(
            () => {
                void submitElevate(rows).then((succeeded) => {
                    if (succeeded) {
                        onSuccess?.();
                    }
                });

                return true;
            },
            trans('trans.department_enrolment_vs_class_list_elevate_confirm', {
                count: String(rows.length),
            }),
            trans('trans.warning'),
            trans('trans.department_enrolment_vs_class_list_elevate', {
                count: String(rows.length),
            }),
        );
    };

    const statusLabel = (status: EnrolmentVsClassListStatus): string => {
        const keys: Record<EnrolmentVsClassListStatus, string> = {
            matched: 'trans.department_enrolment_vs_class_list_status_matched',
            elevate: 'trans.department_enrolment_vs_class_list_status_elevate',
            wrong_place: 'trans.department_enrolment_vs_class_list_status_wrong_place',
            in_admissions: 'trans.department_enrolment_vs_class_list_status_in_admissions',
            not_found: 'trans.department_enrolment_vs_class_list_status_not_found',
            invalid: 'trans.department_enrolment_vs_class_list_status_invalid',
        };

        return trans(keys[status]);
    };

    const statusClass = (status: EnrolmentVsClassListStatus): string => {
        switch (status) {
            case 'matched':
                return 'text-green-700';
            case 'elevate':
                return 'text-sky-700';
            case 'wrong_place':
                return 'text-amber-700';
            case 'invalid':
                return 'text-destructive';
            default:
                return 'text-muted-foreground';
        }
    };

    return {
        selectedFile,
        fileError,
        previewLoading,
        preview,
        previewError,
        processLoading,
        processError,
        processResult,
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
        confirmElevate,
        statusLabel,
        statusClass,
    };
};
