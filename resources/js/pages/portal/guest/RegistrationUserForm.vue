<script setup lang="ts">
import { BaseButton } from '@/components/core/button';
import ComingSoonAnimated from '@/components/core/util/ComingSoonAnimated.vue';
import { useUtils } from '@/composables/core/useUtils';
import { useEnrollmentRegistration, type EnrollmentLookupResult, type ReturningLookupType } from '@/composables/students/useEnrollmentRegistration';
import { useGuestPortal } from '@/composables/students/useGuestPortal';
import { ColorVariant } from '@/enums/colors';
import { clearFormErrors } from '@/lib/forms';
import RegistrationAccountForm from '@/pages/portal/guest/components/RegistrationAccountForm.vue';
import RegistrationAlerts from '@/pages/portal/guest/components/RegistrationAlerts.vue';
import RegistrationBrandHeader from '@/pages/portal/guest/components/RegistrationBrandHeader.vue';
import RegistrationIdentityStep from '@/pages/portal/guest/components/RegistrationIdentityStep.vue';
import RegistrationPathSelector from '@/pages/portal/guest/components/RegistrationPathSelector.vue';
import RegistrationGuide from '@/pages/portal/guest/RegistrationGuide.vue';
import { useCreateUserFormStore, type RegistrationPath } from '@/store/portal/useCreateUserFormStore';
import { CreateApplicationUserParams } from '@/types/portal';
import { Head, useForm } from '@inertiajs/vue3';
import { trans } from 'laravel-vue-i18n';
import { storeToRefs } from 'pinia';
import { computed, ref, watch } from 'vue';

type EnrollmentPath = RegistrationPath | 'returning';
type Step = 'identity' | 'account';

const { createPortalUser } = useGuestPortal();
const { navigateTo, isItTrue, formatZimIdNumber, isZimbabweanNationalId } = useUtils();
const { checkNationalId, checkPassport, lookupReturning } = useEnrollmentRegistration();

const store = useCreateUserFormStore();
const { email, first_name, last_name, password, password_confirmation, id_number, passport_number, registration_path } = storeToRefs(store);

const activePath = ref<EnrollmentPath>('zimbabwean');
const step = ref<Step>('identity');
const passwordMatches = ref(true);
const isChecking = ref(false);
const lookupError = ref<string | null>(null);
const duplicateResult = ref<EnrollmentLookupResult | null>(null);
const returningLookupType = ref<ReturningLookupType>('id_number');
const returningLookupValue = ref('');

const form = useForm<CreateApplicationUserParams & { registration_path: RegistrationPath }>({
    password_confirmation: '',
    email: '',
    first_name: '',
    last_name: '',
    password: '',
    id_number: '',
    passport_number: '',
    registration_path: 'zimbabwean',
});

const maintenanceMode = isItTrue(import.meta.env.VITE_MAINTENANCE_MODE);

const isReturning = computed(() => activePath.value === 'returning');
const isInternational = computed(() => activePath.value === 'international');
const showIdentityStep = computed(() => isReturning.value || step.value === 'identity');
const existingRecordBlocked = computed(() => duplicateResult.value?.found === true && !isReturning.value);
const returningRecordFound = computed(() => isReturning.value && duplicateResult.value?.found === true);

const pathOptions: { id: EnrollmentPath; labelKey: string }[] = [
    { id: 'zimbabwean', labelKey: 'trans.enrollment_path_new' },
    { id: 'returning', labelKey: 'trans.enrollment_path_returning' },
    { id: 'international', labelKey: 'trans.enrollment_path_international' },
];

const resetLookupState = () => {
    duplicateResult.value = null;
    lookupError.value = null;
};

const switchPath = (path: EnrollmentPath) => {
    activePath.value = path;
    registration_path.value = path === 'international' ? 'international' : 'zimbabwean';
    step.value = 'identity';
    resetLookupState();
    returningLookupValue.value = '';
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
    form.last_name = last_name.value ?? '';
    form.password = password.value;
    form.id_number = id_number.value ?? '';
    form.passport_number = passport_number.value ?? '';
    form.registration_path = isInternational.value ? 'international' : 'zimbabwean';
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
    const loginEmail = duplicateResult.value?.loginEmail;
    const url = loginEmail ? `${route('login')}?email=${encodeURIComponent(loginEmail)}` : route('login');
    navigateTo(url);
};

const clearFormError = (field: string) => {
    clearFormErrors(form, field);
};
</script>

<template>
    <Head :title="$t('trans.application_form')" />
    <div class="min-h-svh bg-gray-50">
        <ComingSoonAnimated v-if="maintenanceMode" :title="$t('trans.ui_sorry')" message="Enrolment has been closed" />
        <div v-else class="flex min-h-svh flex-col lg:flex-row">
            <div class="flex w-full flex-1 flex-col p-4 sm:p-6 lg:w-[62%] lg:min-w-0 lg:p-10 xl:w-[65%] xl:p-12 2xl:w-[68%]">
                <div class="mx-auto flex w-full max-w-2xl flex-1 flex-col">
                    <RegistrationBrandHeader />
                    <RegistrationAlerts />

                    <RegistrationPathSelector :active-path="activePath" :path-options="pathOptions" @switch-path="switchPath" />

                    <div class="rounded-2xl bg-white p-5 shadow-md sm:p-8 lg:p-10">
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
                            :last-name="last_name ?? ''"
                            :email="email"
                            :password="password"
                            :password-confirmation="password_confirmation"
                            :processing="form.processing"
                            :errors="form.errors"
                            :password-matches="passwordMatches"
                            @update:first-name="first_name = $event"
                            @update:last-name="last_name = $event"
                            @update:email="email = $event"
                            @update:password="password = $event"
                            @update:password-confirmation="password_confirmation = $event"
                            @clear-error="clearFormError"
                            @back="step = 'identity'"
                            @submit="submitForm"
                        />

                        <div class="mt-4 border-t border-gray-100 pt-4">
                            <BaseButton
                                class="w-full"
                                :variant="ColorVariant.primary_outline"
                                type="button"
                                :disabled="form.processing"
                                @click="() => navigateTo(route('login'))"
                            >
                                {{ $t('trans.returning_student_login') }}
                            </BaseButton>
                        </div>
                    </div>
                </div>
            </div>
            <RegistrationGuide :active-path="activePath" />
        </div>
    </div>
</template>
