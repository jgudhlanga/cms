<script setup lang="ts">
import BaseDatePicker from './BaseDatePicker.vue';
</script>

<template>
    <BaseDatePicker input-id="date_range" :label="$t('Date')" v-bind="$attrs" :enable-time-picker="false" prevent-min-max-navigation range />
</template>
