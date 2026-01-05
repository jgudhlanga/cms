<script setup lang="ts">
import ModeOfStudyComboSelect from '@/components/core/form/combobox/ModeOfStudyComboSelect.vue';
import { AcademicCalendar } from '@/types/academic-calendar';
import { ModeOfStudy } from '@/types/institution';
import { SelectOption } from '@/types/utils';

interface Props {
    academicCalendars: AcademicCalendar[];
    modesOfStudy: ModeOfStudy[];
    handleFilterChange: () => void;
}
defineProps<Props>();

const academicCalendarModel = defineModel<SelectOption | null>('academicCalendarModel');
const modeOfStudyModel = defineModel<SelectOption | null>('modeOfStudyModel');
</script>

<template>
    <div class="mt-4 flex w-full items-center justify-between space-x-4">
        <AcademicCalendarComboSelect
            :data="academicCalendars ?? []"
            :label-uppercase="true"
            v-model="academicCalendarModel"
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
