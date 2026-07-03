<script setup lang="ts">
import BaseCombobox from '@/components/core/form/combobox/BaseCombobox.vue';
import { useAcademicStaffOptions } from '@/composables/institution/useAcademicStaffOptions';
import { SelectOption } from '@/types/utils';
import { computed, toRef } from 'vue';

interface Props {
    institutionDepartmentId: number | string;
    error?: string;
    isRequired?: boolean;
    label?: string;
    placeholder?: string;
}

const props = withDefaults(defineProps<Props>(), {
    isRequired: false,
});

const staffIdModel = defineModel<number | null>({ default: null });

const { options, isLoading } = useAcademicStaffOptions(toRef(props, 'institutionDepartmentId'));

const selectedOption = computed({
    get: (): SelectOption | null =>
        options.value.find((option) => Number(option.value) === staffIdModel.value) ?? null,
    set: (option: SelectOption | SelectOption[] | null | undefined): void => {
        if (Array.isArray(option)) {
            staffIdModel.value = option[0] != null ? Number(option[0].value) : null;

            return;
        }

        staffIdModel.value = option != null ? Number(option.value) : null;
    },
});
</script>

<template>
    <BaseCombobox
        v-model="selectedOption"
        :label="label ?? $tChoice('trans.staff', 1)"
        :placeholder="placeholder ?? $t('academic_calendar.assign_tutor')"
        :options="options"
        :is-loading="isLoading"
        :is-required="isRequired"
        :error="error"
        :is-clearable="true"
    />
</template>
