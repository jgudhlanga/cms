<script setup lang="ts">
import BaseCombobox from '@/components/core/form/combobox/BaseCombobox.vue';
import { AcademicCalendarType } from '@/types/academic-calendar';
import { ref } from 'vue';

const options = ref(
    Object.values(AcademicCalendarType).map((value) => ({
        label: value.charAt(0).toUpperCase() + value.slice(1),
        value,
    })),
);
</script>

<template>
    <BaseCombobox :label="$tChoice('academic_calendar.calendar_type', 1)" :options="options" v-bind="$attrs" />
</template>
