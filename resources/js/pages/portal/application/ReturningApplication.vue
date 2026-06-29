<script setup lang="ts">
import { computed, onMounted, ref } from 'vue';
import ContactDetails from '@/components/students/update/ContactDetails.vue';
import NextOfKinDetails from '@/components/students/update/NextOfKinDetails.vue';
import PersonalDetails from '@/components/students/update/PersonalDetails.vue';
import Programs from '@/components/students/update/Programs.vue';
import { useUtils } from '@/composables/core/useUtils';
import { useCreateApplicationWizard } from '@/composables/students/useCreateApplicationWizard';
import { useReturningApplicationPrefill } from '@/composables/students/useReturningApplicationPrefill';
import { useCreateApplicationFormStore } from '@/store/portal/useCreateApplicationFormStore';
import { AuthObject } from '@/types/data-pagination';
import { CreateApplicationParams } from '@/types/portal';
import BaseAlert from '@/components/core/alert/BaseAlert.vue';
import PortalApplicationLevelChip from '@/components/portal/PortalApplicationLevelChip.vue';
import PortalApplicationMobileFooter from '@/components/portal/PortalApplicationMobileFooter.vue';
import PortalApplicationShell from '@/components/portal/PortalApplicationShell.vue';
import PortalApplicationStepper from '@/components/portal/PortalApplicationStepper.vue';
import type { ApplicationFormStep } from '@/components/portal/PortalApplicationStepper.vue';
import { useIdTypes } from '@/composables/shared/useIdTypes';
import { useApplicationFormHelper } from '@/composables/students/useApplicationFormHelper';
import { useRegistrationAvailability } from '@/composables/students/useRegistrationAvailability';
import { TypeVariant } from '@/enums/type-variants';
import { CourseRequirement, DepartmentLevelRequirement } from '@/types/department-meta-data';
import { Level, IntakePeriod } from '@/types/institution';
import { Head, useForm } from '@inertiajs/vue3';
import { storeToRefs } from 'pinia';

interface Props {
    returningPrefill: Record<string, unknown>;
    studentId: number;
    targetIntake: IntakePeriod;
    hasPaidApplicationFee: boolean | null;
    levelsWithPayment: Level[];
    selectedLevelId?: number | null;
    selectedLevelName?: string | null;
    auth: AuthObject;
    errors: object;
}

const props = defineProps<Props>();
const requirements = ref<CourseRequirement | DepartmentLevelRequirement | null | undefined>(null);
const { listIdTypes } = useIdTypes();
const { navigateTo } = useUtils();
const { updateCreateForm } = useApplicationFormHelper();
const store = useCreateApplicationFormStore();
const storeRefs = storeToRefs(store);

const intakeName = computed(() => props.targetIntake?.attributes?.name ?? '');

const form = useForm<CreateApplicationParams>({
    email: '',
    first_name: '',
    gender: null,
    gender_id: null,
    last_name: null,
    middle_name: null,
    title: null,
    title_id: null,
    address_1: '',
    address_2: '',
    address_3: '',
    address_4: '',
    alt_phone_number: '',
    country: null,
    country_id: null,
    date_of_birth: '',
    id_number: '',
    id_type_id: null,
    idType: null,
    maritalStatus: null,
    marital_status_id: null,
    next_of_kin_address_1: '',
    next_of_kin_address_2: '',
    next_of_kin_address_3: '',
    next_of_kin_address_4: '',
    next_of_kin_name: '',
    next_of_kin_phone_number: '',
    passport_number: '',
    phone_number: '',
    relationship: null,
    relationship_id: null,
    study_permit_number: '',
    modeOfStudy: null,
    mode_of_study_id: null,
    department: null,
    department_id: null,
    course: null,
    course_id: null,
    disability_status: null,
    level: null,
    level_id: null,
    required_level_completed: null,
    required_level_upload: null,
    read_write_acknowledged: null,
    o_level_subject_ids: null,
    o_level_years: null,
    o_level_sittings: null,
    o_level_other_subject_ids: null,
    o_level_other_grade_ids: null,
    o_level_other_years: null,
    o_level_other_sittings: null,
});

const wizard = useCreateApplicationWizard(form, props.studentId);
const { currentStep, isFirstStep, stepTitleKey, primaryActionLabel, stepErrorHint } = wizard;
const { applyPrefill } = useReturningApplicationPrefill(props.returningPrefill, storeRefs);
const { redirectIfClosed } = useRegistrationAvailability();

const getRequirements = () => {
    requirements.value =
        storeRefs.courseRequirements?.value && Number(String(storeRefs.courseRequirements?.value?.id)) > 0
            ? storeRefs.courseRequirements?.value
            : storeRefs.levelRequirements?.value;
};

onMounted(async () => {
    redirectIfClosed();
    store.$reset();
    await listIdTypes();
    applyPrefill();
    updateCreateForm(form);
});

const isProgrammeLevelAvailable = ref(true);
const isValidating = ref(false);

const onStepNavigate = (step: ApplicationFormStep) => {
    wizard.goToStep(step);
};

const onPrimaryAction = async () => {
    isValidating.value = true;
    try {
        getRequirements();
        updateCreateForm(form);

        if (currentStep.value === 'programme') {
            if (!isProgrammeLevelAvailable.value) {
                return;
            }
            const valid = await wizard.validateFullForm(requirements.value);
            if (valid) {
                navigateTo(route('portal.application.returning.confirm'));
            }
            return;
        }

        await wizard.goNext(requirements.value);
    } finally {
        isValidating.value = false;
    }
};
</script>

<template>
    <Head :title="$t('trans.complete_application')" />
    <PortalApplicationShell
        :intake-name="intakeName"
        :page-title="$t('trans.application_form')"
        :sticky-footer="true"
        hide-intake-banner
    >
        <template #header>
            <div class="mb-3 space-y-1.5 text-center">
                <PortalApplicationStepper compact :current-step="currentStep" @navigate="onStepNavigate" />
                <PortalApplicationLevelChip :level-name="selectedLevelName" :intake-name="intakeName" />
            </div>
        </template>

        <BaseAlert
            class="mb-4"
            :type="TypeVariant.info"
            :description="$t('trans.returning_student_reapply_banner', { intake: intakeName })"
        />

        <form @submit.prevent="onPrimaryAction">
            <div class="space-y-4">
                <div>
                    <h2 class="text-base font-semibold text-foreground">{{ $t(stepTitleKey) }}</h2>
                </div>

                <div class="rounded-2xl border border-border bg-card p-5 shadow-md sm:p-6">
                    <PersonalDetails v-if="currentStep === 'personal'" :form="form" bare />
                    <ContactDetails v-else-if="currentStep === 'contact'" :form="form" bare />
                    <NextOfKinDetails v-else-if="currentStep === 'next_of_kin'" :form="form" bare />
                    <Programs
                        v-else-if="currentStep === 'programme'"
                        :form="form"
                        bare
                        :selected-level-name="selectedLevelName"
                        :has-paid-application-fee="hasPaidApplicationFee"
                        :levels-with-payment="levelsWithPayment"
                        @level-availability-change="isProgrammeLevelAvailable = $event"
                    />
                </div>
            </div>
        </form>

        <template #footer>
            <PortalApplicationMobileFooter
                :primary-label="primaryActionLabel"
                :processing="isValidating"
                :error-hint="stepErrorHint"
                :show-back="!isFirstStep"
                :primary-disabled="currentStep === 'programme' && !isProgrammeLevelAvailable"
                @primary="onPrimaryAction"
                @back="wizard.goBack"
            />
        </template>
    </PortalApplicationShell>
</template>
