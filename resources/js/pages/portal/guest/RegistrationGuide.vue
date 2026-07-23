<script setup lang="ts">
import type { EnrollmentPath, GuestStep, StepperVariant } from '@/components/portal/RegistrationStepper.vue';
import { cn } from '@/lib/utils';
import { Check } from 'lucide-vue-next';
import { computed } from 'vue';

type StepDef = { id: GuestStep; labelKey: string };

const props = withDefaults(
    defineProps<{
        activePath: EnrollmentPath;
        highlightedStep: string;
        stepperVariant?: StepperVariant;
        requiresFee?: boolean;
    }>(),
    {
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

const withFee = (stepList: StepDef[]): StepDef[] => {
    if (!props.requiresFee) {
        return stepList;
    }

    const finishIndex = stepList.findIndex((step) => step.id === 'complete-application');
    if (finishIndex === -1) {
        return [...stepList, feeStep];
    }

    const copy = [...stepList];
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

    return withFee([instructionStep, pathStep, levelStep, programStep, identityStep, accountStep, finishStep]);
});

const stepIndex = (id: string) => steps.value.findIndex((step) => step.id === id);

const isComplete = (id: string) => stepIndex(props.highlightedStep) > stepIndex(id);

const isCurrent = (id: string) => props.highlightedStep === id;

const isInstructions = computed(() => props.highlightedStep === 'read-instructions');

const isIdentityStep = computed(() =>
    ['verify-identity', 'lookup', 'verify-passport'].includes(props.highlightedStep),
);

const isCreateAccount = computed(() => props.highlightedStep === 'create-account');

const headerTitleKey = computed(() => {
    if (isIdentityStep.value) {
        return 'trans.enrollment_step_identity';
    }
    if (isCreateAccount.value) {
        return 'trans.enrollment_step_account';
    }
    const current = steps.value.find((step) => step.id === props.highlightedStep);
    return current?.labelKey ?? 'trans.registration_what_you_need';
});

const headerSubtitleKey = computed(() => {
    if (props.highlightedStep === 'verify-identity') {
        return 'trans.registration_guide_identity_new_body';
    }
    if (props.highlightedStep === 'lookup') {
        return 'trans.registration_guide_identity_returning_body';
    }
    if (props.highlightedStep === 'verify-passport') {
        return 'trans.registration_guide_identity_international_body';
    }
    if (isCreateAccount.value) {
        return 'trans.registration_guide_create_account_body';
    }
    return 'trans.ui_follow_these_steps_to_complete_your_application';
});

type GuideItem = { key: string; variant?: 'notice' };

const currentStepItems = computed((): GuideItem[] => {
    if (props.highlightedStep === 'verify-identity') {
        return [
            { key: 'trans.enrollment_enter_national_id' },
            { key: 'trans.registration_guide_no_duplicate' },
            { key: 'trans.registration_guide_record_found' },
        ];
    }

    if (props.highlightedStep === 'lookup') {
        return [
            { key: 'trans.enrollment_enter_national_id' },
            { key: 'trans.enrollment_enter_student_number' },
            { key: 'trans.registration_guide_record_found' },
        ];
    }

    if (props.highlightedStep === 'verify-passport') {
        return [
            { key: 'trans.enrollment_enter_passport' },
            { key: 'trans.registration_guide_no_duplicate' },
            { key: 'trans.registration_guide_record_found' },
        ];
    }

    if (isCreateAccount.value) {
        return [
            { key: 'trans.first_name' },
            { key: 'trans.last_name' },
            { key: 'trans.email' },
            { key: 'trans.password' },
        ];
    }

    return [{ key: 'trans.ui_follow_these_steps_to_complete_your_application' }];
});

const currentStepSectionTitleKey = computed(() => {
    if (isIdentityStep.value || isCreateAccount.value) {
        return 'trans.registration_what_you_need';
    }
    return 'trans.registration_guide_what_happens_next';
});

const instructionRequirements = computed((): GuideItem[] => {
    if (!isInstructions.value) {
        return [];
    }

    return [{ key: 'trans.enrollment_enter_national_id' }, { key: 'trans.email' }];
});
</script>

<template>
    <div class="hidden min-h-svh items-start border-l border-border bg-muted/30 p-4 pt-2 text-foreground sm:p-6 md:pt-6 lg:flex lg:flex-1 lg:p-10 xl:p-12">
        <div class="mx-auto max-w-sm space-y-6 xl:max-w-md">
            <header v-if="!isInstructions">
                <h2 class="text-lg font-semibold text-foreground">{{ $t(headerTitleKey) }}</h2>
                <p class="mt-1 text-sm text-muted-foreground">{{ $t(headerSubtitleKey) }}</p>
            </header>

            <section v-if="isInstructions">
                <h3 class="text-sm font-medium text-foreground">{{ $t('trans.registration_what_you_need') }}</h3>
                <ul class="mt-3 space-y-3 text-sm text-muted-foreground">
                    <li v-for="(item, index) in instructionRequirements" :key="`${item.key}-${index}`" class="flex items-start gap-2">
                        <span class="mt-1.5 h-1.5 w-1.5 shrink-0 rounded-full bg-primary" aria-hidden="true" />
                        {{ $t(item.key) }}
                    </li>
                </ul>
            </section>

            <section v-else-if="currentStepItems.length">
                <h3 class="text-sm font-medium text-foreground">{{ $t(currentStepSectionTitleKey) }}</h3>
                <ul class="mt-3 space-y-3 text-sm text-muted-foreground">
                    <li
                        v-for="(item, index) in currentStepItems"
                        :key="`${item.key}-${index}`"
                        class="flex items-start gap-2"
                        :class="item.variant === 'notice' && 'rounded-md border border-border/60 bg-background/60 p-3'"
                    >
                        <span
                            v-if="item.variant !== 'notice'"
                            class="mt-1.5 h-1.5 w-1.5 shrink-0 rounded-full bg-primary"
                            aria-hidden="true"
                        />
                        {{ $t(item.key) }}
                    </li>
                </ul>
            </section>

            <section>
                <h3 class="text-sm font-medium text-foreground">{{ $t('trans.registration_guide_your_progress') }}</h3>
                <ol class="mt-3 space-y-2">
                    <li
                        v-for="(step, index) in steps"
                        :key="step.id"
                        class="flex items-center gap-3 text-sm"
                        :aria-current="isCurrent(step.id) ? 'step' : undefined"
                    >
                        <span
                            class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full text-xs font-semibold"
                            :class="
                                cn(
                                    isCurrent(step.id) && 'bg-primary text-primary-foreground',
                                    isComplete(step.id) && !isCurrent(step.id) && 'bg-primary/15 text-primary',
                                    !isCurrent(step.id) && !isComplete(step.id) && 'bg-muted text-muted-foreground',
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
                                    isCurrent(step.id) && 'font-medium text-foreground',
                                    isComplete(step.id) && 'text-primary',
                                    !isCurrent(step.id) && !isComplete(step.id) && 'text-muted-foreground',
                                )
                            "
                        >
                            {{ $t(step.labelKey) }}
                        </span>
                    </li>
                </ol>
            </section>
        </div>
    </div>
</template>
