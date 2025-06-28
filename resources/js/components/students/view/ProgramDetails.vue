<script setup lang="ts">
import BaseCard from '@/components/core/card/BaseCard.vue';
import LabelValue from '@/components/core/util/LabelValue.vue';
import { ProgramDetailView } from '@/types/students';
import { ValueAndLabel } from '@/types/utils';

interface Props {
    program: ProgramDetailView;
    title?: string;
    gridSize?: string;
}

const props = withDefaults(defineProps<Props>(), {
    gridSize: '4',
});
const { program } = props;

const programDetails: ValueAndLabel[] = [
    { transChoiceKey: 'trans.department', value: program?.department ?? '' },
    { transChoiceKey: 'trans.level', value: program?.level ?? '' },
    { transChoiceKey: 'trans.course', value: program?.course ?? '' },
];
</script>

<template>
    <BaseCard :title="title ? title : ''">
        <div :class="`grid grid-cols-1 gap-2 md:grid-cols-${gridSize}`">
            <LabelValue
                v-for="(detail, index) in programDetails"
                :key="index"
                :label="`${detail?.transKey ? $t(detail.transKey) : $tChoice(detail.transChoiceKey ?? '', 1)}`"
                :value="detail.value"
            />
        </div>
        <slot />
    </BaseCard>
</template>
