<script setup lang="ts">
import IntakePeriodComboSelect from '@/components/core/form/combobox/IntakePeriodComboSelect.vue';
import ModeOfStudyComboSelect from '@/components/core/form/combobox/ModeOfStudyComboSelect.vue';
import { IconName, icons } from '@/lib/icons';
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
    <div
        class="flex flex-col gap-3 rounded-lg border border-border/60 bg-muted/20 p-3 sm:flex-row sm:flex-wrap sm:items-center sm:gap-x-6 sm:gap-y-2"
        role="group"
        :aria-label="$tChoice('trans.enrolment', 2)"
    >
        <div class="flex min-w-0 flex-1 items-center gap-2 sm:min-w-[220px] sm:max-w-sm">
            <component :is="icons[IconName.calendar]" class="h-4 w-4 shrink-0 text-muted-foreground" aria-hidden="true" />
            <span class="shrink-0 text-sm font-medium text-muted-foreground">{{ $tChoice('trans.intake_period', 1) }}</span>
            <IntakePeriodComboSelect
                :data="intakePeriods ?? []"
                label=""
                v-model="intakePeriodModel"
                :vertical-layout="false"
                :is-required="true"
                width-class="w-full"
                class="min-w-0 flex-1"
                @update:modelValue="handleFilterChange"
            />
        </div>
        <div class="flex min-w-0 flex-1 items-center gap-2 sm:min-w-[220px] sm:max-w-sm">
            <component :is="icons[IconName.graduation_cape]" class="h-4 w-4 shrink-0 text-muted-foreground" aria-hidden="true" />
            <span class="shrink-0 text-sm font-medium text-muted-foreground">{{ $tChoice('trans.mode_of_study', 1) }}</span>
            <ModeOfStudyComboSelect
                :data="modesOfStudy ?? []"
                label=""
                v-model="modeOfStudyModel!"
                :vertical-layout="false"
                :is-required="true"
                width-class="w-full"
                class="min-w-0 flex-1"
                @update:modelValue="handleFilterChange"
            />
        </div>
    </div>
</template>
