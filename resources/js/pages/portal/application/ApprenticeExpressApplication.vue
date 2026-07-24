<script setup lang="ts">
import ApprenticeDetails from '@/components/students/update/ApprenticeDetails.vue';
import PortalApplicationShell from '@/components/portal/PortalApplicationShell.vue';
import { ColorVariant } from '@/enums/colors';
import { BaseButton } from '@/components/core/button';
import { useApplicationFormHelper } from '@/composables/students/useApplicationFormHelper';
import { useRegistrationAvailability } from '@/composables/students/useRegistrationAvailability';
import { useUtils } from '@/composables/core/useUtils';
import { useCreateApplicationFormStore } from '@/store/portal/useCreateApplicationFormStore';
import { CreateApplicationParams } from '@/types/portal';
import { useForm } from '@inertiajs/vue3';
import { storeToRefs } from 'pinia';
import { computed, onMounted, ref } from 'vue';

interface ProgrammeSummary {
    department_id?: number | null;
    department_label?: string | null;
    department_level_id?: number | null;
    department_level_label?: string | null;
    course_id?: number | null;
    course_label?: string | null;
    mode_of_study_id?: number | null;
    mode_of_study_label?: string | null;
}

interface Props {
    applicationStep?: string;
    applicationTrack?: string;
    applicationTrackLabel?: string;
    programmeSummary?: ProgrammeSummary | null;
}

const props = defineProps<Props>();

const submitting = ref(false);
const { redirectIfClosed } = useRegistrationAvailability();
const { updateCreateForm } = useApplicationFormHelper();
const { navigateTo } = useUtils();
const store = useCreateApplicationFormStore();
const storeRefs = storeToRefs(store);

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
    employer: '',
    apprentice_number: '',
});

const programmeSummaryLine = computed(() => {
    const summary = props.programmeSummary;
    if (!summary) {
        return null;
    }

    return [summary.department_label, summary.department_level_label, summary.course_label, summary.mode_of_study_label]
        .filter((part): part is string => typeof part === 'string' && part.length > 0)
        .join(' · ');
});

const hasWizardDetails = computed(() => {
    return Boolean(
        storeRefs.first_name?.value
        && storeRefs.last_name?.value
        && storeRefs.date_of_birth?.value
        && storeRefs.phone_number?.value
        && storeRefs.next_of_kin_name?.value
        && (storeRefs.department_id?.value || storeRefs.department?.value?.value)
        && (storeRefs.level_id?.value || storeRefs.level?.value?.value)
        && (storeRefs.course_id?.value || storeRefs.course?.value?.value)
        && (storeRefs.mode_of_study_id?.value || storeRefs.modeOfStudy?.value?.value),
    );
});

onMounted(() => {
    redirectIfClosed();

    if (!hasWizardDetails.value) {
        navigateTo(route('portal.application.create'));
        return;
    }

    updateCreateForm(form);
    form.employer = storeRefs.employer?.value ?? '';
    form.apprentice_number = storeRefs.apprentice_number?.value ?? '';
});

const submit = () => {
    submitting.value = true;
    const employer = form.employer;
    const apprenticeNumber = form.apprentice_number;
    updateCreateForm(form);
    form.employer = employer;
    form.apprentice_number = apprenticeNumber;

    form.post(route('portal.store-application'), {
        onFinish: () => {
            submitting.value = false;
        },
    });
};
</script>

<template>
    <PortalApplicationShell>
        <div class="mx-auto flex w-full max-w-2xl flex-col px-5 pb-12">
            <div class="mb-8 text-center">
                <h1 class="text-xl font-semibold text-foreground">
                    {{ $t('trans.application_apprentice_express_title') }}
                </h1>
                <p class="mt-2 text-sm text-muted-foreground">
                    {{ $t('trans.application_apprentice_express_description') }}
                </p>
            </div>

            <div
                v-if="programmeSummaryLine"
                class="mb-6 rounded-xl border border-border bg-muted/30 px-4 py-3 text-left"
            >
                <p class="text-xs font-medium uppercase tracking-wide text-muted-foreground">
                    {{ $t('trans.application_apprentice_programme_summary') }}
                </p>
                <p class="mt-1 text-sm font-medium text-foreground">
                    {{ programmeSummaryLine }}
                </p>
            </div>

            <form class="space-y-6" @submit.prevent="submit">
                <ApprenticeDetails :form="form" />

                <div v-if="form.errors.error" class="rounded-lg border border-destructive/30 bg-destructive/5 px-4 py-3 text-sm text-destructive">
                    {{ form.errors.error }}
                </div>
                <div
                    v-for="(message, field) in form.errors"
                    :key="field"
                    v-show="field !== 'error'"
                    class="rounded-lg border border-destructive/30 bg-destructive/5 px-4 py-3 text-sm text-destructive"
                >
                    {{ message }}
                </div>

                <div class="flex justify-between gap-3">
                    <BaseButton
                        type="button"
                        :variant="ColorVariant.shade"
                        @click="navigateTo(route('portal.application.confirm'))"
                    >
                        {{ $t('trans.edit') }}
                    </BaseButton>
                    <BaseButton
                        type="submit"
                        :variant="ColorVariant.primary"
                        :disabled="submitting || form.processing"
                    >
                        {{ $t('trans.submit') }}
                    </BaseButton>
                </div>
            </form>
        </div>
    </PortalApplicationShell>
</template>
