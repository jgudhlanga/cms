<script setup lang="ts">
import BaseCombobox from '@/components/core/form/combobox/BaseCombobox.vue';
import { useAcademicYearOptionsByCalendarType } from '@/composables/academicCalendars/useAcademicYearOptionsByCalendarType';
import type { SelectOption } from '@/types/utils';
import { computed, onMounted, watch } from 'vue';

interface Props {
    calendarType: 'term' | 'semester' | 'abma';
    error?: string;
    isRequired?: boolean;
    label?: string;
    placeholder?: string;
}

const props = withDefaults(defineProps<Props>(), {
    isRequired: false,
});

const academicYearOptionIdModel = defineModel<number | null>({ default: null });

const { yearOptions, yearOptionsLoading, loadYearOptions } = useAcademicYearOptionsByCalendarType();

onMounted(() => {
    void loadYearOptions(props.calendarType);
});

watch(
    () => props.calendarType,
    (calendarType) => {
        void loadYearOptions(calendarType);
    },
);

const selectedOption = computed({
    get: (): SelectOption | null =>
        yearOptions.value.find((option) => Number(option.value) === academicYearOptionIdModel.value) ?? null,
    set: (option: SelectOption | SelectOption[] | null | undefined): void => {
        if (Array.isArray(option)) {
            academicYearOptionIdModel.value = option[0] != null ? Number(option[0].value) : null;

            return;
        }

        academicYearOptionIdModel.value = option != null ? Number(option.value) : null;
    },
});
</script>

<template>
    <BaseCombobox
        v-model="selectedOption"
        :label="''"
        :placeholder="placeholder ?? $t('trans.select')"
        :options="yearOptions"
        :is-loading="yearOptionsLoading"
        :is-required="isRequired"
        :error="error"
    />
</template>
