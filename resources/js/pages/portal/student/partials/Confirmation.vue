<script setup lang="ts">
import BaseCard from '@/components/core/card/BaseCard.vue';
import LabelValue from '@/components/core/util/LabelValue.vue';
import { useUtils } from '@/composables/core/useUtils';
import LevelRequirements from '@/pages/portal/student/partials/LevelRequirements.vue';
import OLevelRequirements from '@/pages/portal/student/partials/OLevelRequirements.vue';
import SDPRequirements from '@/pages/portal/student/partials/SDPRequirements.vue';
import { useCreateApplicationFormStore } from '@/store/portal/useCreateApplicationFormStore';
import { ValueAndLabel } from '@/types/utils';
import { storeToRefs } from 'pinia';

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
    id_type,
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
const { formatDate, isItTrue } = useUtils();
const { getIDType, isNativeCitizen } = useUtils();
const personalDetails: ValueAndLabel[] = [
    { transChoiceKey: 'trans.title', value: title.value?.label ?? '' },
    { transKey: 'trans.first_name', value: first_name.value ?? '' },
    { transKey: 'trans.middle_name', value: middle_name.value ?? '' },
    { transKey: 'trans.last_name', value: last_name.value ?? '' },
    { transChoiceKey: 'trans.gender', value: gender.value?.label ?? '' },
    { transChoiceKey: 'trans.gender', value: gender.value?.label ?? '' },
    { transChoiceKey: 'trans.marital_status', value: maritalStatus?.value?.label ?? '' },
    { transKey: 'trans.id_type', value: getIDType(id_type?.value ?? '') },
];
if (isNativeCitizen(id_type?.value ?? '')) {
    personalDetails.push({
        transKey: 'trans.id_number',
        value: id_number?.value ?? '',
    });
} else {
    personalDetails.push(
        { transKey: 'trans.passport_number', value: passport_number?.value ?? '' },
        { transChoiceKey: 'trans.country', value: country?.value?.label ?? '' },
        { transKey: 'trans.study_permit_number', value: study_permit_number?.value ?? '' },
    );
}

personalDetails.push({ transKey: 'trans.date_of_birth', value: formatDate(date_of_birth?.value ?? '') });

const contactDetails: ValueAndLabel[] = [
    { transKey: 'trans.phone_number', value: phone_number?.value ?? '' },
    { transKey: 'trans.alt_phone_number', value: alt_phone_number?.value ?? '' },
    { transKey: 'trans.email_address', value: email?.value ?? '' },
    { transKey: 'trans.address_1', value: address_1?.value ?? '' },
    { transKey: 'trans.address_2', value: address_2?.value ?? '' },
    { transKey: 'trans.address_3', value: address_3?.value ?? '' },
    { transKey: 'trans.address_4', value: address_4?.value ?? '' },
];

const nextOfKinDetails: ValueAndLabel[] = [
    { transChoiceKey: 'trans.name', value: next_of_kin_name?.value ?? '' },
    { transKey: 'trans.phone_number', value: next_of_kin_phone_number?.value ?? '' },
    { transChoiceKey: 'trans.relationship', value: relationship?.value?.label ?? '' },
    { transKey: 'trans.address_1', value: next_of_kin_address_1?.value ?? '' },
    { transKey: 'trans.address_2', value: next_of_kin_address_2?.value ?? '' },
    { transKey: 'trans.address_3', value: next_of_kin_address_3?.value ?? '' },
    { transKey: 'trans.address_4', value: next_of_kin_address_4?.value ?? '' },
];
const programDetails: ValueAndLabel[] = [
    { transChoiceKey: 'trans.department', value: department?.value?.label ?? '' },
    { transChoiceKey: 'trans.level', value: level?.value?.label ?? '' },
    { transChoiceKey: 'trans.course', value: course?.value?.label ?? '' },
];
</script>

<template>
    <BaseCard :title="$t('trans.personal_details')">
        <div class="grid grid-cols-1 gap-2 md:grid-cols-4">
            <LabelValue
                v-for="(detail, index) in personalDetails"
                :key="index"
                :label="`${detail?.transKey ? $t(detail.transKey) : $tChoice(detail.transChoiceKey ?? '', 1)}`"
                :value="detail.value"
            />
        </div>
    </BaseCard>
    <BaseCard :title="$t('trans.contact_details')">
        <div class="grid grid-cols-1 gap-2 md:grid-cols-4">
            <LabelValue
                v-for="(contact, index) in contactDetails"
                :key="index"
                :label="`${contact?.transKey ? $t(contact.transKey) : $tChoice(contact.transChoiceKey ?? '', 1)}`"
                :value="contact.value"
            />
        </div>
    </BaseCard>

    <BaseCard :title="$t('trans.next_of_kin')">
        <div class="grid grid-cols-1 gap-2 md:grid-cols-4">
            <LabelValue
                v-for="(detail, index) in nextOfKinDetails"
                :key="index"
                :label="`${detail?.transKey ? $t(detail.transKey) : $tChoice(detail.transChoiceKey ?? '', 1)}`"
                :value="detail.value"
            />
        </div>
    </BaseCard>

    <BaseCard :title="$tChoice('trans.program', 1)">
        <div class="grid grid-cols-1 gap-2 md:grid-cols-4">
            <LabelValue
                v-for="(program, index) in programDetails"
                :key="index"
                :label="`${program?.transKey ? $t(program.transKey) : $tChoice(program.transChoiceKey ?? '', 1)}`"
                :value="program.value"
            />
        </div>
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
    </BaseCard>
</template>
