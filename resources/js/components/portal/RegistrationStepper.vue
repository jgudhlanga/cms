<script setup lang="ts">
import { cn } from '@/lib/utils';
import { Check } from 'lucide-vue-next';
import { computed } from 'vue';

export type EnrollmentPath = 'zimbabwean' | 'returning' | 'international';
export type StepperVariant = 'regular' | 'sdp' | 'ojet' | 'apprentice';
export type GuestStep =
    | 'read-instructions'
    | 'choose-track'
    | 'choose-level'
    | 'choose-programme'
    | 'verify-identity'
    | 'create-account'
    | 'pay-fee'
    | 'complete-application'
    | 'lookup'
    | 'login'
    | 'verify-passport'
    | 'track';

type StepDef = { id: GuestStep; labelKey: string };

const props = withDefaults(
    defineProps<{
        activePath?: EnrollmentPath;
        currentStep?: GuestStep;
        highlightedStep?: string;
        stepperVariant?: StepperVariant;
        requiresFee?: boolean;
    }>(),
    {
        activePath: 'zimbabwean',
        currentStep: 'verify-identity',
        stepperVariant: 'regular',
        requiresFee: false,
    },
);

const instructionStep: StepDef = { id: 'read-instructions', labelKey: 'trans.registration_step_instructions' };

const pathStep: StepDef = { id: 'choose-track', labelKey: 'trans.registration_step_path' };
const levelStep: StepDef = { id: 'choose-level', labelKey: 'trans.registration_step_level' };
const programStep: StepDef = { id: 'choose-programme', labelKey: 'trans.registration_step_program' };
const identityStep: StepDef = { id: 'verify-identity', labelKey: 'trans.registration_step_identity' };
const passportStep: StepDef = { id: 'verify-passport', labelKey: 'trans.registration_step_identity' };
const accountStep: StepDef = { id: 'create-account', labelKey: 'trans.registration_step_account' };
const feeStep: StepDef = { id: 'pay-fee', labelKey: 'trans.registration_step_fee' };
const finishStep: StepDef = { id: 'complete-application', labelKey: 'trans.registration_step_finish' };

const withFee = (steps: StepDef[]): StepDef[] => {
    if (!props.requiresFee) {
        return steps;
    }

    const finishIndex = steps.findIndex((step) => step.id === 'complete-application');
    if (finishIndex === -1) {
        return [...steps, feeStep];
    }

    const copy = [...steps];
    copy.splice(finishIndex, 0, feeStep);
    return copy;
};

const returningSteps: StepDef[] = [
    instructionStep,
    { id: 'lookup', labelKey: 'trans.registration_step_identity' },
    { id: 'login', labelKey: 'trans.portal_registration_step_sign_in' },
    levelStep,
    { id: 'track', labelKey: 'trans.portal_registration_step_track_application' },
];

const steps = computed((): StepDef[] => {
    if (props.activePath === 'returning') {
        return returningSteps;
    }

    if (props.activePath === 'international') {
        const base: StepDef[] = [instructionStep, pathStep];
        if (props.stepperVariant !== 'sdp') {
            base.push(levelStep);
        }
        base.push(programStep, passportStep, accountStep, finishStep);
        return withFee(base);
    }

    if (props.stepperVariant === 'apprentice') {
        return [instructionStep, pathStep, levelStep, programStep, identityStep, accountStep, finishStep];
    }

    if (props.stepperVariant === 'sdp') {
        return withFee([instructionStep, pathStep, programStep, identityStep, accountStep, finishStep]);
    }

    // regular + ojet
    return withFee([instructionStep, pathStep, levelStep, programStep, identityStep, accountStep, finishStep]);
});

const activeStepId = computed(() => props.highlightedStep ?? props.currentStep);

const stepIndex = (id: string) => steps.value.findIndex((step) => step.id === id);

const isComplete = (id: string) => {
    const active = stepIndex(activeStepId.value);
    const target = stepIndex(id);
    if (active === -1 || target === -1) {
        return false;
    }
    return active > target;
};

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
    <nav aria-label="Registration progress" class="mb-4 w-full overflow-x-auto overflow-y-visible pt-1">
        <ol class="flex w-full items-start gap-0.5 pb-1">
            <li
                v-for="(step, index) in steps"
                :key="step.id"
                class="flex min-w-0 flex-1 flex-col items-center"
            >
                <button
                    type="button"
                    class="flex w-full flex-col items-center gap-1 disabled:cursor-default"
                    :disabled="!isComplete(step.id)"
                    :aria-current="isCurrent(step.id) ? 'step' : undefined"
                    @click="onStepClick(step.id)"
                >
                    <span
                        :class="
                            cn(
                                'flex size-6 shrink-0 items-center justify-center rounded-full text-[10px] font-semibold ring-1',
                                isCurrent(step.id) && 'bg-primary text-primary-foreground ring-primary',
                                isComplete(step.id) && !isCurrent(step.id) && 'bg-primary/15 text-primary ring-primary/30',
                                !isComplete(step.id) && !isCurrent(step.id) && 'bg-muted text-muted-foreground ring-border',
                            )
                        "
                    >
                        <Check
                            v-if="isComplete(step.id) && !isCurrent(step.id)"
                            class="size-3.5"
                            aria-hidden="true"
                        />
                        <template v-else>
                            {{ index + 1 }}
                        </template>
                    </span>
                    <span
                        :class="
                            cn(
                                'hidden max-w-full truncate text-center text-[10px] leading-tight sm:block',
                                isCurrent(step.id) ? 'font-medium text-foreground' : 'text-muted-foreground',
                                isComplete(step.id) && 'cursor-pointer hover:text-foreground',
                            )
                        "
                    >
                        {{ $t(step.labelKey) }}
                    </span>
                </button>
            </li>
        </ol>
    </nav>
</template>
