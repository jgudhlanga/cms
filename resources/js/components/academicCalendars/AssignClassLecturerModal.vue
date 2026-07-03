<script setup lang="ts">
import AssignClassTutorModal from '@/components/academicCalendars/AssignClassTutorModal.vue';

defineProps<{
    institutionDepartmentId: number;
    calendarYear: string;
}>();
</script>

<template>
    <AssignClassTutorModal v-bind="$props" />
</template>
