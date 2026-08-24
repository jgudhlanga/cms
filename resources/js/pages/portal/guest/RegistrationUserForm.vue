<script setup lang="ts">
import RegistrationStepper from '@/components/portal/RegistrationStepper.vue';
import type { StepperVariant } from '@/components/portal/RegistrationStepper.vue';
import RegistrationIntentSummary from '@/components/portal/RegistrationIntentSummary.vue';
import PortalApplicationIntakeBanner from '@/components/portal/PortalApplicationIntakeBanner.vue';
import { useUtils } from '@/composables/core/useUtils';
import { useEnrollmentRegistration, type EnrollmentLookupResult, type ReturningLookupType } from '@/composables/students/useEnrollmentRegistration';
import { useGuestPortal } from '@/composables/students/useGuestPortal';
import { useRegistrationAvailability } from '@/composables/students/useRegistrationAvailability';
import { useRegistrationStepNavigation } from '@/composables/students/useRegistrationStepNavigation';
import { clearFormErrors } from '@/lib/forms';
import RegistrationAccountForm from '@/pages/portal/guest/components/RegistrationAccountForm.vue';
import RegistrationBrandHeader from '@/pages/portal/guest/components/RegistrationBrandHeader.vue';
import RegistrationIdentityStep from '@/pages/portal/guest/components/RegistrationIdentityStep.vue';
import RegistrationInstructionsStep from '@/pages/portal/guest/components/RegistrationInstructionsStep.vue';
import RegistrationPathSelector from '@/pages/portal/guest/components/RegistrationPathSelector.vue';
import RegistrationGuide from '@/pages/portal/guest/RegistrationGuide.vue';
import { useCreateUserFormStore, type RegistrationPath } from '@/store/portal/useCreateUserFormStore';
import { CreateApplicationUserParams } from '@/types/portal';
import { IntakePeriod } from '@/types/institution';
import { Head, router, useForm } from '@inertiajs/vue3';
import { trans } from 'laravel-vue-i18n';
import { storeToRefs } from 'pinia';
import { computed, onMounted, ref, watch } from 'vue';

type EnrollmentPath = RegistrationPath | 'returning';
type Step = 'identity' | 'account';
type WizardPhase = 'instructions' | 'registration';

type IntentSummary = {
    track?: string | null;
    trackLabel?: string | null;
    continuousFocus?: string | null;
    levelName?: string | null;
    intakeName?: string | null;
};

const props = withDefaults(
    defineProps<{
        openIntakePeriods?: IntakePeriod[];
        singleIntakeName?: string | null;
        openIntakeNames?: string | null;
        intentSummary?: IntentSummary | null;
        eligibilityComplete?: boolean;
        startAtIdentity?: boolean;
        requireEligibilityFirst?: boolean;
        stepperVariant?: StepperVariant;
        requiresFee?: boolean;
        lockedIdentity?: {
            identityType?: 'zimbabwean' | 'international' | null;
            idNumber?: string | null;
            passportNumber?: string | null;
        } | null;
    }>(),
    {
        stepperVariant: 'regular',
        requiresFee: false,
        lockedIdentity: null,
    },
);

const { createPortalUser } = useGuestPortal();
const { navigateTo, formatZimIdNumber, isZimbabweanNationalId } = useUtils();
const { redirectIfClosed } = useRegistrationAvailability();
const { checkNationalId, checkPassport, lookupReturning } = useEnrollmentRegistration();
const { navigateToRegistrationStep } = useRegistrationStepNavigation();

const store = useCreateUserFormStore();
const {
    email,
    first_name,
    middle_name,
    last_name,
    password,
    password_confirmation,
    id_number,
    passport_number,
    registration_path,
    acknowledged_advert,
} = storeToRefs(store);

const wizardPhase = ref<WizardPhase>(
    props.startAtIdentity || acknowledged_advert.value ? 'registration' : 'instructions',
);
const showInstructionsValidationHint = ref(false);
const identityLocked = computed(() => props.lockedIdentity != null);
const lockedPath = computed<EnrollmentPath>(() =>
    props.lockedIdentity?.identityType === 'international' ? 'international' : 'zimbabwean',
);
const activePath = ref<EnrollmentPath>(identityLocked.value ? lockedPath.value : 'zimbabwean');
const step = ref<Step>(identityLocked.value ? 'account' : 'identity');
const passwordMatches = ref(true);
const isChecking = ref(false);
const lookupError = ref<string | null>(null);
const duplicateResult = ref<EnrollmentLookupResult | null>(null);
const returningLookupType = ref<ReturningLookupType>('id_number');
const returningLookupValue = ref('');

