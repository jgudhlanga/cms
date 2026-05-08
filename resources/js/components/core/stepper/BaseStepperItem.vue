<script setup lang="ts">
import BaseStepperHeading from '@/components/core/stepper/BaseStepperHeading.vue';
import BaseStepperSeparator from '@/components/core/stepper/BaseStepperSeparator.vue';
import BaseStepperTrigger from '@/components/core/stepper/BaseStepperTrigger.vue';
import { StepperItem } from '@/components/ui/stepper';
import { Step } from '@/types/forms';
interface Props {
    steps: Step[];
}
defineProps<Props>();
</script>
<template>
    <div class="flex-start flex w-full justify-between gap-2">
        <StepperItem
            v-for="step in steps"
            :key="step.step"
            v-slot="{ state }"
            class="relative flex w-full flex-col items-center justify-center"
            :step="step.step"
        >
            <BaseStepperSeparator v-if="step.step !== steps[steps.length - 1].step" />
            <BaseStepperTrigger :state="state" />
            <BaseStepperHeading :state="state" :title="$t(step.title)" :description="$t(step.description)" />
        </StepperItem>
    </div>
</template>
