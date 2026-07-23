<script setup lang="ts">
import { cn } from '@/lib/utils';
import { Check } from 'lucide-vue-next';
import { computed } from 'vue';

export type ApplicationFormStep = 'personal' | 'contact' | 'next_of_kin' | 'programme';

const steps = [
    { id: 'personal' as const, labelKey: 'trans.personal_details' },
    { id: 'contact' as const, labelKey: 'trans.contact_details' },
    { id: 'next_of_kin' as const, labelKey: 'trans.next_of_kin' },
    { id: 'programme' as const, labelKey: 'trans.programs' },
];

const props = withDefaults(
    defineProps<{
        currentStep: ApplicationFormStep;
        compact?: boolean;
    }>(),
    {
        compact: false,
    },
);

const emit = defineEmits<{
    navigate: [stepId: ApplicationFormStep];
}>();

const currentIndex = computed(() => steps.findIndex((step) => step.id === props.currentStep));

const isComplete = (index: number) => index < currentIndex.value;
const isCurrent = (index: number) => index === currentIndex.value;

const onStepClick = (stepId: ApplicationFormStep, index: number) => {
    if (isComplete(index)) {
        emit('navigate', stepId);
    }
};
</script>

<template>
    <nav aria-label="Application progress" :class="compact ? 'mb-1.5 w-full' : 'mb-4 w-full'">
        <ol class="flex w-full items-center">
            <li
                v-for="(step, index) in steps"
                :key="step.id"
                class="flex flex-1 items-center"
                :aria-current="isCurrent(index) ? 'step' : undefined"
                :aria-label="isCurrent(index) ? $t(step.labelKey) : undefined"
            >
                <span v-if="index > 0" class="pointer-events-none h-px flex-1 bg-border" aria-hidden="true" />
                <component
                    :is="isComplete(index) ? 'button' : 'span'"
                    :type="isComplete(index) ? 'button' : undefined"
                    class="relative z-10 flex shrink-0 items-center justify-center rounded-full text-xs font-semibold"
                    :class="
                        cn(
                            compact ? 'h-8 w-8' : 'h-7 w-7',
                            isCurrent(index) && 'bg-primary text-primary-foreground',
                            isComplete(index) &&
                                'cursor-pointer bg-primary/15 text-primary transition-colors hover:bg-primary/25 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary focus-visible:ring-offset-2',
                            !isCurrent(index) && !isComplete(index) && 'bg-muted text-muted-foreground',
                        )
                    "
                    :aria-label="isComplete(index) ? $t(step.labelKey) : undefined"
                    @click="onStepClick(step.id, index)"
                >
                    <Check v-if="isComplete(index) && !isCurrent(index)" class="size-3.5" aria-hidden="true" />
                    <template v-else>
                        {{ index + 1 }}
                    </template>
                </component>
                <span v-if="index < steps.length - 1" class="pointer-events-none h-px flex-1 bg-border" aria-hidden="true" />
            </li>
        </ol>
        <p v-if="!compact && isCurrent(currentIndex)" class="mt-2 text-center text-xs font-medium text-foreground">
            {{ $t(steps[currentIndex].labelKey) }}
        </p>
    </nav>
</template>
