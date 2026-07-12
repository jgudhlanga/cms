import { errorAlert, successAlert, warningDialog } from '@/lib/alerts';
import customAxios from '@/services/http-init';
import type {
    ApprenticeClassListStatus,
    ApprenticeImportPreview,
    ApprenticeImportPreviewRow,
    ApprenticeImportPreviewStatus,
    ApprenticeImportPreviewSummary,
    ApprenticeImportProcessResult,
    ApprenticeImportRefreshRowResponse,
} from '@/types/apprentice-import';
import { trans } from 'laravel-vue-i18n';
import { computed, ref } from 'vue';

const ACCEPTED_EXTENSIONS = ['.xlsx', '.xls', '.csv'];

const buildSummaryFromRows = (rows: ApprenticeImportPreviewRow[]): ApprenticeImportPreviewSummary => {
    const summary: ApprenticeImportPreviewSummary = {
        total: rows.length,
        found: 0,
        notFound: 0,
        invalid: 0,
        alreadyApprentice: 0,
        invalidId: 0,
        selectable: 0,
    };

    for (const row of rows) {
        if (row.status === 'found') {
            summary.found++;
        } else if (row.status === 'invalid') {
            summary.invalid++;
        } else {
            summary.notFound++;
        }

        if (row.isAlreadyApprentice) {
            summary.alreadyApprentice++;
        }

        if (row.studentId !== null && !row.idNumberValid) {
            summary.invalidId++;
        }

        if (row.isSelectable) {
            summary.selectable++;
        }
    }

    return summary;
};

