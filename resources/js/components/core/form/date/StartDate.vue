<script setup lang="ts">
import BaseDatePicker from './BaseDatePicker.vue';
</script>

<template>
    <base-date-picker
        path="start_date"
        :label="$t('commencementDate')"
        v-bind="$attrs"
    />
</template>
