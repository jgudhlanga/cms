<script setup lang="ts">
import StudentPageHeader from '@/components/shared/students/StudentPageHeader.vue';
import ViewContactDetails from '@/components/students/view/ViewContactDetails.vue';
import ViewNextOfKinDetails from '@/components/students/view/ViewNextOfKinDetails.vue';
import ViewPersonalDetails from '@/components/students/view/ViewPersonalDetails.vue';
import ProgramDetails from '@/components/students/view/ProgramDetails.vue';
import { useUtils } from '@/composables/core/useUtils';
import { useStudentPortal } from '@/composables/students/useStudentPortal';
import { useCreateApplicationFormStore } from '@/store/portal/useCreateApplicationFormStore';
import { CreateApplicationParams } from '@/types/portal';

import BaseAlert from '@/components/core/alert/BaseAlert.vue';
import { BaseButton } from '@/components/core/button';
import LevelRequirements from '@/components/students/update/LevelRequirements.vue';
import OLevelRequirements from '@/components/students/update/OLevelRequirements.vue';
import SDPRequirements from '@/components/students/update/SDPRequirements.vue';
import { useApplicationFormHelper } from '@/composables/students/useApplicationFormHelper';
import { ButtonSize } from '@/enums/buttons';
import { ColorVariant } from '@/enums/colors';
import { TypeVariant } from '@/enums/type-variants';
import { ContactDetailView, NextOfKinDetailView, PersonalDetailView, ProgramDetailView } from '@/types/students';
import { useForm } from '@inertiajs/vue3';
import { storeToRefs } from 'pinia';
import { onMounted } from 'vue';

// Composable
const { saveApplication, selectLevel } = useStudentPortal();
const { isItTrue, navigateTo } = useUtils();
const { updateCreateForm } = useApplicationFormHelper();

const {
    email,
    first_name,
    gender,
    last_name,
    middle_name,
    title,
    address_1,
    address_2,
    address_3,
    address_4,
    alt_phone_number,
    country,
    date_of_birth,
    id_number,
    idType,
    maritalStatus,
    next_of_kin_address_1,
    next_of_kin_address_2,
    next_of_kin_address_3,
    next_of_kin_address_4,
    next_of_kin_name,
    next_of_kin_phone_number,
    passport_number,
    phone_number,
    relationship,
    study_permit_number,
    department,
    course,
    level,
    levelRequirements,
    disability_status,
} = storeToRefs(useCreateApplicationFormStore());
const personal: PersonalDetailView = {
    title: title.value?.label ?? '',
    firstname: first_name?.value ?? '',
    middleName: middle_name?.value ?? '',
    lastname: last_name?.value ?? '',
    gender: gender?.value?.label ?? '',
    maritalStatus: maritalStatus?.value?.label ?? '',
    idType: idType?.value?.label ?? '',
    idNumber: id_number?.value ?? '',
    passportNumber: passport_number?.value ?? '',
    country: country?.value?.label ?? '',
    studyPermitNumber: study_permit_number?.value ?? '',
    dateOfBirth: date_of_birth?.value ?? '',
    disabilityStatus: disability_status?.value ?? '',
};

const contacts: ContactDetailView = {
    phoneNumber: phone_number?.value ?? '',
    altPhoneNumber: alt_phone_number?.value ?? '',
    emailAddress: email?.value ?? '',
    address1: address_1?.value ?? '',
    address2: address_2?.value ?? '',
    address3: address_3?.value ?? '',
    address4: address_4?.value ?? '',
};

const nextOfKin: NextOfKinDetailView = {
    name: next_of_kin_name?.value ?? '',
    phoneNumber: next_of_kin_phone_number?.value ?? '',
    relationship: relationship?.value?.label ?? '',
    address1: next_of_kin_address_1?.value ?? '',
    address2: next_of_kin_address_2?.value ?? '',
    address3: next_of_kin_address_3?.value ?? '',
    address4: next_of_kin_address_4?.value ?? '',
};
const programDetails: ProgramDetailView = {
    department: department?.value?.label ?? '',
    level: level?.value?.label ?? '',
    course: course?.value?.label ?? '',
};
const storeRefs = storeToRefs(useCreateApplicationFormStore());

const form = useForm<CreateApplicationParams>({
    email: storeRefs.email.value || '',
    first_name: storeRefs.first_name.value || '',
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

const save = async () => {
   // selectLevel(String(form.level_id));
    updateCreateForm(form);
    saveApplication(form);
};
onMounted(() => {
   // selectLevel(String(form.level_id));
    /*ToastService.warning('Sorry, The registration has ended for now. Contact the administration for more info.');
    navigateTo(route('login'));
    return;*/
});
</script>
<template>
    <StudentPageHeader />
    <div class="mt-20 flex w-full flex-col bg-white px-5 md:p-0">
        <div class="flex w-full flex-col space-y-6 md:mx-auto md:w-7/8">
            <BaseAlert
                description="Before submitting, carefully review your application details to ensure everything is accurate and up to date. Check your personal information, contact details, and any required details. Once you confirm the information is correct, you can proceed to submit your application."
                :type="TypeVariant.success"
            />
            <ViewPersonalDetails :personal="personal" :title="$t('trans.personal_details')" />
            <ViewContactDetails :contacts="contacts" :title="$t('trans.contact_details')" />
            <ViewNextOfKinDetails :next-of-kin="nextOfKin" :title="$t('trans.next_of_kin')" />
            <ProgramDetails :program="programDetails" :title="$tChoice('trans.program', 1)">
                <div class="mt-5 flex flex-col" v-if="levelRequirements">
                    <template v-if="isItTrue(levelRequirements.attributes.isOLevelRequired)">
                        <OLevelRequirements :is-view-only="true" />
                    </template>
                    <template v-if="levelRequirements.attributes.requiredLevel">
                        <LevelRequirements :requirements="levelRequirements" :is-view-only="true" />
                    </template>
                    <template v-if="isItTrue(levelRequirements.attributes.onlyReadWriteRequired)">
                        <SDPRequirements :is-view-only="true" />
                    </template>
                </div>
            </ProgramDetails>
            <div class="my-5 flex flex-col justify-center space-y-2 space-x-3 md:flex-row">
                <BaseButton
                    @click="navigateTo(route('portal.application.create'))"
                    type="button"
                    :variant="ColorVariant.shade"
                    class="w-full md:w-50"
                    :size="ButtonSize.xl"
                    >{{ $t('trans.edit') }}</BaseButton
                >
                <BaseButton type="button" @click="save" class="w-full md:w-50" :size="ButtonSize.xl">
                    {{ $t('trans.submit') }}
                </BaseButton>
            </div>
        </div>
    </div>
</template>
