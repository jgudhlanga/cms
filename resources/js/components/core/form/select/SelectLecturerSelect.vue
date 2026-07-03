<script setup lang="ts">
import BaseCombobox from '@/components/core/form/combobox/BaseCombobox.vue';
import { useAcademicStaffOptions } from '@/composables/institution/useAcademicStaffOptions';
import { clearFormErrors } from '@/lib/forms';
import { SelectOption } from '@/types/utils';
import { InertiaForm } from '@inertiajs/vue3';
import { computed, toRef } from 'vue';

interface Props {
    institutionDepartmentId: number | string;
    form?: InertiaForm<any>;
    error?: string;
    isRequired?: boolean;
    verticalLayout?: boolean;
    widthClass?: string;
    label?: string;
}

const props = withDefaults(defineProps<Props>(), {
    isRequired: false,
    verticalLayout: true,
    widthClass: 'w-full',
    label: undefined,
});

const staffIdsModel = defineModel<number[]>({ default: () => [] });

const { options, isLoading } = useAcademicStaffOptions(toRef(props, 'institutionDepartmentId'));

const selectedOptions = computed({
    get: () => options.value.filter((option) => staffIdsModel.value.includes(Number(option.value))),
    set: (selected: SelectOption[]) => {
        staffIdsModel.value = selected.map((option) => Number(option.value));
        if (props.form) {
            clearFormErrors(props.form, 'staff_ids');
        }
    },
});
</script>

<template>
    <BaseCombobox
        :label="label === undefined ? $tChoice('syllabus.lecturer', 2) : label"
        v-model="selectedOptions"
        :options="options"
        :is-loading="isLoading"
        :is-required="isRequired"
        :error="error"
        :vertical-layout="verticalLayout"
        :width-class="widthClass"
        multiple
    />
</template>
