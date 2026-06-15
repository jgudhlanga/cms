<script setup lang="ts">
import StaffImportPanel from '@/pages/maintenance/partials/staff/StaffImportPanel.vue';
import type { StaffImportResult } from '@/types/staff-import';

defineProps<{
    staffImportResult?: StaffImportResult | null;
}>();
</script>

<template>
    <StaffImportPanel :staff-import-result="staffImportResult" />
</template>
