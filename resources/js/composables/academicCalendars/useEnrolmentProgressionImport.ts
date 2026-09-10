import { errorAlert, successAlert } from '@/lib/alerts';
import customAxios from '@/services/http-init';
import { router } from '@inertiajs/vue3';
import { trans } from 'laravel-vue-i18n';
import { computed, ref } from 'vue';

export type ProgressionImportPreviewRow = {
    rowNumber: number;
    studentNumber: string;
    studentEnrolmentId: number | null;
    studentName: string | null;
    phaseLabel: string | null;
    status: string | null;
    eligible: boolean;
    skipReason: string | null;
    academicCalendarClassId: number | null;
};

type ProgressionImportPreview = {
    rows: ProgressionImportPreviewRow[];
    summary: { total: number; eligible: number; skipped: number };
};

const ACCEPTED_EXTENSIONS = ['.xlsx', '.xls', '.csv'];

export function useEnrolmentProgressionImport(options: {
    templateUrl: string;
    previewUrl: string;
    processUrl: string;
    classConfigQuery: Record<string, string>;
    academicCalendarClassId?: number | null;
}) {
    const selectedFile = ref<File | null>(null);
    const fileError = ref<string | null>(null);
    const previewLoading = ref(false);
    const preview = ref<ProgressionImportPreview | null>(null);
    const previewError = ref<string | null>(null);
    const processLoading = ref(false);
    const selectedRowNumbers = ref<number[]>([]);

    const previewRows = computed(() => preview.value?.rows ?? []);
    const eligibleRows = computed(() => previewRows.value.filter((row) => row.eligible));

    const previewSummaryLabel = computed((): string | null => {
        if (!preview.value) {
            return null;
        }

        const { total, eligible, skipped } = preview.value.summary;

        return trans('academic_calendar.progression_import_summary', {
            total: String(total),
            eligible: String(eligible),
            skipped: String(skipped),
        });
    });

    const canRunPreview = computed(
        () => selectedFile.value !== null && fileError.value === null && !previewLoading.value && !processLoading.value,
    );

    const selectedEligibleRows = computed(() =>
        eligibleRows.value.filter((row) => selectedRowNumbers.value.includes(row.rowNumber)),
    );

    const selectAllEligibleModel = computed({
        get: () =>
            eligibleRows.value.length > 0
            && eligibleRows.value.every((row) => selectedRowNumbers.value.includes(row.rowNumber)),
        set: (value: boolean) => {
            selectedRowNumbers.value = value ? eligibleRows.value.map((row) => row.rowNumber) : [];
        },
    });

    const isAcceptedFile = (file: File): boolean => {
        const name = file.name.toLowerCase();

        return ACCEPTED_EXTENSIONS.some((extension) => name.endsWith(extension));
    };

    const onFileChange = (event: Event): void => {
        const input = event.target as HTMLInputElement;
        const file = input.files?.[0] ?? null;
        selectedFile.value = file;
        preview.value = null;
        selectedRowNumbers.value = [];
        previewError.value = null;

        if (file && !isAcceptedFile(file)) {
            fileError.value = trans('academic_calendar.course_work_import_invalid_file_type');
            selectedFile.value = null;

            return;
        }

        fileError.value = null;
    };

    const runPreview = async (): Promise<void> => {
        if (!selectedFile.value) {
            return;
        }

        previewLoading.value = true;
        previewError.value = null;

        const formData = new FormData();
        formData.append('file', selectedFile.value);
        if (options.academicCalendarClassId) {
            formData.append('academic_calendar_class_id', String(options.academicCalendarClassId));
        }

        try {
            const response = await customAxios.post(options.previewUrl, formData, {
                headers: { 'Content-Type': 'multipart/form-data' },
                params: options.classConfigQuery,
            });
            preview.value = response.data as ProgressionImportPreview;
            selectedRowNumbers.value = (preview.value.rows ?? [])
                .filter((row) => row.eligible)
                .map((row) => row.rowNumber);

            if ((preview.value.rows ?? []).length === 0) {
                previewError.value = trans('academic_calendar.progression_import_empty');
            }
        } catch (error: unknown) {
            const message =
                (error as { response?: { data?: { message?: string } } })?.response?.data?.message
                ?? trans('academic_calendar.progression_import_preview_failed');
            previewError.value = message;
            errorAlert(message);
        } finally {
            previewLoading.value = false;
        }
    };

    const isRowSelected = (rowNumber: number): boolean => selectedRowNumbers.value.includes(rowNumber);

    const setRowSelected = (rowNumber: number, value: boolean): void => {
        if (value) {
            if (!selectedRowNumbers.value.includes(rowNumber)) {
                selectedRowNumbers.value = [...selectedRowNumbers.value, rowNumber];
            }

            return;
        }

        selectedRowNumbers.value = selectedRowNumbers.value.filter((id) => id !== rowNumber);
    };

    const processSelected = (): void => {
        if (selectedEligibleRows.value.length === 0) {
            errorAlert(trans('academic_calendar.progression_import_select_eligible'));

            return;
        }

        processLoading.value = true;

        router.post(
            options.processUrl,
            {
                ...options.classConfigQuery,
                academic_calendar_class_id: options.academicCalendarClassId ?? undefined,
                rows: selectedEligibleRows.value.map((row) => ({
                    studentEnrolmentId: row.studentEnrolmentId,
                    academicCalendarClassId: row.academicCalendarClassId,
                })),
            },
            {
                preserveScroll: true,
                onFinish: () => {
                    processLoading.value = false;
                },
                onSuccess: () => {
                    successAlert(trans('trans.success'));
                    preview.value = null;
                    selectedFile.value = null;
                    selectedRowNumbers.value = [];
                },
                onError: () => {
                    errorAlert(trans('academic_calendar.progression_import_select_eligible'));
                },
            },
        );
    };

    const cancelImport = (): void => {
        selectedFile.value = null;
        fileError.value = null;
        preview.value = null;
        previewError.value = null;
        selectedRowNumbers.value = [];
    };

    return {
        templateUrl: options.templateUrl,
        selectedFile,
        fileError,
        previewLoading,
        preview,
        previewError,
        processLoading,
        previewRows,
        eligibleRows,
        previewSummaryLabel,
        canRunPreview,
        selectAllEligibleModel,
        selectedEligibleRows,
        onFileChange,
        runPreview,
        isRowSelected,
        setRowSelected,
        processSelected,
        cancelImport,
    };
}
