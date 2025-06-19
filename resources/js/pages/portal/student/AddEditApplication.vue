<script setup lang="ts">
// UI components
import PageContainer from '@/components/core/page/PageContainer.vue';
import BaseStepperButtons from '@/components/core/stepper/BaseStepperButtons.vue';
import BaseStepperItem from '@/components/core/stepper/BaseStepperItem.vue';
import { Stepper } from '@/components/ui/stepper';

// Page sections
import Confirmation from '@/pages/portal/student/partials/Confirmation.vue';
import ContactDetails from '@/pages/portal/student/partials/ContactDetails.vue';
import NextOfKinDetails from '@/pages/portal/student/partials/NextOfKinDetails.vue';
import PersonalDetails from '@/pages/portal/student/partials/PersonalDetails.vue';
import Programs from '@/pages/portal/student/partials/Programs.vue';

// Composables
import { useUtils } from '@/composables/core/useUtils';
import { useDepartmentLevels } from '@/composables/institution/useDepartmentLevels';
import { useStudentPortal } from '@/composables/portal/useStudentPortal';

// Store & types
import { useCreateApplicationFormStore } from '@/store/portal/useCreateApplicationFormStore';
import { AuthObject } from '@/types/data-pagination';
import { CreateApplicationParams } from '@/types/portal';
import { BreadcrumbItemInterface } from '@/types/ui';
import { User } from '@/types/users';

// Utilities
import { errorAlert } from '@/lib/alerts';
import { Head, useForm } from '@inertiajs/vue3';
import { trans } from 'laravel-vue-i18n';
import { storeToRefs } from 'pinia';
import { onMounted, ref } from 'vue';

// Props
interface Props {
    user: User;
    auth: AuthObject;
    errors: object;
}

const props = defineProps<Props>();
const { user } = props;

// Composables
const { steps, applicationFormSchema, saveApplication } = useStudentPortal();
const { listLevelRequirements } = useDepartmentLevels();
const { isNativeCitizen } = useUtils();

// Stepper state
const stepIndex = ref(1);
const maxStep = 5;

// Breadcrumbs
const breadcrumbs: BreadcrumbItemInterface[] = [{ title: user.attributes?.name }];

// Store
const storeRefs = storeToRefs(useCreateApplicationFormStore());

// Form
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
    id_type: '',
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
    department: null,
    department_id: null,
    course: null,
    course_id: null,
    level: null,
    level_id: null,
    o_level_subject_ids: null,
    required_level_completed: null,
    read_write_acknowledged: null,
});

// Stepper Navigation
const goNext = async (next: () => void) => {
    updateForm();
    try {
        const schema = applicationFormSchema(isNativeCitizen(storeRefs.id_type.value ?? ''));
        schema[stepIndex.value - 1].parse(form);
        if (storeRefs.date_of_birth.value == null) {
            errorAlert(trans('trans.enter_required_field', { field: trans('trans.date_of_birth').toLowerCase() }));
            return;
        }
        next();
        if (stepIndex.value === 4 && storeRefs.level.value?.value) {
            await listLevelRequirements(storeRefs.level.value.value.toString());
        }
    } catch (error: any) {
        form.setError(error.format());
    }
};

// Populate from user
const populateInitialForm = () => {
    const attrs = user.attributes;
    storeRefs.first_name.value = attrs?.first_name;
    storeRefs.middle_name.value = attrs?.middle_name ?? '';
    storeRefs.last_name.value = attrs?.last_name;
    storeRefs.email.value = attrs?.email ?? '';
    storeRefs.title.value = { value: attrs?.titleId, label: attrs?.title };
    storeRefs.gender.value = { value: attrs?.genderId, label: attrs?.gender };
    storeRefs.id_type.value ||= 'zimbabwean-national-id-number';
};

