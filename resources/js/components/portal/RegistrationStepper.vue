<script setup lang="ts">
import { cn } from '@/lib/utils';
import { computed } from 'vue';

type EnrollmentPath = 'zimbabwean' | 'returning' | 'international';
type GuestStep = 'read-instructions' | 'verify-identity' | 'create-account' | 'choose-level' | 'pay-fee' | 'complete-application';

const props = withDefaults(
    defineProps<{
        activePath?: EnrollmentPath;
        currentStep?: GuestStep;
        highlightedStep?: string;
    }>(),
    {
        activePath: 'zimbabwean',
        currentStep: 'verify-identity',
    },
);

const instructionStep = { id: 'read-instructions', labelKey: 'trans.registration_instructions_step' } as const;

const newStudentSteps = [
    instructionStep,
    { id: 'verify-identity', labelKey: 'trans.portal_registration_step_verify_identity' },
    { id: 'create-account', labelKey: 'trans.portal_registration_step_create_account' },
    { id: 'choose-level', labelKey: 'trans.portal_registration_step_select_level' },
    { id: 'pay-fee', labelKey: 'trans.portal_registration_step_application_fee' },
    { id: 'complete-application', labelKey: 'trans.portal_registration_step_finish_application' },
] as const;

const returningSteps = [
    instructionStep,
    { id: 'lookup', labelKey: 'trans.portal_registration_step_verify_identity' },
    { id: 'login', labelKey: 'trans.portal_registration_step_sign_in' },
    { id: 'choose-level', labelKey: 'trans.portal_registration_step_select_level' },
    { id: 'track', labelKey: 'trans.portal_registration_step_track_application' },
] as const;

const internationalSteps = [
    instructionStep,
    { id: 'verify-passport', labelKey: 'trans.portal_registration_step_verify_identity' },
    { id: 'create-account', labelKey: 'trans.portal_registration_step_create_account' },
    { id: 'choose-level', labelKey: 'trans.portal_registration_step_select_level' },
    { id: 'complete-application', labelKey: 'trans.portal_registration_step_finish_application' },
] as const;

const steps = computed(() => {
    if (props.activePath === 'returning') {
        return returningSteps;
    }
    if (props.activePath === 'international') {
        return internationalSteps;
    }
    return newStudentSteps;
});

const activeStepId = computed(() => props.highlightedStep ?? props.currentStep);

const stepIndex = (id: string) => steps.value.findIndex((step) => step.id === id);

const isComplete = (id: string) => stepIndex(activeStepId.value) > stepIndex(id);

const isCurrent = (id: string) => activeStepId.value === id;

const emit = defineEmits<{
    navigate: [stepId: string];
}>();

const onStepClick = (id: string) => {
    if (isComplete(id)) {
        emit('navigate', id);
    }
};
</script>

<template>
    <nav aria-label="Registration progress" class="mb-6 w-full">
        <ol class="flex w-full items-start">
            <li
                v-for="(step, index) in steps"
                :key="step.id"
                class="flex flex-1 flex-col items-center"
                :aria-current="isCurrent(step.id) ? 'step' : undefined"
            >
                <div class="flex w-full items-center">
                    <span v-if="index > 0" class="h-px flex-1 bg-border" aria-hidden="true" />
                    <component
                        :is="isComplete(step.id) ? 'button' : 'span'"
                        :type="isComplete(step.id) ? 'button' : undefined"
                        class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full text-xs font-semibold"
                        :class="
                            cn(
                                isCurrent(step.id) && 'bg-primary text-primary-foreground',
                                isComplete(step.id) &&
                                    'cursor-pointer bg-primary/15 text-primary transition-colors hover:bg-primary/25 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary focus-visible:ring-offset-2',
                                !isCurrent(step.id) && !isComplete(step.id) && 'bg-muted text-muted-foreground',
                            )
                        "
                        :aria-label="isComplete(step.id) ? $t(step.labelKey) : undefined"
                        @click="onStepClick(step.id)"
                    >
                        {{ index + 1 }}
                    </component>
                    <span v-if="index < steps.length - 1" class="h-px flex-1 bg-border" aria-hidden="true" />
                </div>
                <span v-if="isCurrent(step.id)" class="mt-2 px-1 text-center text-xs font-medium text-foreground">
                    {{ $t(step.labelKey) }}
                </span>
            </li>
        </ol>
    </nav>
</template>
