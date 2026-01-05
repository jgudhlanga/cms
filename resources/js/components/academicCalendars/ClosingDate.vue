<script setup lang="ts">
import BaseDatePicker from '@/components/core/form/date/BaseDatePicker.vue';
</script>

<template>
    <BaseDatePicker
        input-id="closing_date"
        :label="$tChoice('academic_calendar.closing_date', 1)"
        v-bind="$attrs"
        :enable-time-picker="false"
        prevent-min-max-navigation
        :hide-offset-dates="true"
        :teleport="true"
    />
</template>