if (identityLocked.value) {
    registration_path.value = lockedPath.value === 'international' ? 'international' : 'zimbabwean';
    if (lockedPath.value === 'international') {
        passport_number.value = props.lockedIdentity?.passportNumber ?? '';
        id_number.value = '';
    } else {
        id_number.value = props.lockedIdentity?.idNumber ?? '';
        passport_number.value = '';
    }
}

const form = useForm<CreateApplicationUserParams & { registration_path: RegistrationPath }>({
    password_confirmation: '',
    email: '',
    first_name: '',
    middle_name: '',
    last_name: '',
    password: '',
    id_number: '',
    passport_number: '',
    registration_path: 'zimbabwean',
    acknowledged_advert: false,
});

const isReturning = computed(() => activePath.value === 'returning');
const isInternational = computed(() => activePath.value === 'international');
const showIdentityStep = computed(
    () => !identityLocked.value && (isReturning.value || step.value === 'identity'),
);
const existingRecordBlocked = computed(() => duplicateResult.value?.found === true && !isReturning.value);
const returningRecordFound = computed(() => isReturning.value && duplicateResult.value?.found === true);
const isInstructionsPhase = computed(() => wizardPhase.value === 'instructions' || !acknowledged_advert.value);

const pathOptions: { id: EnrollmentPath; labelKey: string }[] = [
    { id: 'zimbabwean', labelKey: 'trans.enrollment_path_new' },
    { id: 'returning', labelKey: 'trans.enrollment_path_returning' },
    { id: 'international', labelKey: 'trans.enrollment_path_international' },
];

const visiblePathOptions = computed(() => {
    if (!identityLocked.value) {
        return pathOptions;
    }

    return pathOptions.filter((option) => option.id === lockedPath.value);
});

const stepperHighlight = computed(() => {
    if (isInstructionsPhase.value) {
        return 'read-instructions';
    }

    if (isReturning.value) {
        return 'lookup';
    }
    if (step.value === 'account') {
        return 'create-account';
    }
    if (isInternational.value) {
        return 'verify-passport';
    }
    return 'verify-identity';
});

onMounted(() => {
    redirectIfClosed();

    if (props.startAtIdentity) {
        acknowledged_advert.value = true;
        wizardPhase.value = 'registration';
        step.value = identityLocked.value ? 'account' : 'identity';
        return;
    }

    // Always show Instructions on this page so stepper back-nav works.
    // Continue → Path is handled by completeInstructions().
    wizardPhase.value = 'instructions';
});

watch(acknowledged_advert, (acknowledged) => {
    if (!acknowledged && !props.startAtIdentity) {
        wizardPhase.value = 'instructions';
    }
});

const goToEligibilityFlow = () => {
    router.visit(route('portal.register.track'));
};

const goToProgrammeStep = () => {
    router.visit(route('portal.register.programme'));
};

const completeInstructions = () => {
    if (!acknowledged_advert.value) {
        showInstructionsValidationHint.value = true;
        return;
    }

    showInstructionsValidationHint.value = false;

    if (props.requireEligibilityFirst && !props.eligibilityComplete && activePath.value !== 'returning') {
        goToEligibilityFlow();
        return;
    }

    wizardPhase.value = 'registration';
};

const resetLookupState = () => {
    duplicateResult.value = null;
    lookupError.value = null;
};

const switchPath = (path: EnrollmentPath) => {
    if (identityLocked.value) {
        return;
    }

    activePath.value = path;
    registration_path.value = path === 'international' ? 'international' : 'zimbabwean';
    step.value = 'identity';
    resetLookupState();
    returningLookupValue.value = '';

    if (
        props.requireEligibilityFirst &&
        !props.eligibilityComplete &&
        !props.startAtIdentity &&
        path !== 'returning' &&
        wizardPhase.value === 'registration'
    ) {
        goToEligibilityFlow();
    }
};

