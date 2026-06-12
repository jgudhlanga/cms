import {
    buildRowCorrectionsPayload,
    computeEffectiveSummary,
    getEffectiveCorrection,
} from '@/composables/institution/syllabus-import/syllabusImportRowHelpers';
import { errorAlert } from '@/lib/alerts';
import customAxios from '@/services/http-init';
import type {
    SyllabusImportPreview,
    SyllabusImportPreviewAction,
    SyllabusImportPreviewRow,
    SyllabusImportRowCorrection,
} from '@/types/syllabus-import';
import { useForm } from '@inertiajs/vue3';
import { trans } from 'laravel-vue-i18n';
import { computed, reactive, ref } from 'vue';

const ACCEPTED_EXTENSIONS = ['.xlsx', '.xls', '.csv'];

export const useCourseSyllabusImport = (institutionDepartmentId: string) => {
    const selectedFile = ref<File | null>(null);
    const fileError = ref<string | null>(null);
    const previewLoading = ref(false);
    const preview = ref<SyllabusImportPreview | null>(null);
    const previewError = ref<string | null>(null);
    const rowCorrections = reactive<Record<number, SyllabusImportRowCorrection>>({});
    const excludedRowNumbers = reactive<Set<number>>(new Set());

    const confirmForm = useForm<{
        preview_token: string;
        row_corrections: Record<number, SyllabusImportRowCorrection>;
        excluded_row_numbers: number[];
    }>({
        preview_token: '',
        row_corrections: {},
        excluded_row_numbers: [],
    });

    const templateUrl = route('department-course-syllabuses.import.template', institutionDepartmentId);
    const previewUrl = route('department-course-syllabuses.import.preview', institutionDepartmentId);
    const processUrl = route('department-course-syllabuses.import.process', institutionDepartmentId);

    const previewRows = computed((): SyllabusImportPreviewRow[] => {
        if (!preview.value) {
            return [];
        }

        return preview.value.rows.filter((row) => !excludedRowNumbers.has(row.rowNumber));
    });

    const effectiveSummary = computed(() => {
        if (!preview.value) {
            return null;
        }

        return computeEffectiveSummary(preview.value, rowCorrections, excludedRowNumbers);
    });

    const canConfirmImport = computed((): boolean => {
        const summary = effectiveSummary.value;

        if (!preview.value || !summary) {
            return false;
        }

        return (
            summary.failed === 0
            && summary.syllabusCreates
                + summary.syllabusUpdates
                + summary.moduleCreates
                + summary.moduleUpdates
                > 0
        );
    });

    const confirmBlockedMessage = computed((): string => {
        if (!preview.value || !effectiveSummary.value) {
            return '';
        }

        const summary = effectiveSummary.value;

        if (summary.failed > 0) {
            return trans('syllabus.import_preview_cannot_confirm');
        }

        if (
            summary.syllabusCreates
                + summary.syllabusUpdates
                + summary.moduleCreates
                + summary.moduleUpdates
            === 0
        ) {
            return trans('syllabus.import_no_rows');
        }

        return trans('syllabus.import_preview_can_confirm');
    });

    const previewSummaryLabel = computed((): string | null => {
        if (!effectiveSummary.value) {
            return null;
        }

        const summary = effectiveSummary.value;

        return trans('syllabus.import_preview_summary', {
            syllabusCreates: String(summary.syllabusCreates),
            syllabusUpdates: String(summary.syllabusUpdates),
            moduleCreates: String(summary.moduleCreates),
            moduleUpdates: String(summary.moduleUpdates),
            skipped: String(summary.skipped + summary.moduleSkips),
            failed: String(summary.failed),
        });
    });

    const isAcceptedFile = (file: File): boolean => {
        const name = file.name.toLowerCase();

        return ACCEPTED_EXTENSIONS.some((extension) => name.endsWith(extension));
    };

    const resetPreviewState = (): void => {
        preview.value = null;
        previewError.value = null;
        confirmForm.preview_token = '';
        confirmForm.row_corrections = {};
        confirmForm.excluded_row_numbers = [];
        Object.keys(rowCorrections).forEach((key) => {
            delete rowCorrections[Number(key)];
        });
        excludedRowNumbers.clear();
    };

    const cancelImport = (): void => {
        selectedFile.value = null;
        fileError.value = null;
        previewError.value = null;
        resetPreviewState();
    };

    const onFileChange = (event: Event, fileInput: HTMLInputElement | null): void => {
        const target = event.target as HTMLInputElement;
        const file = target.files?.[0] ?? null;

        selectedFile.value = file;
        fileError.value = null;
        resetPreviewState();

        if (file !== null && !isAcceptedFile(file)) {
            fileError.value = trans('syllabus.import_invalid_file_type');
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
        resetPreviewState();

        const formData = new FormData();
        formData.append('file', selectedFile.value);

        try {
            const response = await customAxios('').post<SyllabusImportPreview>(previewUrl, formData, {
                headers: { 'Content-Type': 'multipart/form-data' },
            });

            preview.value = response.data;
            confirmForm.preview_token = response.data.previewToken;
        } catch (caught) {
            const responseData = (caught as { response?: { data?: { message?: string; errors?: Record<string, string[]> } } })
                .response?.data;

            const message =
                responseData?.errors?.file?.[0]
                ?? responseData?.message
                ?? trans('syllabus.import_preview_failed');

            previewError.value = message;
            errorAlert(message);
        } finally {
            previewLoading.value = false;
        }
    };

    const updateRowCorrection = (rowNumber: number, correction: SyllabusImportRowCorrection): void => {
        rowCorrections[rowNumber] = correction;
    };

    const removeRow = (rowNumber: number): void => {
        excludedRowNumbers.add(rowNumber);
    };

    const getCorrection = (row: SyllabusImportPreviewRow): SyllabusImportRowCorrection => {
        return getEffectiveCorrection(rowCorrections, row);
    };

    const submitImport = (onSuccess?: () => void): void => {
        if (!canConfirmImport.value || !confirmForm.preview_token || !preview.value) {
            return;
        }

        confirmForm.row_corrections = buildRowCorrectionsPayload(
            preview.value,
            rowCorrections,
            excludedRowNumbers,
        );
        confirmForm.excluded_row_numbers = [...excludedRowNumbers];

        confirmForm.post(processUrl, {
            preserveScroll: true,
            onSuccess: () => {
                selectedFile.value = null;
                resetPreviewState();
                onSuccess?.();
            },
        });
    };

    const actionLabel = (action: SyllabusImportPreviewAction): string => {
        const keys: Record<SyllabusImportPreviewAction, string> = {
            create: 'syllabus.import_preview_action_create',
            update: 'syllabus.import_preview_action_update',
            skip: 'syllabus.import_preview_action_skip',
            fail: 'syllabus.import_preview_action_fail',
        };

        return trans(keys[action]);
    };

    const actionClass = (action: SyllabusImportPreviewAction): string => {
        if (action === 'create' || action === 'update') {
            return 'text-green-700';
        }

        if (action === 'skip') {
            return 'text-muted-foreground';
        }

        return 'text-destructive';
    };

    return {
        selectedFile,
        fileError,
        previewLoading,
        preview,
        previewError,
        confirmForm,
        templateUrl,
        previewRows,
        effectiveSummary,
        previewSummaryLabel,
        canConfirmImport,
        confirmBlockedMessage,
        cancelImport,
        onFileChange,
        runPreview,
        submitImport,
        updateRowCorrection,
        removeRow,
        getCorrection,
        actionLabel,
        actionClass,
    };
};
