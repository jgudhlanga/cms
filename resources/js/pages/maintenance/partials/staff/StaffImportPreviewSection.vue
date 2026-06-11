<script setup lang="ts">
import { BaseButton } from '@/components/core/button';
import { Badge } from '@/components/ui/badge';
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

type StaffImportPreviewSummary = Pick<
    StaffImportPreview['summary'],
    'creates' | 'updates' | 'skipped' | 'failed'
>;

defineProps<{
    preview: StaffImportPreview;
    summary: StaffImportPreviewSummary;
    lookups: StaffImportPreviewLookups;
    previewRows: StaffImportPreviewRow[];
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
    'remove-row': [rowNumber: number];
}>();
</script>

<template>
    <div class="space-y-3">
        <div class="flex min-w-0 flex-wrap items-center gap-2">
                <span class="text-xs font-bold uppercase text-accent-foreground">
                    {{ $t('trans.maintenance_staff_import_preview_title') }}
                </span>

                <Badge variant="outline" class="max-w-xs truncate font-normal" :title="preview.fileName">
                    {{ preview.fileName }}
                </Badge>

                <Badge
                    variant="outline"
                    class="border-green-200 bg-green-50 font-normal text-green-800"
                >
                    {{ summary.creates }}
                    {{ $t('trans.maintenance_staff_import_preview_action_create') }}
                </Badge>

                <Badge
                    variant="outline"
                    class="border-blue-200 bg-blue-50 font-normal text-blue-800"
                >
                    {{ summary.updates }}
                    {{ $t('trans.maintenance_staff_import_preview_action_update') }}
                </Badge>

                <Badge variant="secondary" class="font-normal">
                    {{ summary.skipped }}
                    {{ $t('trans.maintenance_staff_import_preview_action_skip_empty') }}
                </Badge>

                <Badge
                    :variant="summary.failed > 0 ? 'destructive' : 'outline'"
                    class="font-normal"
                >
                    {{ summary.failed }}
                    {{ $t('trans.maintenance_staff_import_preview_action_fail') }}
                </Badge>

                <span
                    class="text-xs"
                    :class="canConfirmImport ? 'text-muted-foreground' : 'text-destructive'"
                >
                    {{ confirmBlockedMessage }}
                </span>
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
            @remove-row="(rowNumber) => emit('remove-row', rowNumber)"
        />

        <div class="flex flex-wrap gap-2">
            <BaseButton
                type="button"
                :variant="ColorVariant.warning"
                :size="ButtonSize.lg"
                :disabled="confirmProcessing"
                @click="emit('cancel')"
            >
                {{ $t('trans.cancel') }}
            </BaseButton>
            <BaseButton
                type="button"
                :variant="ColorVariant.primary"
                :size="ButtonSize.lg"
                :processing="confirmProcessing"
                :disabled="!canConfirmImport || confirmProcessing"
                @click="emit('confirm')"
            >
                {{ $t('trans.maintenance_staff_import_confirm') }}
            </BaseButton>
        </div>
    </div>
</template>
