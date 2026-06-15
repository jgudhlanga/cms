<script setup lang="ts">
import { BaseButton } from '@/components/core/button';
import { ButtonSize } from '@/enums/buttons';
import { ColorVariant } from '@/enums/colors';
import type { StaffImportFailedRow } from '@/types/staff-import';

defineProps<{
    failedRows: StaffImportFailedRow[];
}>();

const emit = defineEmits<{
    dismiss: [];
}>();
</script>

<template>
    <div class="rounded-lg border border-destructive/30 bg-destructive/5 p-4 text-sm">
        <div class="flex items-start justify-between gap-3">
            <h3 class="font-semibold text-destructive">
                {{ $t('trans.maintenance_staff_import_result_failed_rows_title') }}
            </h3>
            <BaseButton
                type="button"
                :variant="ColorVariant.warning_outline"
                :size="ButtonSize.sm"
                @click="emit('dismiss')"
            >
                {{ $t('trans.close') }}
            </BaseButton>
        </div>
        <ul class="mt-2 space-y-2">
            <li
                v-for="failedRow in failedRows"
                :key="failedRow.rowNumber"
                class="rounded border border-destructive/30 bg-background px-2 py-1.5 text-xs"
            >
                <p class="font-medium text-foreground">
                    {{ $t('trans.maintenance_staff_import_result_failed_row', { row: String(failedRow.rowNumber) }) }}
                    <span v-if="failedRow.employeeNumber"> · {{ failedRow.employeeNumber }}</span>
                    <span v-if="failedRow.fullName"> · {{ failedRow.fullName }}</span>
                    <span v-if="failedRow.email"> · {{ failedRow.email }}</span>
                </p>
                <p class="mt-0.5 text-destructive">
                    {{ failedRow.errors.join(' ') }}
                </p>
            </li>
        </ul>
    </div>
</template>
