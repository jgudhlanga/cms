<script setup lang="ts">
import BaseCombobox from '@/components/core/form/combobox/BaseCombobox.vue';
import { AcademicCalendarType } from '@/types/academic-calendar';
const options = Object.values(AcademicCalendarType).map((value) => ({
    value,
    label: value.charAt(0).toUpperCase() + value.slice(1),
}));
</script>

<template>
    <BaseCombobox label="Type" :options="options" v-bind="$attrs" />
</template>
