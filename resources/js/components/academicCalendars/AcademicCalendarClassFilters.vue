<script setup lang="ts">
import ModeOfStudyComboSelect from '@/components/core/form/combobox/ModeOfStudyComboSelect.vue';
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
    <div class="mt-4 flex w-full items-center justify-between space-x-4">
        <BaseCombobox
            :label="$tChoice('academic_calendar.calendar_year', 1)"
            :options="academicYearOptions ?? []"
            v-model="academicYearModel"
            :label-uppercase="true"
            :vertical-layout="false"
            :is-required="true"
            @update:modelValue="handleFilterChange"
            class="w-full"
        />
        <ModeOfStudyComboSelect
            :data="modesOfStudy ?? []"
            v-model="modeOfStudyModel!"
            @update:modelValue="handleFilterChange"
            :vertical-layout="false"
            :label-uppercase="true"
            :is-required="true"
            class="w-full"
        />
    </div>
</template> 
