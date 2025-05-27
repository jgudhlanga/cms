<script setup lang="ts">
import PageContainer from '@/components/core/page/PageContainer.vue';
import BaseStepperButtons from '@/components/core/stepper/BaseStepperButtons.vue';
import BaseStepperItem from '@/components/core/stepper/BaseStepperItem.vue';
import { Stepper } from '@/components/ui/stepper';
import { useStudentPortal } from '@/composables/portal/useStudentPortal';
import ContactDetails from '@/pages/portal/student/partials/ContactDetails.vue';
import NextOfKinDetails from '@/pages/portal/student/partials/NextOfKinDetails.vue';
import PersonalDetails from '@/pages/portal/student/partials/PersonalDetails.vue';
import { useCreateApplicationFormStore } from '@/store/portal/useCreateApplicationFormStore';
import { AuthObject } from '@/types/data-pagination';
import { CreateApplicationParams } from '@/types/portal';
import { BreadcrumbItemInterface } from '@/types/ui';
import { User } from '@/types/users';
import { Head, useForm } from '@inertiajs/vue3';
import { storeToRefs } from 'pinia';
import { onMounted, ref } from 'vue';

interface Props {
    user: User;
    auth: AuthObject;
    errors: object;
}

const props = defineProps<Props>();
const { steps } = useStudentPortal();
const { user } = props;
const stepIndex = ref(1);
const maxStep = 5;
const metaValid = ref(true);
const breadcrumbs: BreadcrumbItemInterface[] = [{ title: user.attributes?.name }, { transKey: 'finish_your_application' }];
const { id_type, first_name, middle_name, last_name, title, gender } = storeToRefs(useCreateApplicationFormStore());
const form = useForm<CreateApplicationParams>({
    email: '',
    first_name: '',
    gender: null,
    gender_id: null,
    last_name: '',
    middle_name: '',
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
});

const goNext = (next: () => void) => {
    updateForm();
    try {
        next();
    } catch (error: any) {
        form.setError(error.format());
    }
};

onMounted(() => {
    first_name.value = user.attributes?.first_name;
    middle_name.value = user.attributes?.middle_name ?? '';
    last_name.value = user.attributes?.last_name;
    title.value = {
        value: user.attributes?.titleId,
        label: user.attributes?.title,
    };
    gender.value = {
        value: user.attributes?.genderId,
        label: user.attributes?.gender,
    };
    id_type.value = id_type.value || 'zimbabwean-national-id-number';
});

const updateForm = () => {};
</script>
<template>
    <Head :title="$t('trans.create_new_application')" />
    <PageContainer :breadcrumbs="breadcrumbs">
        <form @submit.prevent="() => {}">
            <Stepper orientation="vertical" v-slot="{ isPrevDisabled, nextStep, prevStep }" v-model="stepIndex" class="flex w-full flex-col">
                <BaseStepperItem :steps="steps" :meta-valid="metaValid" />
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
                        <p>Programs</p>
                    </template>
                    <template v-if="stepIndex === maxStep">
                        <p>Confirm</p>
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
