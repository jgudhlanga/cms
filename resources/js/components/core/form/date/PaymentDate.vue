<script setup lang="ts">
import BaseDatePicker from './BaseDatePicker.vue';

const currentDate = new Date();
const sixteenYearsAgo = new Date(currentDate);
sixteenYearsAgo.setFullYear(currentDate.getFullYear() - 15);
</script>

<template>
    <BaseDatePicker
        input-id="payment_date"
        label="Payment Date"
        v-bind="$attrs"
        :enable-time-picker="false"
        prevent-min-max-navigation
        :max-date="new Date()"
        :hide-offset-dates="true"
        :teleport="true"
    />
</template>