watch(id_number, (value) => {
    if (!value || isReturning.value) return;
    id_number.value = formatZimIdNumber(value) ?? value;
});

const onReturningLookupValueUpdate = (value: string) => {
    returningLookupValue.value =
        returningLookupType.value === 'id_number' ? (formatZimIdNumber(value) ?? value) : value;
    lookupError.value = null;
};

const validateIdentityInput = (): boolean => {
    if (isReturning.value) {
        if (!returningLookupValue.value.trim()) {
            lookupError.value = 'Please enter a search value.';
            return false;
        }
        if (returningLookupType.value === 'id_number' && !isZimbabweanNationalId(returningLookupValue.value)) {
            lookupError.value = trans('trans.enrollment_invalid_national_id');
            return false;
        }
        return true;
    }

    if (isInternational.value) {
        if (!passport_number.value?.trim()) {
            lookupError.value = 'Please enter your passport number.';
            return false;
        }
        return true;
    }

    if (!id_number.value?.trim()) {
        lookupError.value = 'Please enter your National ID number.';
        return false;
    }

    if (!isZimbabweanNationalId(id_number.value)) {
        lookupError.value = trans('trans.enrollment_invalid_national_id');
        return false;
    }

    return true;
};

const handleIdentityContinue = async () => {
    lookupError.value = null;
    resetLookupState();

    if (!validateIdentityInput()) {
        return;
    }

    isChecking.value = true;
    try {
        if (isReturning.value) {
            const value =
                returningLookupType.value === 'id_number'
                    ? (formatZimIdNumber(returningLookupValue.value) ?? returningLookupValue.value)
                    : returningLookupValue.value.trim();
            duplicateResult.value = await lookupReturning(returningLookupType.value, value);
            return;
        }

        if (isInternational.value) {
            duplicateResult.value = await checkPassport(passport_number.value ?? '');
        } else {
            duplicateResult.value = await checkNationalId(id_number.value ?? '');
        }

        if (duplicateResult.value?.found) {
            return;
        }

        step.value = 'account';
    } catch {
        lookupError.value = 'Unable to verify your details. Please try again.';
    } finally {
        isChecking.value = false;
    }
};

const updateForm = () => {
    form.password_confirmation = password_confirmation.value;
    form.email = email.value;
    form.first_name = first_name.value;
    form.middle_name = middle_name.value ?? '';
    form.last_name = last_name.value ?? '';
    form.password = password.value;
    form.id_number = identityLocked.value
        ? (props.lockedIdentity?.idNumber ?? id_number.value ?? '')
        : (id_number.value ?? '');
    form.passport_number = identityLocked.value
        ? (props.lockedIdentity?.passportNumber ?? passport_number.value ?? '')
        : (passport_number.value ?? '');
    form.registration_path = isInternational.value ? 'international' : 'zimbabwean';
    form.acknowledged_advert = acknowledged_advert.value;
};

const submitForm = () => {
    updateForm();
    if (password_confirmation.value !== password.value) {
        passwordMatches.value = false;
        return;
    }
    passwordMatches.value = true;
    createPortalUser(form, isInternational.value ? 'international' : 'zimbabwean');
};

const continueToLogin = () => {
    navigateTo(route('login'));
};

const navigateToStep = (stepId: string) => {
    if (stepId === 'read-instructions') {
        wizardPhase.value = 'instructions';
        return;
    }

    if (stepId === 'verify-identity' || stepId === 'lookup' || stepId === 'verify-passport') {
        wizardPhase.value = 'registration';
        step.value = 'identity';
        return;
    }

    if (stepId === 'create-account') {
        wizardPhase.value = 'registration';
        step.value = 'account';
        return;
    }

    navigateToRegistrationStep(stepId);
};

const clearFormError = (field: string) => {
    clearFormErrors(form, field);
};
</script>

