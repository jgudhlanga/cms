<script setup lang="ts">
import { BaseButton } from '@/components/core/button';
import { ButtonSize } from '@/enums/buttons';
import { ColorVariant } from '@/enums/colors';
</script>

<template>
    <div
        class="flex flex-col gap-3 rounded-lg border border-border p-3 sm:flex-row sm:items-center sm:justify-between"
    >
        <div class="min-w-0 flex-1 space-y-1">
            <p class="text-sm font-medium text-foreground">
                {{ $t('trans.maintenance_staff_export_title') }}
            </p>
            <p class="text-xs text-muted-foreground">
                {{ $t('trans.maintenance_staff_export_description') }}
            </p>
        </div>

        <a
            :href="route('maintenance.staff.export')"
            class="inline-flex shrink-0"
            target="_blank"
            rel="noopener noreferrer"
        >
            <BaseButton type="button" :variant="ColorVariant.primary_outline" :size="ButtonSize.sm">
                {{ $t('trans.maintenance_staff_export_download') }}
            </BaseButton>
        </a>
    </div>
</template>
