<script setup lang="ts">
import AcademicYearComboSelect from '@/components/core/form/combobox/AcademicYearComboSelect.vue';
import ModeOfStudyComboSelect from '@/components/core/form/combobox/ModeOfStudyComboSelect.vue';
import { AcademicYear, ModeOfStudy } from '@/types/institution';
import { SelectOption } from '@/types/utils';

interface Props {
    academicYears: AcademicYear[];
    modesOfStudy: ModeOfStudy[];
    handleFilterChange: () => void;
}
defineProps<Props>();

const modeOfStudyModel = defineModel<SelectOption | null>('modeOfStudyModel');
const academicYearModel = defineModel<SelectOption | null>('academicYearModel');
</script>

<template>
    <div class="mt-4 flex w-full items-center justify-between space-x-4">
        <AcademicYearComboSelect
            :data="academicYears ?? []"
            v-model="academicYearModel!"
            @update:modelValue="handleFilterChange"
            :vertical-layout="false"
            :label-uppercase="true"
            :is-required="true"
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
