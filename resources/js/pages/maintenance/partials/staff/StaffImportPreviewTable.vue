<script setup lang="ts">
import StaffImportBulkDepartmentRow from '@/pages/maintenance/partials/staff/StaffImportBulkDepartmentRow.vue';
import StaffImportPreviewRow from '@/pages/maintenance/partials/staff/StaffImportPreviewRow.vue';
import type {
    StaffImportFieldKey,
    StaffImportLookupField,
    StaffImportLookupOption,
    StaffImportPreviewLookups,
    StaffImportPreviewRow as StaffImportPreviewRowData,
    StaffImportRowCorrection,
} from '@/types/staff-import';

defineProps<{
    rows: StaffImportPreviewRowData[];
    lookups: StaffImportPreviewLookups;
    bulkDepartmentField: StaffImportLookupField;
    bulkDepartmentId: number | null;
    getCorrection: (row: StaffImportPreviewRowData) => StaffImportRowCorrection;
    getEffectiveAction: (row: StaffImportPreviewRowData) => StaffImportPreviewRowData['action'];
    getActionLabel: (row: StaffImportPreviewRowData) => string;
    getActiveErrors: (row: StaffImportPreviewRowData) => string[];
    getCreatedFields: (rowNumber: number) => Set<StaffImportFieldKey>;
    getCreatedRoleNames: (rowNumber: number) => Set<string>;
}>();

const emit = defineEmits<{
    'update:bulkDepartmentId': [number | null];
    'bulk-department-created': [StaffImportLookupOption];
    'bulk-department-apply': [];
    'update:correction': [rowNumber: number, correction: StaffImportRowCorrection];
    'lookup-created': [rowNumber: number, fieldKey: StaffImportFieldKey, option: StaffImportLookupOption];
    'remove-row': [rowNumber: number];
}>();
</script>

<template>
    <div class="w-full overflow-x-auto rounded-lg border border-border">
        <table class="j-table w-full min-w-full">
            <thead class="j-thead sticky top-0 z-10 bg-muted">
                <tr class="j-th">
                    <th class="j-th text-left">#</th>
                    <th class="j-th text-left">{{ $t('trans.employee_number') }}</th>
                    <th class="j-th text-left">{{ $tChoice('trans.name', 1) }}</th>
                    <th class="j-th text-left">{{ $t('trans.email') }}</th>
                    <th class="j-th text-left">{{ $t('trans.phone_number') }}</th>
                    <th class="j-th text-left">{{ $t('trans.date_of_birth') }}</th>
                    <th class="j-th text-left">{{ $tChoice('trans.department', 1) }}</th>
                    <th class="j-th text-left">{{ $tChoice('trans.title', 1) }}</th>
                    <th class="j-th text-left">{{ $tChoice('trans.gender', 1) }}</th>
                    <th class="j-th text-left">{{ $t('trans.maintenance_staff_import_marital_status') }}</th>
                    <th class="j-th text-left">{{ $t('trans.maintenance_staff_import_employment_type') }}</th>
                    <th class="j-th text-left">{{ $tChoice('trans.role', 2) }}</th>
                    <th class="j-th text-center">{{ $tChoice('trans.status', 1) }}</th>
                </tr>
            </thead>
            <tbody class="j-tbody">
                <StaffImportBulkDepartmentRow
                    :bulk-department-field="bulkDepartmentField"
                    :lookups="lookups"
                    :bulk-department-id="bulkDepartmentId"
                    @update:bulk-department-id="emit('update:bulkDepartmentId', $event)"
                    @department-created="emit('bulk-department-created', $event)"
                    @apply="emit('bulk-department-apply')"
                />
                <StaffImportPreviewRow
                    v-for="row in rows"
                    :key="row.rowNumber"
                    :row="row"
                    :lookups="lookups"
                    :correction="getCorrection(row)"
                    :effective-action="getEffectiveAction(row)"
                    :action-label="getActionLabel(row)"
                    :active-errors="getActiveErrors(row)"
                    :created-fields="getCreatedFields(row.rowNumber)"
                    :created-role-names="getCreatedRoleNames(row.rowNumber)"
                    @update:correction="emit('update:correction', row.rowNumber, $event)"
                    @lookup-created="(fieldKey, option) => emit('lookup-created', row.rowNumber, fieldKey, option)"
                    @remove="emit('remove-row', row.rowNumber)"
                />
            </tbody>
        </table>
    </div>
</template>
