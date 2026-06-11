<script setup lang="ts">
import StaffImportLookupCell from '@/pages/maintenance/partials/staff/StaffImportLookupCell.vue';
import type { StaffImportLookupField, StaffImportLookupOption, StaffImportPreviewLookups } from '@/types/staff-import';

defineProps<{
    bulkDepartmentField: StaffImportLookupField;
    lookups: StaffImportPreviewLookups;
    bulkDepartmentId: number | null;
}>();

const emit = defineEmits<{
    'update:bulkDepartmentId': [number | null];
    'department-created': [StaffImportLookupOption];
    apply: [];
}>();
</script>

<template>
    <tr class="j-tr border-b border-border bg-muted/40">
        <td colspan="13" class="j-td py-1">
            <div class="flex min-w-0 items-center gap-2 text-[10px]">
                <span class="shrink-0 font-medium text-muted-foreground">
                    {{ $t('trans.maintenance_staff_import_apply_department_all') }}
                </span>
                <StaffImportLookupCell
                    compact
                    class="min-w-0 flex-1"
                    :field="bulkDepartmentField"
                    :options="lookups.departments"
                    lookup-type="department"
                    :creatable="bulkDepartmentField.raw !== ''"
                    :model-value="bulkDepartmentId"
                    @update:model-value="emit('update:bulkDepartmentId', $event)"
                    @created="emit('department-created', $event)"
                />
                <button
                    type="button"
                    class="shrink-0 whitespace-nowrap text-primary hover:underline disabled:opacity-50"
                    :disabled="bulkDepartmentId === null"
                    @click="emit('apply')"
                >
                    {{ $t('trans.maintenance_staff_import_apply_department') }}
                </button>
            </div>
        </td>
    </tr>
</template>
