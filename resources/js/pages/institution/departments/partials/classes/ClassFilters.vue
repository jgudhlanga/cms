<script setup lang="ts">
import IntakePeriodComboSelect from '@/components/core/form/combobox/IntakePeriodComboSelect.vue';
import ModeOfStudyComboSelect from '@/components/core/form/combobox/ModeOfStudyComboSelect.vue';
import { IntakePeriod, ModeOfStudy } from '@/types/institution';
import { SelectOption } from '@/types/utils';

interface Props {
    intakePeriods: IntakePeriod[];
    modesOfStudy: ModeOfStudy[];
    handleFilterChange: () => void;
}
defineProps<Props>();

const intakePeriodModel = defineModel<SelectOption | null>('intakePeriodModel');
const modeOfStudyModel = defineModel<SelectOption | null>('modeOfStudyModel');
</script>

<template>
    <div class="mt-4 flex w-full items-center justify-between space-x-4">
        <IntakePeriodComboSelect
            :data="intakePeriods ?? []"
            :label-uppercase="true"
            v-model="intakePeriodModel"
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
