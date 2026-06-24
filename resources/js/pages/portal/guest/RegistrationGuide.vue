<script setup lang="ts">
import { cn } from '@/lib/utils';
import { computed } from 'vue';

type EnrollmentPath = 'zimbabwean' | 'returning' | 'international';

const props = defineProps<{
    activePath: EnrollmentPath;
    highlightedStep: string;
}>();

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

const stepIndex = (id: string) => steps.value.findIndex((step) => step.id === id);

const isComplete = (id: string) => stepIndex(props.highlightedStep) > stepIndex(id);

const isCurrent = (id: string) => props.highlightedStep === id;

const isInstructions = computed(() => props.highlightedStep === 'read-instructions');

const isIdentityStep = computed(() =>
    ['verify-identity', 'lookup', 'verify-passport'].includes(props.highlightedStep),
);

const isCreateAccount = computed(() => props.highlightedStep === 'create-account');

const headerTitleKey = computed(() => {
    if (isInstructions.value) {
        return 'trans.registration_instructions_title';
    }
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
    if (isInstructions.value) {
        return 'trans.registration_instructions_subtitle';
    }
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
    if (isInstructions.value) {
        return [
            { key: 'trans.ui_college_advert_warning', variant: 'notice' },
            { key: 'trans.ui_ecocash_users_payment_device_warning', variant: 'notice' },
            { key: 'trans.enrollment_enter_national_id' },
            { key: 'trans.email' },
        ];
    }

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

const instructionNotices = computed((): GuideItem[] => {
    if (!isInstructions.value) {
        return [];
    }

    return [
        { key: 'trans.ui_college_advert_warning', variant: 'notice' },
        { key: 'trans.ui_ecocash_users_payment_device_warning', variant: 'notice' },
    ];
});

const instructionRequirements = computed((): GuideItem[] => {
    if (!isInstructions.value) {
        return [];
    }

    return [{ key: 'trans.enrollment_enter_national_id' }, { key: 'trans.email' }];
});
</script>

<template>
    <div class="hidden min-h-svh items-center border-l border-border bg-muted/30 py-10 text-foreground lg:flex lg:flex-1">
        <div class="mx-auto max-w-sm space-y-6 p-6 xl:max-w-md">
            <header>
                <h2 class="text-lg font-semibold text-foreground">{{ $t(headerTitleKey) }}</h2>
                <p class="mt-1 text-sm text-muted-foreground">{{ $t(headerSubtitleKey) }}</p>
            </header>

            <section v-if="isInstructions" class="rounded-xl border border-border/70 bg-background/50 p-4">
                <p class="text-sm text-muted-foreground">{{ $t('trans.registration_guide_after_instructions') }}</p>
                <p class="mt-3 text-xs text-muted-foreground">{{ $t('trans.registration_instructions_time_estimate') }}</p>
            </section>

            <section v-if="isInstructions">
                <h3 class="text-sm font-medium text-foreground">{{ $t('trans.enrollment_important_notices') }}</h3>
                <ul class="mt-3 space-y-3 text-sm text-muted-foreground">
                    <li
                        v-for="(item, index) in instructionNotices"
                        :key="`${item.key}-${index}`"
                        class="rounded-md border border-border/60 bg-background/60 p-3"
                    >
                        {{ $t(item.key) }}
                    </li>
                </ul>
            </section>

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
                                    isComplete(step.id) && 'bg-primary/15 text-primary',
                                    !isCurrent(step.id) && !isComplete(step.id) && 'bg-muted text-muted-foreground',
                                )
                            "
                        >
                            {{ index + 1 }}
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
