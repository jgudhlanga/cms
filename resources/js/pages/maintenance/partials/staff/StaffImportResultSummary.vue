<script setup lang="ts">
import { BaseButton } from '@/components/core/button';
import { ButtonSize } from '@/enums/buttons';
import { ColorVariant } from '@/enums/colors';
import type { StaffImportResult } from '@/types/staff-import';

defineProps<{
    result: StaffImportResult;
}>();

const emit = defineEmits<{
    dismiss: [];
}>();
</script>

<template>
    <div class="rounded-lg border border-border bg-muted/30 p-4 text-sm">
        <div class="flex items-start justify-between gap-3">
            <h3 class="font-semibold">{{ $t('trans.maintenance_staff_import_result_title') }}</h3>
            <BaseButton
                type="button"
                :variant="ColorVariant.warning_outline"
                :size="ButtonSize.sm"
                @click="emit('dismiss')"
            >
                {{ $t('trans.close') }}
            </BaseButton>
        </div>
        <ul class="mt-2 space-y-1 text-muted-foreground">
            <li>
                {{ $t('trans.maintenance_staff_import_result_total', { count: String(result.rowsTotal) }) }}
            </li>
            <li>
                {{ $t('trans.maintenance_staff_import_result_succeeded', { count: String(result.rowsSucceeded) }) }}
            </li>
            <li>
                {{ $t('trans.maintenance_staff_import_result_failed', { count: String(result.rowsFailed) }) }}
            </li>
            <li>
                {{ $t('trans.maintenance_staff_import_result_skipped', { count: String(result.rowsSkipped) }) }}
            </li>
        </ul>
    </div>
</template>