<template>
    <Head :title="$t('trans.application_form')" />
    <div class="min-h-svh bg-background">
        <div class="flex min-h-svh flex-col lg:flex-row">
            <div class="flex w-full flex-1 flex-col p-4 pt-2 sm:p-6 md:pt-6 lg:w-[62%] lg:min-w-0 lg:p-10 xl:w-[65%] xl:p-12 2xl:w-[68%]">
                <div class="mx-auto flex w-full max-w-2xl flex-1 flex-col">
                    <RegistrationBrandHeader />

                    <RegistrationStepper
                        :active-path="activePath"
                        :highlighted-step="stepperHighlight"
                        :stepper-variant="stepperVariant"
                        :requires-fee="requiresFee"
                        @navigate="navigateToStep"
                    />

                    <RegistrationIntentSummary v-if="intentSummary" :summary="intentSummary" />

                    <PortalApplicationIntakeBanner
                        :intake-name="props.singleIntakeName"
                        :open-intake-names="props.openIntakeNames"
                    />

                    <div class="rounded-2xl border border-border bg-card p-5 text-card-foreground shadow-md dark:shadow-sm sm:p-8 lg:p-10">
                        <RegistrationInstructionsStep
                            v-if="isInstructionsPhase"
                            v-model:acknowledged-advert="acknowledged_advert"
                            :show-validation-hint="showInstructionsValidationHint"
                            @continue="completeInstructions"
                        />

                        <template v-else>
                            <RegistrationPathSelector
                                :active-path="activePath"
                                :path-options="visiblePathOptions"
                                @switch-path="switchPath"
                            />

                            <div
                                v-if="identityLocked"
                                class="mb-4 rounded-lg border border-border bg-muted/30 p-3 text-sm text-muted-foreground"
                            >
                                <p v-if="lockedPath === 'international'">
                                    <span class="font-medium text-foreground">{{ $t('trans.passport_number') }}:</span>
                                    {{ lockedIdentity?.passportNumber }}
                                </p>
                                <p v-else>
                                    <span class="font-medium text-foreground">{{ $t('trans.id_number') }}:</span>
                                    {{ lockedIdentity?.idNumber }}
                                </p>
                            </div>

                            <RegistrationIdentityStep
                                v-if="showIdentityStep"
                                :active-path="activePath"
                                v-model:returning-lookup-type="returningLookupType"
                                :returning-lookup-value="returningLookupValue"
                                :id-number="id_number ?? ''"
                                :passport-number="passport_number ?? ''"
                                :duplicate-result="duplicateResult"
                                :existing-record-blocked="existingRecordBlocked"
                                :returning-record-found="returningRecordFound"
                                :lookup-error="lookupError"
                                :is-checking="isChecking"
                                :form-errors="form.errors"
                                @update:returning-lookup-value="onReturningLookupValueUpdate"
                                @update:id-number="id_number = $event"
                                @update:passport-number="passport_number = $event"
                                @clear-error="clearFormError"
                                @continue="handleIdentityContinue"
                                @switch-path="switchPath"
                                @continue-login="continueToLogin"
                            />

                            <RegistrationAccountForm
                                v-else
                                :first-name="first_name ?? ''"
                                :middle-name="middle_name ?? ''"
                                :last-name="last_name ?? ''"
                                :email="email"
                                :password="password"
                                :password-confirmation="password_confirmation"
                                :processing="form.processing"
                                :errors="form.errors"
                                :password-matches="passwordMatches"
                                @update:first-name="first_name = $event"
                                @update:middle-name="middle_name = $event"
                                @update:last-name="last_name = $event"
                                @update:email="email = $event"
                                @update:password="password = $event"
                                @update:password-confirmation="password_confirmation = $event"
                                @clear-error="clearFormError"
                                @back="identityLocked ? goToProgrammeStep() : (step = 'identity')"
                                @submit="submitForm"
                            />
                        </template>

                        <p class="mt-6 text-center text-sm text-muted-foreground">
                            {{ $t('trans.ui_already_have_an_account') }}
                            <button
                                type="button"
                                class="font-medium text-primary underline underline-offset-4 hover:underline"
                                @click="continueToLogin"
                            >
                                {{ $t('trans.ui_log_in') }}
                            </button>
                        </p>
                    </div>
                </div>
            </div>
            <RegistrationGuide
                :active-path="activePath"
                :highlighted-step="stepperHighlight"
                :stepper-variant="stepperVariant"
                :requires-fee="requiresFee"
            />
        </div>
    </div>
</template>
