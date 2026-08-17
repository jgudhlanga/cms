import { errorAlert, successAlert, warningDialog } from '@/lib/alerts';
import customAxios from '@/services/http-init';
import type {
    HostelOccupantImportFilter,
    HostelOccupantImportPaymentSource,
    HostelOccupantImportPreview,
    HostelOccupantImportPreviewRow,
    HostelOccupantImportProcessResult,
} from '@/types/hostel-occupant-import';
import { router } from '@inertiajs/vue3';
import { trans } from 'laravel-vue-i18n';
import { computed, ref } from 'vue';

const ACCEPTED_EXTENSIONS = ['.xlsx', '.xls', '.csv'];

export const useHostelOccupantImport = (hostelId: number, canConfirmPayments: boolean) => {
    const selectedFile = ref<File | null>(null);
    const fileError = ref<string | null>(null);
    const previewLoading = ref(false);
    const preview = ref<HostelOccupantImportPreview | null>(null);
    const previewError = ref<string | null>(null);
    const processLoading = ref(false);
    const processError = ref<string | null>(null);
    const processResult = ref<HostelOccupantImportProcessResult | null>(null);
    const activeFilter = ref<HostelOccupantImportFilter>('all');

    const templateUrl = route('hostels.occupants.import.template', hostelId);
    const previewUrl = route('hostels.occupants.import.preview', hostelId);
    const processUrl = route('hostels.occupants.import.process', hostelId);

    const previewRows = computed(() => preview.value?.rows ?? []);

    const filteredPreviewRows = computed(() => {
        if (activeFilter.value === 'ready') {
            return previewRows.value.filter((row) => row.isSelectable);
        }

        if (activeFilter.value === 'assumed_paid') {
            return previewRows.value.filter((row) => row.isSelectable && row.paymentSource === 'assumed_paid');
        }

        if (activeFilter.value === 'errors') {
            return previewRows.value.filter((row) => !row.isSelectable);
        }

        return previewRows.value;
    });

    const previewSummaryLabel = computed((): string | null => {
        if (!preview.value) {
            return null;
        }

        const { total, ready, assumedPaid, errors } = preview.value.summary;

        return trans('hms.import_occupants_preview_summary', {
            total: String(total),
            ready: String(ready),
            assumedPaid: String(assumedPaid),
            errors: String(errors),
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
        activeFilter.value = 'all';
    };

    const cancelImport = (): void => {
        selectedFile.value = null;
        fileError.value = null;
        resetPreviewState();
    };

    const onFileChange = (event: Event, fileInput: HTMLInputElement | null): void => {
        const target = event.target as HTMLInputElement;
        const file = target.files?.[0] ?? null;

        selectedFile.value = file;
        fileError.value = null;
        resetPreviewState();

        if (file !== null && !isAcceptedFile(file)) {
            fileError.value = trans('hms.import_occupants_invalid_file_type');
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

        try {
            const response = await customAxios('').post<HostelOccupantImportPreview>(previewUrl, formData, {
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
                ?? trans('hms.import_occupants_preview_failed');

            previewError.value = message;
            errorAlert(message);
        } finally {
            previewLoading.value = false;
        }
    };

    const paymentLabel = (source: HostelOccupantImportPaymentSource | null): string => {
        if (source === 'ledger') {
            return trans('hms.import_occupants_payment_ledger');
        }

        if (source === 'bank') {
            return trans('hms.import_occupants_payment_bank');
        }

        if (source === 'sponsored') {
            return trans('hms.import_occupants_payment_sponsored');
        }

        if (source === 'apprentice') {
            return trans('hms.import_occupants_payment_apprentice');
        }

        if (source === 'assumed_paid') {
            return trans('hms.import_occupants_payment_assumed_paid');
        }

        return trans('hms.import_occupants_status_invalid');
    };

    const paymentClass = (source: HostelOccupantImportPaymentSource | null): string => {
        if (source === 'ledger' || source === 'bank') {
            return 'text-emerald-700';
        }

        if (source === 'sponsored' || source === 'apprentice') {
            return 'text-violet-700';
        }

        if (source === 'assumed_paid') {
            return 'text-amber-700';
        }

        return 'text-destructive';
    };

    const statusLabel = (row: HostelOccupantImportPreviewRow): string => {
        return row.isSelectable
            ? trans('hms.import_occupants_status_ready')
            : trans('hms.import_occupants_status_invalid');
    };

    const statusClass = (row: HostelOccupantImportPreviewRow): string => {
        return row.isSelectable ? 'text-emerald-700' : 'text-destructive';
    };

    const checkboxSkipTitle = (row: HostelOccupantImportPreviewRow): string => {
        return row.skipReasons[0] ?? '';
    };

    const submitImport = async (rows: HostelOccupantImportPreviewRow[]): Promise<boolean> => {
        const payload = {
            rows: rows.map((row) => ({
                rowNumber: row.rowNumber,
                studentId: row.studentId as number,
                disability: row.disability,
                hostelRoomId: row.hostelRoomId as number,
                hostelRoomSectionId: row.hostelRoomSectionId as number,
            })),
        };

        processLoading.value = true;
        processError.value = null;
        processResult.value = null;

        try {
            const response = await customAxios('').post<HostelOccupantImportProcessResult>(processUrl, payload);
            processResult.value = response.data;

            const message = trans('hms.import_occupants_process_success', {
                imported: String(response.data.summary.imported),
                skipped: String(response.data.summary.skipped),
            });

            successAlert(message);
            router.visit(route('hostels.show', hostelId));

            return true;
        } catch (caught) {
            const status = (caught as { response?: { status?: number } }).response?.status;
            const responseData = (caught as {
                response?: { data?: { message?: string } };
            }).response?.data;

            const message = status === 403
                ? trans('hms.import_occupants_process_forbidden')
                : (responseData?.message ?? trans('hms.import_occupants_process_failed'));

            processError.value = message;
            errorAlert(message);

            return false;
        } finally {
            processLoading.value = false;
        }
    };

    const confirmImport = (rows: HostelOccupantImportPreviewRow[]): void => {
        if (rows.length === 0 || !canConfirmPayments) {
            return;
        }

        const assumedPaidCount = rows.filter((row) => row.paymentSource === 'assumed_paid').length;
        let message = trans('hms.import_occupants_confirm_message', {
            count: String(rows.length),
        });

        if (assumedPaidCount > 0) {
            message = `${message} ${trans('hms.import_occupants_confirm_assumed_note', {
                count: String(assumedPaidCount),
            })}`;
        }

        warningDialog(
            () => {
                void submitImport(rows);

                return true;
            },
            message,
            trans('hms.import_occupants_confirm_title'),
        );
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
        activeFilter,
        templateUrl,
        previewRows,
        filteredPreviewRows,
        previewSummaryLabel,
        canRunPreview,
        canConfirmPayments,
        cancelImport,
        onFileChange,
        runPreview,
        confirmImport,
        paymentLabel,
        paymentClass,
        statusLabel,
        statusClass,
        checkboxSkipTitle,
    };
};
