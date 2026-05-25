<script setup lang="ts">
import BaseCombobox from '@/components/core/form/combobox/BaseCombobox.vue';
import { computed } from 'vue';

interface Props {
    showLabel?: boolean
}

withDefaults(defineProps<Props>(), {
    showLabel: true,
})

const currentYear = new Date().getFullYear();
const options = computed(() =>
    Array.from({ length: 3 }, (_, index) => {
        const year = currentYear + index;

        return {
            label: String(year),
            value: String(year),
        };
    }),
);
</script>

<template>
    <BaseCombobox :label="showLabel ? $tChoice('academic_calendar.calendar_year', 1) : ''" :options="options" v-bind="$attrs" />
</template>
