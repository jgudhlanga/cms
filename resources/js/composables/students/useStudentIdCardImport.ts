import { errorAlert, successAlert, warningDialog } from '@/lib/alerts';
import customAxios from '@/services/http-init';
import type {
    StudentIdCardImportFilter,
    StudentIdCardImportPreview,
    StudentIdCardImportPreviewRow,
    StudentIdCardImportProcessResult,
} from '@/types/student-id-card-import';
import { router } from '@inertiajs/vue3';
import { trans } from 'laravel-vue-i18n';
import { computed, ref } from 'vue';

const ACCEPTED_EXTENSIONS = ['.csv'];

export const useStudentIdCardImport = () => {
    const selectedFile = ref<File | null>(null);
    const fileError = ref<string | null>(null);
    const previewLoading = ref(false);
    const preview = ref<StudentIdCardImportPreview | null>(null);
    const previewError = ref<string | null>(null);
    const processLoading = ref(false);
    const processError = ref<string | null>(null);
    const activeFilter = ref<StudentIdCardImportFilter>('all');

    const templateUrl = route('admin.students.id-card-requests.import.template');
    const previewUrl = route('admin.students.id-card-requests.import.preview');
    const processUrl = route('admin.students.id-card-requests.import.process');

    const previewRows = computed(() => preview.value?.rows ?? []);

    const filteredPreviewRows = computed(() => {
        if (activeFilter.value === 'ready') {
            return previewRows.value.filter((row) => row.isSelectable);
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

        const { total, ready, errors } = preview.value.summary;

        return trans('trans.student_id_card_import_preview_summary', {
            total: String(total),
            ready: String(ready),
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
        return ACCEPTED_EXTENSIONS.some((extension) => file.name.toLowerCase().endsWith(extension));
    };

    const resetPreviewState = (): void => {
        preview.value = null;
        previewError.value = null;
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
            fileError.value = trans('trans.student_id_card_import_invalid_file_type');
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
        processError.value = null;
        preview.value = null;

        const formData = new FormData();
        formData.append('file', selectedFile.value);

        try {
            const response = await customAxios('').post<StudentIdCardImportPreview>(previewUrl, formData, {
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
                ?? trans('trans.student_id_card_import_preview_failed');

            previewError.value = message;
            errorAlert(message);
        } finally {
            previewLoading.value = false;
        }
    };

    const statusLabel = (row: StudentIdCardImportPreviewRow): string => {
        return row.isSelectable
            ? trans('trans.student_id_card_import_status_ready')
            : trans('trans.student_id_card_import_status_invalid');
    };

    const statusClass = (row: StudentIdCardImportPreviewRow): string => {
        return row.isSelectable ? 'text-emerald-700' : 'text-destructive';
    };

    const checkboxSkipTitle = (row: StudentIdCardImportPreviewRow): string => {
        return row.skipReasons[0] ?? '';
    };

    const submitImport = async (rows: StudentIdCardImportPreviewRow[]): Promise<boolean> => {
        const payload = {
            rows: rows.map((row) => ({
                rowNumber: row.rowNumber,
                studentId: row.studentId as number,
            })),
        };

        processLoading.value = true;
        processError.value = null;

        try {
            const response = await customAxios('').post<StudentIdCardImportProcessResult>(processUrl, payload);

            successAlert(trans('trans.student_id_card_import_process_success', {
                imported: String(response.data.summary.imported),
                skipped: String(response.data.summary.skipped),
            }));

            router.visit(route('admin.students.id-card-requests.index'));

            return true;
        } catch (caught) {
            const responseData = (caught as {
                response?: { data?: { message?: string } };
            }).response?.data;

            const message = responseData?.message ?? trans('trans.student_id_card_import_process_failed');
            processError.value = message;
            errorAlert(message);

            return false;
        } finally {
            processLoading.value = false;
        }
    };

    const confirmImport = (rows: StudentIdCardImportPreviewRow[]): void => {
        if (rows.length === 0) {
            return;
        }

        warningDialog(
            () => {
                void submitImport(rows);

                return true;
            },
            trans('trans.student_id_card_import_confirm_message', {
                count: String(rows.length),
            }),
            trans('trans.student_id_card_import_confirm_title'),
        );
    };

    return {
        fileError,
        previewLoading,
        preview,
        previewError,
        processLoading,
        processError,
        activeFilter,
        templateUrl,
        filteredPreviewRows,
        previewSummaryLabel,
        canRunPreview,
        cancelImport,
        onFileChange,
        runPreview,
        confirmImport,
        statusLabel,
        statusClass,
        checkboxSkipTitle,
    };
};
