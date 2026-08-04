<script setup lang="ts">
import StaffExportPanel from '@/pages/maintenance/partials/staff/StaffExportPanel.vue';
import StaffImportPanel from '@/pages/maintenance/partials/staff/StaffImportPanel.vue';
import type { StaffImportResult } from '@/types/staff-import';

defineProps<{
    staffImportResult?: StaffImportResult | null;
}>();
</script>

<template>
    <div class="w-full min-w-0 space-y-4">
        <StaffExportPanel />
        <StaffImportPanel :staff-import-result="staffImportResult" />
    </div>
</template>
