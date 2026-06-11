import { STAFF_IMPORT_ACCEPTED_EXTENSIONS } from '@/composables/maintenance/staff-import/constants';
import {
    activeRowErrors,
    buildRowCorrectionsPayload,
    computeEffectiveSummary,
    effectiveAction,
    getEffectiveCorrection,
    lookupOptionsKey,
    resolvedLookupId,
    staffImportActionLabel,
} from '@/composables/maintenance/staff-import/staffImportRowHelpers';
import { errorAlert } from '@/lib/alerts';
import customAxios from '@/services/http-init';
import type {
    StaffImportFieldKey,
    StaffImportLookupOption,
    StaffImportPreview,
    StaffImportPreviewLookups,
    StaffImportPreviewRow,
    StaffImportRowCorrection,
} from '@/types/staff-import';
import { useForm } from '@inertiajs/vue3';
import { trans } from 'laravel-vue-i18n';
import { computed, reactive, ref } from 'vue';

export const useStaffImport = () => {
    const selectedFile = ref<File | null>(null);
    const fileError = ref<string | null>(null);
    const previewLoading = ref(false);
    const preview = ref<StaffImportPreview | null>(null);
    const previewLookups = ref<StaffImportPreviewLookups | null>(null);
    const previewError = ref<string | null>(null);
    const rowCorrections = reactive<Record<number, StaffImportRowCorrection>>({});
    const excludedRowNumbers = reactive<Set<number>>(new Set());
    const createdFields = reactive<Record<number, StaffImportFieldKey[]>>({});
    const createdRoleNames = reactive<Record<number, string[]>>({});
    const bulkDepartmentId = ref<number | null>(null);

    const confirmForm = useForm<{
        preview_token: string;
        row_corrections: Record<number, StaffImportRowCorrection>;
        excluded_row_numbers: number[];
    }>({
        preview_token: '',
        row_corrections: {},
        excluded_row_numbers: [],
    });

    const templateUrl = route('maintenance.staff-import.template');
    const previewUrl = route('maintenance.staff-import.preview');
    const processUrl = route('maintenance.staff-import.process');

    const previewRows = computed((): StaffImportPreviewRow[] => {
        if (!preview.value) {
            return [];
        }

        return preview.value.rows.filter(
            (row) => row.action !== 'skip_empty' && !excludedRowNumbers.has(row.rowNumber),
        );
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

        return summary.failed === 0 && summary.creates + summary.updates > 0;
    });

    const confirmBlockedMessage = computed((): string => {
        if (!preview.value || !effectiveSummary.value) {
            return '';
        }

        const { failed, creates, updates } = effectiveSummary.value;

        if (failed > 0) {
            return trans('trans.maintenance_staff_import_preview_cannot_confirm');
        }

        if (creates + updates === 0) {
            return trans('trans.maintenance_staff_import_preview_no_rows');
        }

        return trans('trans.maintenance_staff_import_preview_can_confirm');
    });

    const previewSummaryLabel = computed((): string | null => {
        if (!effectiveSummary.value) {
            return null;
        }

        const { creates, updates, skipped, failed } = effectiveSummary.value;

        return trans('trans.maintenance_staff_import_preview_summary', {
            creates: String(creates),
            updates: String(updates),
            skipped: String(skipped),
            failed: String(failed),
        });
    });

    const bulkDepartmentField = computed((): StaffImportPreviewRow['fields']['department'] => {
        const rawCounts = new Map<string, number>();

        for (const row of previewRows.value) {
            if (resolvedLookupId(row, rowCorrections, 'department', 'institutionDepartmentId') !== null) {
                continue;
            }

            const raw = row.fields.department.raw.trim();

            if (raw !== '') {
                rawCounts.set(raw, (rawCounts.get(raw) ?? 0) + 1);
            }
        }

        let raw = '';
        let highestCount = 0;

        for (const [value, count] of rawCounts) {
            if (count > highestCount) {
                raw = value;
                highestCount = count;
            }
        }

        return {
            raw,
            resolvedId: bulkDepartmentId.value,
            resolvedLabel: null,
            matchType: null,
            needsReview: false,
        };
    });

    const isAcceptedFile = (file: File): boolean => {
        const name = file.name.toLowerCase();

        return STAFF_IMPORT_ACCEPTED_EXTENSIONS.some((extension) => name.endsWith(extension));
    };

    const resetPreviewState = (): void => {
        preview.value = null;
        previewLookups.value = null;
        previewError.value = null;
        confirmForm.preview_token = '';
        confirmForm.row_corrections = {};
        confirmForm.excluded_row_numbers = [];
        Object.keys(rowCorrections).forEach((key) => {
            delete rowCorrections[Number(key)];
        });
        excludedRowNumbers.clear();
        Object.keys(createdFields).forEach((key) => {
            delete createdFields[Number(key)];
        });
        Object.keys(createdRoleNames).forEach((key) => {
            delete createdRoleNames[Number(key)];
        });
        bulkDepartmentId.value = null;
    };

    const cancelImport = (): void => {
        selectedFile.value = null;
        fileError.value = null;
        previewError.value = null;
        resetPreviewState();
    };

    const onFileChange = (event: Event): void => {
        const target = event.target as HTMLInputElement;
        const file = target.files?.[0] ?? null;

        selectedFile.value = file;
        fileError.value = null;
        resetPreviewState();

        if (file !== null && !isAcceptedFile(file)) {
            fileError.value = trans('trans.maintenance_staff_import_invalid_file_type');
            selectedFile.value = null;
        }
    };

    const runPreview = async (onBeforePreview?: () => void): Promise<void> => {
        if (!selectedFile.value || fileError.value) {
            return;
        }

        onBeforePreview?.();

        previewLoading.value = true;
        previewError.value = null;
        resetPreviewState();

        const formData = new FormData();
        formData.append('file', selectedFile.value);

        try {
            const response = await customAxios('').post<StaffImportPreview>(previewUrl, formData, {
                headers: { 'Content-Type': 'multipart/form-data' },
            });

            preview.value = response.data;
            previewLookups.value = structuredClone(response.data.lookups);
            confirmForm.preview_token = response.data.previewToken;
        } catch (caught) {
            const responseData = (caught as { response?: { data?: { message?: string; errors?: Record<string, string[]> } } })
                .response?.data;

            const message =
                responseData?.errors?.file?.[0] ??
                responseData?.message ??
                trans('trans.maintenance_staff_import_preview_failed');

            previewError.value = message;
            errorAlert(message);
        } finally {
            previewLoading.value = false;
        }
    };

    const updateRowCorrection = (rowNumber: number, correction: StaffImportRowCorrection): void => {
        rowCorrections[rowNumber] = correction;
    };

    const removeRow = (rowNumber: number): void => {
        excludedRowNumbers.add(rowNumber);
    };

    const onLookupCreated = (
        rowNumber: number,
        fieldKey: StaffImportFieldKey,
        option: StaffImportLookupOption,
    ): void => {
        const optionsKey = lookupOptionsKey(fieldKey);

        if (previewLookups.value !== null && optionsKey !== null) {
            const existing = previewLookups.value[optionsKey];
            const alreadyExists = existing.some((item) => item.value === option.value);

            if (!alreadyExists) {
                previewLookups.value[optionsKey] = [...existing, option].sort((a, b) =>
                    a.label.localeCompare(b.label),
                );
            }
        }

        if (!createdFields[rowNumber]?.includes(fieldKey)) {
            createdFields[rowNumber] = [...(createdFields[rowNumber] ?? []), fieldKey];
        }

        if (fieldKey === 'roles' && !createdRoleNames[rowNumber]?.includes(option.label)) {
            createdRoleNames[rowNumber] = [...(createdRoleNames[rowNumber] ?? []), option.label];
        }
    };

    const applyBulkDepartment = (): void => {
        if (bulkDepartmentId.value === null) {
            return;
        }

        for (const row of previewRows.value) {
            rowCorrections[row.rowNumber] = {
                ...getEffectiveCorrection(rowCorrections, row),
                institutionDepartmentId: bulkDepartmentId.value,
            };
        }
    };

    const onBulkDepartmentCreated = (option: StaffImportLookupOption): void => {
        if (previewLookups.value !== null) {
            const existing = previewLookups.value.departments;
            const alreadyExists = existing.some((item) => item.value === option.value);

            if (!alreadyExists) {
                previewLookups.value.departments = [...existing, option].sort((a, b) =>
                    a.label.localeCompare(b.label),
                );
            }
        }

        bulkDepartmentId.value = option.value;
        applyBulkDepartment();
    };

    const submitImport = (onImportSuccess?: () => void): void => {
        if (!canConfirmImport.value || !confirmForm.preview_token || !preview.value) {
            return;
        }

        confirmForm.row_corrections = buildRowCorrectionsPayload(preview.value, rowCorrections, excludedRowNumbers);
        confirmForm.excluded_row_numbers = [...excludedRowNumbers];

        confirmForm.post(processUrl, {
            preserveScroll: true,
            onSuccess: () => {
                selectedFile.value = null;
                resetPreviewState();
                onImportSuccess?.();
            },
        });
    };

    const getCreatedFieldsForRow = (rowNumber: number): Set<StaffImportFieldKey> => {
        return new Set(createdFields[rowNumber] ?? []);
    };

    const getCreatedRoleNamesForRow = (rowNumber: number): Set<string> => {
        return new Set(createdRoleNames[rowNumber] ?? []);
    };

    const getRowCorrection = (row: StaffImportPreviewRow): StaffImportRowCorrection => {
        return getEffectiveCorrection(rowCorrections, row);
    };

    const getRowActiveErrors = (row: StaffImportPreviewRow): string[] => {
        return activeRowErrors(row, rowCorrections);
    };

    const getRowEffectiveAction = (row: StaffImportPreviewRow): StaffImportPreviewRow['action'] => {
        return effectiveAction(row, rowCorrections);
    };

    const getRowActionLabel = (row: StaffImportPreviewRow): string => {
        return staffImportActionLabel(effectiveAction(row, rowCorrections));
    };

    return {
        selectedFile,
        fileError,
        previewLoading,
        preview,
        previewLookups,
        previewError,
        bulkDepartmentId,
        confirmForm,
        templateUrl,
        previewRows,
        effectiveSummary,
        canConfirmImport,
        confirmBlockedMessage,
        previewSummaryLabel,
        bulkDepartmentField,
        cancelImport,
        onFileChange,
        runPreview,
        updateRowCorrection,
        removeRow,
        onLookupCreated,
        applyBulkDepartment,
        onBulkDepartmentCreated,
        submitImport,
        getCreatedFieldsForRow,
        getCreatedRoleNamesForRow,
        getRowCorrection,
        getRowActiveErrors,
        getRowEffectiveAction,
        getRowActionLabel,
    };
};
