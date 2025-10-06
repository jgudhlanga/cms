<script setup lang="ts">
import { BaseButton } from '@/components/core/button';
import BaseCard from '@/components/core/card/BaseCard.vue';
import { BaseInput } from '@/components/core/form';
import PageContainer from '@/components/core/page/PageContainer.vue';
import CustomSeparator from '@/components/core/util/CustomSeparator.vue';
import ContactDetails from '@/components/students/update/ContactDetails.vue';
import NextOfKinDetails from '@/components/students/update/NextOfKinDetails.vue';
import PersonalDetails from '@/components/students/update/PersonalDetails.vue';
import Programs from '@/components/students/update/Programs.vue';
import UploadProofOfPayment from '@/components/students/update/UploadProofOfPayment.vue';
import { useUtils } from '@/composables/core/useUtils';
import { useIdTypes } from '@/composables/shared/useIdTypes';
import { ButtonSize } from '@/enums/buttons';
import { clearFormErrors } from '@/lib/forms';
import { useCreateApplicationFormStore } from '@/store/portal/useCreateApplicationFormStore';
import { AuthObject } from '@/types/data-pagination';
import { CreateApplicationParams } from '@/types/portal';
import { Link } from '@/types/ui';
import { Head, useForm } from '@inertiajs/vue3';
import { storeToRefs } from 'pinia';
import { computed, onMounted } from 'vue';

interface Props {
    auth: AuthObject;
    errors: object;
    type: 'cash-payment' | 'online-payment';
}

defineProps<Props>();
const breadcrumbs: Array<Link> = [
    { transKey: 'dashboard', href: route('dashboard') },
    { transChoiceKey: 'enrolment', href: route('enrolments.index') },
    { title: 'Create new' },
];

const storeRefs = storeToRefs(useCreateApplicationFormStore());
const { payment_reference, payment_date } = storeRefs;
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
    proof_of_payment: null,
    payment_reference: null,
    payment_date: null,
});
const { idTypes, listIdTypes } = useIdTypes();
const { isNativeCitizen, isItTrue } = useUtils();
const defaultIdType = computed(() => {
    return idTypes.value.find((type) => isItTrue(type.attributes?.isDefault)) ?? null;
});
onMounted(async () => {
    await listIdTypes();
    if (!storeRefs.idType.value) {
        storeRefs.idType.value = {
            label: defaultIdType.value?.attributes?.name ?? '',
            value: Number(defaultIdType.value?.id) || '',
        };
    }
});

const handleUploadFileChange = (event: any) => {
    const upload = event.target.files[0];
    if (!upload) return;
    if (storeRefs.proof_of_payment) {
        storeRefs.proof_of_payment.value = upload;
    }
};
</script>

<template>
    <Head :title="$tChoice('enrolment', 2)" />
    <PageContainer :breadcrumbs="breadcrumbs">
        <form @submit.prevent="() => {}">
            <PersonalDetails :form="form" />
            <CustomSeparator classes="h-1 my-5" />
            <ContactDetails :form="form" />
            <CustomSeparator classes="h-1 my-5" />
            <NextOfKinDetails :form="form" />
            <CustomSeparator classes="h-1 my-5" />
            <BaseCard
                title="Proof of Payment details"
                description="Please provide a scanned copy of your proof of payment or bank deposit slip. Make sure the document is clear and fully visible, including the date, amount, and reference number, to avoid any delays in processing"
            >
                <div class="mt-4 grid grid-cols-1 gap-3 md:grid-cols-3">
                    <UploadProofOfPayment label="Upload file" :handle-change="handleUploadFileChange" />
                    <BaseInput
                        input-id="payment_reference"
                        label="Payment reference"
                        placeholder="enter payment reference"
                        v-model="payment_reference"
                        :is-required="true"
                        @input="clearFormErrors(form, 'payment_reference')"
                        :error="form.errors.payment_reference"
                    />
                    <BaseInput
                        input-id="payment_date"
                        label="Payment date"
                        placeholder="enter lastname / surname"
                        v-model="payment_date"
                        :is-required="true"
                        @input="clearFormErrors(form, 'payment_date')"
                        :error="form.errors.payment_date"
                    />
                </div>
            </BaseCard>
            <CustomSeparator classes="h-1 my-5" />
            <Programs :form="form" />
            <CustomSeparator classes="h-1 my-5" />
            <div class="mb-10 flex items-center justify-center">
                <BaseButton class="w-full md:w-[200px]" :size="ButtonSize.xl">
                    {{ $t('trans.save') }}
                </BaseButton>
            </div>
        </form>
    </PageContainer>
</template>
