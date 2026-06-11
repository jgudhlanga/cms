<script setup lang="ts">
import { BaseButton } from '@/components/core/button';
import StaffImportPreviewTable from '@/pages/maintenance/partials/staff/StaffImportPreviewTable.vue';
import { ButtonSize } from '@/enums/buttons';
import { ColorVariant } from '@/enums/colors';
import type {
    StaffImportFieldKey,
    StaffImportLookupField,
    StaffImportLookupOption,
    StaffImportPreview,
    StaffImportPreviewLookups,
    StaffImportPreviewRow,
    StaffImportRowCorrection,
} from '@/types/staff-import';

defineProps<{
    preview: StaffImportPreview;
    lookups: StaffImportPreviewLookups;
    previewRows: StaffImportPreviewRow[];
    previewSummaryLabel: string | null;
    confirmBlockedMessage: string;
    canConfirmImport: boolean;
    confirmProcessing: boolean;
    bulkDepartmentField: StaffImportLookupField;
    bulkDepartmentId: number | null;
    getCorrection: (row: StaffImportPreviewRow) => StaffImportRowCorrection;
    getEffectiveAction: (row: StaffImportPreviewRow) => StaffImportPreviewRow['action'];
    getActionLabel: (row: StaffImportPreviewRow) => string;
    getActiveErrors: (row: StaffImportPreviewRow) => string[];
    getCreatedFields: (rowNumber: number) => Set<StaffImportFieldKey>;
    getCreatedRoleNames: (rowNumber: number) => Set<string>;
}>();

const emit = defineEmits<{
    cancel: [];
    confirm: [];
    'update:bulkDepartmentId': [number | null];
    'bulk-department-created': [StaffImportLookupOption];
    'bulk-department-apply': [];
    'update:correction': [rowNumber: number, correction: StaffImportRowCorrection];
    'lookup-created': [rowNumber: number, fieldKey: StaffImportFieldKey, option: StaffImportLookupOption];
}>();
</script>

<template>
    <div class="space-y-4 rounded-lg border border-border p-4">
        <div>
            <h3 class="font-semibold">{{ $t('trans.maintenance_staff_import_preview_title') }}</h3>
            <p class="mt-1 text-sm text-muted-foreground">{{ preview.fileName }}</p>
            <p v-if="previewSummaryLabel" class="mt-2 text-sm">{{ previewSummaryLabel }}</p>
            <p
                class="mt-2 text-sm"
                :class="canConfirmImport ? 'text-muted-foreground' : 'text-destructive'"
            >
                {{ confirmBlockedMessage }}
            </p>
        </div>

        <StaffImportPreviewTable
            :rows="previewRows"
            :lookups="lookups"
            :bulk-department-field="bulkDepartmentField"
            :bulk-department-id="bulkDepartmentId"
            :get-correction="getCorrection"
            :get-effective-action="getEffectiveAction"
            :get-action-label="getActionLabel"
            :get-active-errors="getActiveErrors"
            :get-created-fields="getCreatedFields"
            :get-created-role-names="getCreatedRoleNames"
            @update:bulk-department-id="emit('update:bulkDepartmentId', $event)"
            @bulk-department-created="emit('bulk-department-created', $event)"
            @bulk-department-apply="emit('bulk-department-apply')"
            @update:correction="(rowNumber, correction) => emit('update:correction', rowNumber, correction)"
            @lookup-created="(rowNumber, fieldKey, option) => emit('lookup-created', rowNumber, fieldKey, option)"
        />

        <div class="flex flex-wrap gap-2">
            <BaseButton
                type="button"
                :variant="ColorVariant.warning"
                :size="ButtonSize.sm"
                :disabled="confirmProcessing"
                @click="emit('cancel')"
            >
                {{ $t('trans.cancel') }}
            </BaseButton>
            <BaseButton
                type="button"
                :variant="ColorVariant.primary"
                :size="ButtonSize.sm"
                :processing="confirmProcessing"
                :disabled="!canConfirmImport || confirmProcessing"
                @click="emit('confirm')"
            >
                {{ $t('trans.maintenance_staff_import_confirm') }}
            </BaseButton>
        </div>
    </div>
</template>
