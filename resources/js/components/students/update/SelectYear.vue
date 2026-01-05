<script setup lang="ts">
import BaseDatePicker from '@/components/core/form/date/BaseDatePicker.vue';

interface Props {
    inputId: string;
}

defineProps<Props>();
</script>

<template>
    <BaseDatePicker
        :input-id="inputId"
        type="year"
        class="w-50"
        year-picker
        label=""
        v-bind="$attrs"
        :min-date="new Date(1950, 0, 1)"
        :max-date="new Date()"
        :hide-offset-dates="true"
        :teleport="true"
    />
</template>
