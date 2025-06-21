<script setup lang="ts">
import BaseCard from '@/components/core/card/BaseCard.vue';
import LabelValue from '@/components/core/util/LabelValue.vue';
import { useUtils } from '@/composables/core/useUtils';
import { PersonalDetailView } from '@/types/students';
import { ValueAndLabel } from '@/types/utils';

const { formatDate } = useUtils();
const { getIDType, isNativeCitizen } = useUtils();

interface Props {
    personal: PersonalDetailView;
    title?: string;
}

const props = defineProps<Props>();
const { personal } = props;

const personalDetails: ValueAndLabel[] = [
    { transChoiceKey: 'trans.title', value: personal?.title ?? '' },
    { transKey: 'trans.first_name', value: personal?.firstname ?? '' },
    { transKey: 'trans.middle_name', value: personal?.middleName ?? '' },
    { transKey: 'trans.last_name', value: personal?.lastname ?? '' },
    { transChoiceKey: 'trans.gender', value: personal?.gender ?? '' },
    { transChoiceKey: 'trans.marital_status', value: personal?.maritalStatus ?? '' },
    { transKey: 'trans.id_type', value: getIDType(personal?.idType ?? '') },
];
if (isNativeCitizen(personal?.idType ?? '')) {
    personalDetails.push({
        transKey: 'trans.id_number',
        value: personal?.idNumber ?? '',
    });
} else {
    personalDetails.push(
        { transKey: 'trans.passport_number', value: personal?.passportNumber ?? '' },
        { transChoiceKey: 'trans.country', value: personal?.country ?? '' },
        { transKey: 'trans.study_permit_number', value: personal?.studyPermitNumber ?? '' },
    );
}

personalDetails.push({ transKey: 'trans.date_of_birth', value: formatDate(personal?.dateOfBirth ?? '') });
</script>

<template>
    <BaseCard :title="title ? title : '' ">
        <div class="grid grid-cols-1 gap-2 md:grid-cols-4">
            <LabelValue
                v-for="(detail, index) in personalDetails"
                :key="index"
                :label="`${detail?.transKey ? $t(detail.transKey) : $tChoice(detail.transChoiceKey ?? '', 1)}`"
                :value="detail.value"
            />
        </div>
    </BaseCard>
</template>
