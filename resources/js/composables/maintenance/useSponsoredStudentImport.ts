import { errorAlert, successAlert, warningDialog } from '@/lib/alerts';
import customAxios from '@/services/http-init';
import type {
    SponsoredStudentClassListStatus,
    SponsoredStudentImportPreview,
    SponsoredStudentImportPreviewRow,
    SponsoredStudentImportPreviewStatus,
    SponsoredStudentImportPreviewSummary,
    SponsoredStudentImportProcessResult,
} from '@/types/sponsored-student-import';
import { trans } from 'laravel-vue-i18n';
import { computed, ref } from 'vue';

const ACCEPTED_EXTENSIONS = ['.xlsx', '.xls', '.csv'];

const buildSummaryFromRows = (rows: SponsoredStudentImportPreviewRow[]): SponsoredStudentImportPreviewSummary => {
    const summary: SponsoredStudentImportPreviewSummary = {
        total: rows.length,
        found: 0,
        notFound: 0,
        invalid: 0,
        alreadySponsored: 0,
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

        if (row.isAlreadySponsored) {
            summary.alreadySponsored++;
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

export const useSponsoredStudentImport = (calendarYear: number) => {
    const selectedFile = ref<File | null>(null);
    const fileError = ref<string | null>(null);
    const previewLoading = ref(false);
    const preview = ref<SponsoredStudentImportPreview | null>(null);
    const previewError = ref<string | null>(null);
    const processLoading = ref(false);
    const processError = ref<string | null>(null);
    const processResult = ref<SponsoredStudentImportProcessResult | null>(null);

    const templateUrl = route('maintenance.sponsored-students.template');
    const previewUrl = route('maintenance.sponsored-students.preview');
    const processUrl = route('maintenance.sponsored-students.process');

    const previewRows = computed(() => preview.value?.rows ?? []);

    const previewSummaryLabel = computed((): string | null => {
        if (!preview.value) {
            return null;
        }

        const { total, found, notFound, invalid, alreadySponsored, invalidId, selectable } = preview.value.summary;

        return trans('trans.maintenance_sponsored_students_import_preview_summary', {
            total: String(total),
            found: String(found),
            notFound: String(notFound),
            invalid: String(invalid),
            alreadySponsored: String(alreadySponsored),
            invalidId: String(invalidId),
            selectable: String(selectable),
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
            fileError.value = trans('trans.maintenance_sponsored_students_import_invalid_file_type');
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
        formData.append('calendar_year', String(calendarYear));

        try {
            const response = await customAxios('').post<SponsoredStudentImportPreview>(previewUrl, formData, {
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
                ?? trans('trans.maintenance_sponsored_students_import_preview_failed');

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
            summary: buildSummaryFromRows(rows),
        };
    };

    const checkboxSkipTitle = (row: SponsoredStudentImportPreviewRow): string | undefined => {
        return row.skipReasons[0];
    };

    const submitMoveToFinalClass = async (rows: SponsoredStudentImportPreviewRow[]): Promise<boolean> => {
        if (rows.length === 0 || processLoading.value) {
            return false;
        }

        processLoading.value = true;
        processError.value = null;
        processResult.value = null;

        const payload = {
            calendar_year: calendarYear,
            rows: rows.map((row) => ({
                rowNumber: row.rowNumber,
                studentApplicationId: row.studentApplicationId as number,
                sponsor: row.sponsor,
            })),
        };

        try {
            const response = await customAxios('').post<SponsoredStudentImportProcessResult>(processUrl, payload);
            processResult.value = response.data;

            successAlert(
                trans('trans.maintenance_sponsored_students_import_process_success', {
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
                ?? trans('trans.maintenance_sponsored_students_import_process_failed');

            processError.value = message;
            errorAlert(message);

            return false;
        } finally {
            processLoading.value = false;
        }
    };

    const confirmMoveToFinalClass = (rows: SponsoredStudentImportPreviewRow[], onSuccess?: () => void): void => {
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
            trans('trans.maintenance_sponsored_students_import_move_confirm_message', {
                count: String(rows.length),
            }),
            trans('trans.warning'),
            trans('trans.maintenance_sponsored_students_import_move_to_final_class', {
                count: String(rows.length),
            }),
        );
    };

    const statusLabel = (status: SponsoredStudentImportPreviewStatus): string => {
        const keys: Record<SponsoredStudentImportPreviewStatus, string> = {
            found: 'trans.maintenance_sponsored_students_import_status_found',
            not_found: 'trans.maintenance_sponsored_students_import_status_not_found',
            invalid: 'trans.maintenance_sponsored_students_import_status_invalid',
        };

        return trans(keys[status]);
    };

    const statusClass = (status: SponsoredStudentImportPreviewStatus): string => {
        if (status === 'found') {
            return 'text-green-700';
        }

        if (status === 'invalid') {
            return 'text-destructive';
        }

        return 'text-amber-700';
    };

    const classListStatusLabel = (status: SponsoredStudentClassListStatus | null): string => {
        if (status === null) {
            return '—';
        }

        const keys: Record<SponsoredStudentClassListStatus, string> = {
            provisional: 'trans.maintenance_sponsored_students_import_class_list_provisional',
            verified: 'trans.maintenance_sponsored_students_import_class_list_verified',
            waiting: 'trans.maintenance_sponsored_students_import_class_list_waiting',
            final: 'trans.maintenance_sponsored_students_import_class_list_final',
            failed: 'trans.maintenance_sponsored_students_import_class_list_failed',
        };

        return trans(keys[status]);
    };

    const classListStatusClass = (status: SponsoredStudentClassListStatus | null): string => {
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

    const actionLabel = (row: SponsoredStudentImportPreviewRow): string => {
        if (row.action === 'update') {
            return trans('trans.maintenance_sponsored_students_import_action_update');
        }

        if (row.action === 'create') {
            return trans('trans.maintenance_sponsored_students_import_action_create');
        }

        return '—';
    };

    const actionClass = (row: SponsoredStudentImportPreviewRow): string => {
        if (row.action === 'update') {
            return 'rounded-full bg-sky-100 px-2 py-0.5 text-xs font-medium text-sky-900';
        }

        if (row.action === 'create') {
            return 'rounded-full bg-muted px-2 py-0.5 text-xs font-medium text-muted-foreground';
        }

        return 'text-muted-foreground';
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
        previewSummaryLabel,
        canRunPreview,
        cancelImport,
        onFileChange,
        runPreview,
        removePreviewRow,
        checkboxSkipTitle,
        submitMoveToFinalClass,
        confirmMoveToFinalClass,
        statusLabel,
        statusClass,
        classListStatusLabel,
        classListStatusClass,
        actionLabel,
        actionClass,
    };
};