export const useApprenticeImport = (calendarYear: number) => {
    const selectedDepartmentId = ref<number | null>(null);
    const selectedFile = ref<File | null>(null);
    const fileError = ref<string | null>(null);
    const previewLoading = ref(false);
    const preview = ref<ApprenticeImportPreview | null>(null);
    const previewError = ref<string | null>(null);
    const processLoading = ref(false);
    const processError = ref<string | null>(null);
    const processResult = ref<ApprenticeImportProcessResult | null>(null);
    const rowRefreshLoading = ref<Set<number>>(new Set());

    const templateUrl = route('maintenance.apprentice-management.template');
    const previewUrl = route('maintenance.apprentice-management.preview');
    const processUrl = route('maintenance.apprentice-management.process');
    const refreshRowUrl = route('maintenance.apprentice-management.refresh-row');

    const previewRows = computed(() => preview.value?.rows ?? []);

    const previewSummaryLabel = computed((): string | null => {
        if (!preview.value) {
            return null;
        }

        const { total, found, notFound, invalid, alreadyApprentice, invalidId, selectable } = preview.value.summary;

        return trans('trans.maintenance_apprentice_import_preview_summary', {
            total: String(total),
            found: String(found),
            notFound: String(notFound),
            invalid: String(invalid),
            alreadyApprentice: String(alreadyApprentice),
            invalidId: String(invalidId),
            selectable: String(selectable),
        });
    });

    const canRunPreview = computed((): boolean => {
        return selectedDepartmentId.value !== null
            && selectedFile.value !== null
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
            fileError.value = trans('trans.maintenance_apprentice_import_invalid_file_type');
            selectedFile.value = null;

            if (fileInput) {
                fileInput.value = '';
            }
        }
    };

    const runPreview = async (): Promise<void> => {
        if (!selectedFile.value || fileError.value || selectedDepartmentId.value === null) {
            return;
        }

        previewLoading.value = true;
        previewError.value = null;
        processResult.value = null;
        processError.value = null;
        preview.value = null;

        const formData = new FormData();
        formData.append('file', selectedFile.value);
        formData.append('institution_department_id', String(selectedDepartmentId.value));
        formData.append('calendar_year', String(calendarYear));

        try {
            const response = await customAxios('').post<ApprenticeImportPreview>(previewUrl, formData, {
                headers: { 'Content-Type': 'multipart/form-data' },
            });

            preview.value = response.data;
        } catch (caught) {
            const responseData = (caught as {
                response?: { data?: { message?: string; errors?: Record<string, string[]> } };
            }).response?.data;

            const message =
                responseData?.errors?.file?.[0]
                ?? responseData?.errors?.institution_department_id?.[0]
                ?? responseData?.message
                ?? trans('trans.maintenance_apprentice_import_preview_failed');

            previewError.value = message;
            errorAlert(message);
        } finally {
            previewLoading.value = false;
        }
    };

    const applyRefreshedRow = (updatedRow: ApprenticeImportPreviewRow): void => {
        if (!preview.value) {
            return;
        }

        const rows = preview.value.rows.map((row) =>
            row.rowNumber === updatedRow.rowNumber ? updatedRow : row,
        );

        preview.value = {
            rows,
            summary: buildSummaryFromRows(rows),
        };
    };

    const removePreviewRow = (rowNumber: number): void => {
        if (!preview.value) {
            return;
        }

        const rows = preview.value.rows.filter((row) => row.rowNumber !== rowNumber);

        preview.value = {
            rows,
            summary: buildSummaryFromRows(rows),
        };
    };

    const refreshPreviewRow = async (row: ApprenticeImportPreviewRow): Promise<boolean> => {
        if (selectedDepartmentId.value === null || rowRefreshLoading.value.has(row.rowNumber)) {
            return false;
        }

        rowRefreshLoading.value = new Set([...rowRefreshLoading.value, row.rowNumber]);

        const payload = {
            institution_department_id: selectedDepartmentId.value,
            calendar_year: calendarYear,
            rowNumber: row.rowNumber,
            idNumber: row.idNumber,
            studentNumber: row.studentNumber,
            apprenticeNumber: row.apprenticeNumber,
            employer: row.employer,
        };

        try {
            const response = await customAxios('').post<ApprenticeImportRefreshRowResponse>(refreshRowUrl, payload);
            applyRefreshedRow(response.data.row);

            return true;
        } catch (caught) {
            const responseData = (caught as {
                response?: { data?: { message?: string } };
            }).response?.data;

            errorAlert(responseData?.message ?? trans('trans.maintenance_apprentice_import_refresh_row_failed'));

            return false;
        } finally {
            const next = new Set(rowRefreshLoading.value);
            next.delete(row.rowNumber);
            rowRefreshLoading.value = next;
        }
    };

    const hasInvalidIdSkip = (row: ApprenticeImportPreviewRow): boolean => {
        return row.studentId !== null && !row.idNumberValid;
    };

    const nonInvalidIdSkipReasons = (row: ApprenticeImportPreviewRow): string[] => {
        const invalidIdLabel = trans('trans.maintenance_apprentice_import_skip_invalid_id');

        return row.skipReasons.filter((reason) => reason !== invalidIdLabel);
    };

    const checkboxSkipTitle = (row: ApprenticeImportPreviewRow): string | undefined => {
        const reasons = nonInvalidIdSkipReasons(row);

        return reasons[0];
    };

    const submitMoveToFinalClass = async (rows: ApprenticeImportPreviewRow[]): Promise<boolean> => {
        if (selectedDepartmentId.value === null || rows.length === 0 || processLoading.value) {
            return false;
        }

        processLoading.value = true;
        processError.value = null;
        processResult.value = null;

        const payload = {
            institution_department_id: selectedDepartmentId.value,
            calendar_year: calendarYear,
            rows: rows.map((row) => ({
                rowNumber: row.rowNumber,
                studentApplicationId: row.studentApplicationId as number,
                apprenticeNumber: row.apprenticeNumber,
                employer: row.employer,
            })),
        };

        try {
            const response = await customAxios('').post<ApprenticeImportProcessResult>(processUrl, payload);
            processResult.value = response.data;

            successAlert(
                trans('trans.maintenance_apprentice_import_process_success', {
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
                ?? trans('trans.maintenance_apprentice_import_process_failed');

            processError.value = message;
            errorAlert(message);

            return false;
        } finally {
            processLoading.value = false;
        }
    };

    const confirmMoveToFinalClass = (rows: ApprenticeImportPreviewRow[], onSuccess?: () => void): void => {
        if (rows.length === 0) {
            return;
        }

        warningDialog(
            () => {
                void submitMoveToFinalClass(rows).then((succeeded) => {
                    if (succeeded) {
                        onSuccess?.();
                    }
                });

                return true;
            },
            trans('trans.maintenance_apprentice_import_move_confirm_message', {
                count: String(rows.length),
            }),
            trans('trans.warning'),
            trans('trans.maintenance_apprentice_import_move_to_final_class', {
                count: String(rows.length),
            }),
        );
    };

    const statusLabel = (status: ApprenticeImportPreviewStatus): string => {
        const keys: Record<ApprenticeImportPreviewStatus, string> = {
            found: 'trans.maintenance_apprentice_import_status_found',
            not_found: 'trans.maintenance_apprentice_import_status_not_found',
            invalid: 'trans.maintenance_apprentice_import_status_invalid',
        };

        return trans(keys[status]);
    };

    const statusClass = (status: ApprenticeImportPreviewStatus): string => {
        if (status === 'found') {
            return 'text-green-700';
        }

        if (status === 'invalid') {
            return 'text-destructive';
        }

        return 'text-amber-700';
    };

    const classListStatusLabel = (status: ApprenticeClassListStatus | null): string => {
        if (status === null) {
            return '—';
        }

        const keys: Record<ApprenticeClassListStatus, string> = {
            provisional: 'trans.maintenance_apprentice_import_class_list_provisional',
            verified: 'trans.maintenance_apprentice_import_class_list_verified',
            waiting: 'trans.maintenance_apprentice_import_class_list_waiting',
            final: 'trans.maintenance_apprentice_import_class_list_final',
            failed: 'trans.maintenance_apprentice_import_class_list_failed',
        };

        return trans(keys[status]);
    };

    const classListStatusClass = (status: ApprenticeClassListStatus | null): string => {
        switch (status) {
            case 'final':
                return 'rounded-full bg-emerald-100 px-2 py-0.5 text-xs font-medium text-emerald-900';
            case 'verified':
                return 'rounded-full bg-sky-100 px-2 py-0.5 text-xs font-medium text-sky-900';
            case 'waiting':
                return 'rounded-full bg-amber-100 px-2 py-0.5 text-xs font-medium text-amber-900';
            case 'failed':
                return 'rounded-full bg-destructive/15 px-2 py-0.5 text-xs font-medium text-destructive';
            case 'provisional':
                return 'rounded-full bg-muted px-2 py-0.5 text-xs font-medium text-muted-foreground';
            default:
                return 'text-muted-foreground';
        }
    };

    return {
        selectedDepartmentId,
        selectedFile,
        fileError,
        previewLoading,
        preview,
        previewError,
        processLoading,
        processError,
        processResult,
        rowRefreshLoading,
        templateUrl,
        previewRows,
        previewSummaryLabel,
        canRunPreview,
        cancelImport,
        onFileChange,
        runPreview,
        refreshPreviewRow,
        applyRefreshedRow,
        removePreviewRow,
        hasInvalidIdSkip,
        nonInvalidIdSkipReasons,
        checkboxSkipTitle,
        submitMoveToFinalClass,
        confirmMoveToFinalClass,
        statusLabel,
        statusClass,
        classListStatusLabel,
        classListStatusClass,
    };
};
