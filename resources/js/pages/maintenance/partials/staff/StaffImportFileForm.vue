<script setup lang="ts">
import { BaseButton } from '@/components/core/button';
import { ButtonSize } from '@/enums/buttons';
import { ColorVariant } from '@/enums/colors';
import { cn } from '@/lib/utils';

withDefaults(
    defineProps<{
        fileError: string | null;
        previewError: string | null;
        previewLoading: boolean;
        hasSelectedFile: boolean;
        compact?: boolean;
    }>(),
    {
        compact: false,
    },
);

const emit = defineEmits<{
    'file-change': [Event];
    preview: [];
}>();
</script>

<template>
    <div :class="cn('space-y-3', compact ? '' : 'rounded-lg border border-border p-4')">
        <div class="space-y-2">
            <label
                class="text-xs font-bold uppercase text-muted-foreground"
                for="staff-import-file"
            >
                {{ $t('trans.maintenance_staff_import_file_label') }}
            </label>
            <div :class="cn('flex flex-col gap-2', compact ? 'sm:flex-row sm:items-center' : '')">
                <input
                    id="staff-import-file"
                    type="file"
                    accept=".xlsx,.xls,.csv,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet,application/vnd.ms-excel,text/csv"
                    class="block min-w-0 flex-1 text-sm text-muted-foreground file:mr-4 file:rounded-md file:border-0 file:bg-secondary file:px-4 file:py-2 file:text-sm file:font-medium"
                    @change="emit('file-change', $event)"
                />
                <BaseButton
                    type="button"
                    :variant="ColorVariant.primary_outline"
                    :size="ButtonSize.sm"
                    class="shrink-0"
                    :processing="previewLoading"
                    :disabled="!hasSelectedFile || previewLoading || Boolean(fileError)"
                    @click="emit('preview')"
                >
                    {{ $t('trans.maintenance_staff_import_preview') }}
                </BaseButton>
            </div>
            <p v-if="!compact" class="text-xs text-muted-foreground">
                {{ $t('trans.maintenance_staff_import_file_hint') }}
            </p>
            <p v-if="fileError" class="text-sm text-destructive">{{ fileError }}</p>
            <p v-if="previewError" class="text-sm text-destructive">{{ previewError }}</p>
        </div>

        <BaseButton
            v-if="!compact"
            type="button"
            :variant="ColorVariant.primary_outline"
            :size="ButtonSize.sm"
            :processing="previewLoading"
            :disabled="!hasSelectedFile || previewLoading || Boolean(fileError)"
            @click="emit('preview')"
        >
            {{ $t('trans.maintenance_staff_import_preview') }}
        </BaseButton>
    </div>
</template>