// Sync Pinia refs into form
const updateForm = () => {
    Object.assign(form, {
        email: storeRefs.email.value,
        first_name: storeRefs.first_name.value,
        gender: storeRefs.gender.value,
        gender_id: storeRefs.gender.value?.value ?? '',
        last_name: storeRefs.last_name.value,
        middle_name: storeRefs.middle_name.value ?? '',
        title: storeRefs.title.value,
        title_id: storeRefs.title.value?.value ?? '',
        address_1: storeRefs.address_1.value,
        address_2: storeRefs.address_2.value,
        address_3: storeRefs.address_3.value,
        address_4: storeRefs.address_4.value,
        alt_phone_number: storeRefs.alt_phone_number?.value ?? '',
        country: storeRefs.country?.value,
        country_id: storeRefs.country?.value?.value ?? null,
        date_of_birth: storeRefs.date_of_birth.value ?? '',
        id_number: storeRefs.id_number?.value ?? '',
        id_type: storeRefs.id_type.value ?? '',
        maritalStatus: storeRefs.maritalStatus?.value,
        marital_status_id: storeRefs.maritalStatus?.value?.value ?? null,
        next_of_kin_address_1: storeRefs.next_of_kin_address_1.value ?? '',
        next_of_kin_address_2: storeRefs.next_of_kin_address_2.value ?? '',
        next_of_kin_address_3: storeRefs.next_of_kin_address_3.value ?? '',
        next_of_kin_address_4: storeRefs.next_of_kin_address_4.value ?? '',
        next_of_kin_name: storeRefs.next_of_kin_name.value ?? '',
        next_of_kin_phone_number: storeRefs.next_of_kin_phone_number.value ?? '',
        passport_number: storeRefs.passport_number?.value ?? '',
        phone_number: storeRefs.phone_number?.value ?? '',
        relationship: storeRefs.relationship.value,
        relationship_id: storeRefs.relationship.value?.value ?? null,
        study_permit_number: storeRefs.study_permit_number?.value ?? '',
        department: storeRefs.department.value,
        department_id: storeRefs.department.value?.value ?? null,
        course: storeRefs.course.value,
        course_id: storeRefs.course.value?.value ?? null,
        level: storeRefs.level.value,
        level_id: storeRefs.level.value?.value ?? null,
        o_level_subject_ids: storeRefs.o_level_subject_ids?.value ?? null,
        required_level_completed: storeRefs.required_level_completed?.value ?? null,
        read_write_acknowledged: storeRefs.read_write_acknowledged?.value ?? null,
    });
};

// Lifecycle
onMounted(() => {
    populateInitialForm();
});
</script>
<template>
    <Head :title="$tChoice('trans.application', 1)" />
    <PageContainer :breadcrumbs="breadcrumbs">
        <form @submit.prevent="() => saveApplication(form)">
            <Stepper v-slot="{ isPrevDisabled, nextStep, prevStep }" v-model="stepIndex" class="flex w-full flex-col">
                <BaseStepperItem :steps="steps" />
                <!-- CONTENT -->
                <div class="mt-4 flex flex-col gap-4">
                    <template v-if="stepIndex === 1">
                        <PersonalDetails :form="form" />
                    </template>
                    <template v-if="stepIndex === 2">
                        <ContactDetails :form="form" />
                    </template>
                    <template v-if="stepIndex === 3">
                        <NextOfKinDetails :form="form" />
                    </template>
                    <template v-if="stepIndex === 4">
                        <Programs :form="form" />
                    </template>
                    <template v-if="stepIndex === maxStep">
                        <Confirmation />
                    </template>
                </div>
                <!-- BUTTONS -->
                <BaseStepperButtons
                    :step-index="stepIndex"
                    :prev-step-action="() => prevStep()"
                    :next-step-action="() => goNext(() => nextStep())"
                    :previous-disabled="isPrevDisabled"
                    :max-step="maxStep"
                />
            </Stepper>
        </form>
    </PageContainer>
</template>
