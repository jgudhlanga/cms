<script setup lang="ts">
import BaseDatePicker from '@/components/core/form/date/BaseDatePicker.vue';

interface Props {
    showLabel?: boolean;
}

withDefaults(defineProps<Props>(), {
    showLabel: true,
});
</script>

<template>
    <BaseDatePicker
        input-id="opening_date"
        :label="showLabel ?  $tChoice('academic_calendar.opening_date', 1) : ''"
        v-bind="$attrs"
        :enable-time-picker="false"
        prevent-min-max-navigation
        :hide-offset-dates="true"
        :teleport="true"
    />
</template>
