<script setup lang="ts">
import ModeOfStudyComboSelect from '@/components/core/form/combobox/ModeOfStudyComboSelect.vue';
import BaseContextField from '@/components/core/layout/BaseContextField.vue';
import BaseFilterBar from '@/components/core/layout/BaseFilterBar.vue';
import { ModeOfStudy } from '@/types/institution';
import { SelectOption } from '@/types/utils';

interface Props {
    academicYearOptions: SelectOption[];
    modesOfStudy: ModeOfStudy[];
    handleFilterChange: () => void;
}
defineProps<Props>();

const academicYearModel = defineModel<SelectOption | null>('academicYearModel');
const modeOfStudyModel = defineModel<SelectOption | null>('modeOfStudyModel');
</script>

<template>
    <BaseFilterBar
        bordered
        :title="$t('trans.ui_class_filters')"
        class="mt-4"
        :columns="2"
    >
        <BaseContextField
            :label="$tChoice('academic_calendar.calendar_year', 1)"
            icon="calendar"
            required
        >
            <BaseCombobox
                label=""
                :options="academicYearOptions ?? []"
                v-model="academicYearModel"
                :is-required="true"
                @update:modelValue="handleFilterChange"
            />
        </BaseContextField>

        <BaseContextField
            :label="$tChoice('trans.mode_of_study', 1)"
            icon="graduation_cape"
            required
        >
            <ModeOfStudyComboSelect
                label=""
                :data="modesOfStudy ?? []"
                v-model="modeOfStudyModel!"
                @update:modelValue="handleFilterChange"
                :is-required="true"
            />
        </BaseContextField>
    </BaseFilterBar>
</template>
