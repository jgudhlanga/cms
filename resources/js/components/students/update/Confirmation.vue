<script setup lang="ts">
import ContactDetails from '@/components/students/view/ContactDetails.vue';
import NextOfKinDetails from '@/components/students/view/NextOfKinDetails.vue';
import PersonalDetails from '@/components/students/view/PersonalDetails.vue';
import ProgramDetails from '@/components/students/view/ProgramDetails.vue';
import { useUtils } from '@/composables/core/useUtils';
import LevelRequirements from '@/components/students/update/LevelRequirements.vue';
import OLevelRequirements from '@/components/students/update/OLevelRequirements.vue';
import SDPRequirements from '@/components/students/update/SDPRequirements.vue';
import { useCreateApplicationFormStore } from '@/store/portal/useCreateApplicationFormStore';
import { ContactDetailView, NextOfKinDetailView, PersonalDetailView, ProgramDetailView } from '@/types/students';
import { storeToRefs } from 'pinia';
import { ButtonSize } from '@/enums/buttons';
import { BaseButton } from '@/components/core/button';

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
} = storeToRefs(useCreateApplicationFormStore());
const { isItTrue } = useUtils();
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
</script>

<template>
    <PersonalDetails :personal="personal" :title="$t('trans.personal_details')" />
    <ContactDetails :contacts="contacts" :title="$t('trans.contact_details')" />
    <NextOfKinDetails :next-of-kin="nextOfKin" :title="$t('trans.next_of_kin')" />
    <ProgramDetails :program="programDetails" :title="$tChoice('trans.program', 1)">
        <div class="mt-5 flex flex-col" v-if="levelRequirements">
            <template v-if="isItTrue(levelRequirements.attributes.isOLevelRequired)">
                <OLevelRequirements :level-requirements="levelRequirements" :is-view-only="true" />
            </template>
            <template v-if="levelRequirements.attributes.requiredLevel">
                <LevelRequirements :level-requirements="levelRequirements" :is-view-only="true" />
            </template>
            <template v-if="isItTrue(levelRequirements.attributes.onlyReadWriteRequired)">
                <SDPRequirements :level-requirements="levelRequirements" :is-view-only="true" />
            </template>
        </div>
    </ProgramDetails>
    <div class="mb-10 flex items-center justify-center">
        <BaseButton type="button" @click="() => {}" class="w-full md:w-[200px]" :size="ButtonSize.xl">
            {{ $t('trans.submit') }}
        </BaseButton>
    </div>
</template>
