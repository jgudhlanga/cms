<script setup lang="ts">
import { BaseButton } from '@/components/core/button';
import { useStaffImport } from '@/composables/maintenance/useStaffImport';
import { useStaffImportResult } from '@/composables/maintenance/useStaffImportResult';
import { ButtonSize } from '@/enums/buttons';
import { ColorVariant } from '@/enums/colors';
import StaffImportFailedRowsPanel from '@/pages/maintenance/partials/staff/StaffImportFailedRowsPanel.vue';
import StaffImportFileForm from '@/pages/maintenance/partials/staff/StaffImportFileForm.vue';
import StaffImportPreviewSection from '@/pages/maintenance/partials/staff/StaffImportPreviewSection.vue';
import StaffImportResultSummary from '@/pages/maintenance/partials/staff/StaffImportResultSummary.vue';
import type { StaffImportResult } from '@/types/staff-import';
import { ref, toRef } from 'vue';

export type { StaffImportResult };

const props = defineProps<{
    staffImportResult?: StaffImportResult | null;
}>();

const fileFormKey = ref(0);

const {
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
    bulkDepartmentField,
    cancelImport,
    onFileChange,
    runPreview,
    updateRowCorrection,
    removeRow,
    onLookupCreated,
    onBulkDepartmentCreated,
    applyBulkDepartment,
    submitImport,
    getCreatedFieldsForRow,
    getCreatedRoleNamesForRow,
    getRowCorrection,
    getRowActiveErrors,
    getRowEffectiveAction,
    getRowActionLabel,
} = useStaffImport();

const {
    persistedFailedRows,
    hasImportResult,
    dismissImportResult,
    clearPersistedFailedRows,
} = useStaffImportResult(toRef(props, 'staffImportResult'));

const resetFileForm = (): void => {
    fileFormKey.value++;
};

const handleCancel = (): void => {
    cancelImport();
    resetFileForm();
};

const handlePreview = (): void => {
    void runPreview(clearPersistedFailedRows);
};

const handleConfirm = (): void => {
    submitImport(resetFileForm);
};
</script>

<template>
    <div class="w-full min-w-0 space-y-4">
        <div
            class="flex flex-col gap-4 rounded-lg border border-border p-3 md:flex-row md:items-end md:justify-between"
        >
            <a :href="templateUrl" class="inline-flex shrink-0" target="_blank" rel="noopener noreferrer">
                <BaseButton type="button" :variant="ColorVariant.primary_outline" :size="ButtonSize.sm">
                    {{ $t('trans.maintenance_staff_import_download_template') }}
                </BaseButton>
            </a>

            <StaffImportFileForm
                :key="fileFormKey"
                class="min-w-0 flex-1 md:max-w-xl"
                :compact="true"
                :file-error="fileError"
                :preview-error="previewError"
                :preview-loading="previewLoading"
                :has-selected-file="selectedFile !== null"
                @file-change="onFileChange"
                @preview="handlePreview"
            />
        </div>

        <StaffImportPreviewSection
            v-if="preview && previewLookups && effectiveSummary"
            :preview="preview"
            :summary="effectiveSummary"
            :lookups="previewLookups"
            :preview-rows="previewRows"
            :confirm-blocked-message="confirmBlockedMessage"
            :can-confirm-import="canConfirmImport"
            :confirm-processing="confirmForm.processing"
            :bulk-department-field="bulkDepartmentField"
            :bulk-department-id="bulkDepartmentId"
            :get-correction="getRowCorrection"
            :get-effective-action="getRowEffectiveAction"
            :get-action-label="getRowActionLabel"
            :get-active-errors="getRowActiveErrors"
            :get-created-fields="getCreatedFieldsForRow"
            :get-created-role-names="getCreatedRoleNamesForRow"
            @cancel="handleCancel"
            @confirm="handleConfirm"
            @update:bulk-department-id="bulkDepartmentId = $event"
            @bulk-department-created="onBulkDepartmentCreated"
            @bulk-department-apply="applyBulkDepartment"
            @update:correction="updateRowCorrection"
            @lookup-created="onLookupCreated"
            @remove-row="removeRow"
        />

        <StaffImportResultSummary
            v-if="hasImportResult && staffImportResult"
            :result="staffImportResult"
            @dismiss="dismissImportResult"
        />

        <StaffImportFailedRowsPanel
            v-if="persistedFailedRows.length"
            :failed-rows="persistedFailedRows"
            @dismiss="clearPersistedFailedRows"
        />
    </div>
</template>
