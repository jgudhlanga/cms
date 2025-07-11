<script setup lang="ts">
import BaseCard from '@/components/core/card/BaseCard.vue';
import BaseImage from '@/components/core/image/BaseImage.vue';
import LabelValue from '@/components/core/util/LabelValue.vue';
import { useUtils } from '@/composables/core/useUtils';
import { PersonalDetailView } from '@/types/students';
import { ValueAndLabel } from '@/types/utils';
import { computed } from 'vue';

const { isNativeCitizen, formatDate, isItTrue } = useUtils();

interface Props {
    personal: PersonalDetailView;
    title?: string;
    gridSize?: string;
    showExtra?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
    gridSize: '4',
    showExtra: false,
});
const { personal, showExtra } = props;

const personalDetails = computed<ValueAndLabel[]>(() => {
    const details: ValueAndLabel[] = [
        { transChoiceKey: 'trans.title', value: personal?.title ?? '' },
        { transKey: 'trans.first_name', value: personal?.firstname ?? '' },
        { transKey: 'trans.middle_name', value: personal?.middleName ?? '' },
        { transKey: 'trans.last_name', value: personal?.lastname ?? '' },
        { transChoiceKey: 'trans.gender', value: personal?.gender ?? '' },
        { transChoiceKey: 'trans.marital_status', value: personal?.maritalStatus ?? '' },
        { transChoiceKey: 'trans.id_type', value: personal?.idType ?? '' },
    ];

    if (isNativeCitizen(personal?.idType ?? '')) {
        details.push({
            transKey: 'trans.id_number',
            value: personal?.idNumber ?? '',
        });
    } else {
        details.push(
            { transKey: 'trans.passport_number', value: personal?.passportNumber ?? '' },
            { transChoiceKey: 'trans.country', value: personal?.country ?? '' },
            { transKey: 'trans.study_permit_number', value: personal?.studyPermitNumber ?? '' },
        );
    }

    details.push({ transKey: 'trans.date_of_birth', value: formatDate(personal?.dateOfBirth ?? '') });

    if (isItTrue(showExtra)) {
        details.push(
            { transChoiceKey: 'trans.race', value: personal?.race ?? '' },
            { transChoiceKey: 'trans.religion', value: personal?.religion ?? '' },
            { transChoiceKey: 'trans.denomination', value: personal?.denomination ?? '' },
            { transKey: 'trans.weight', value: personal?.weight ?? '' },
            { transKey: 'trans.height', value: personal?.height ?? '' },
        );
    }

    return details;
});
</script>

<template>
    <BaseCard :title="title ? title : ''">
        <div class="flex space-x-3">
            <BaseImage
                v-if="personal?.showAvatar"
                :src="personal?.avatarUrl ?? ''"
                :is-person="true"
                classes="w-[130px] h-[130px] rounded-full border-[1px] border-primary shadow-lg"
            />
            <div :class="`grid w-full grid-cols-1 gap-2 md:grid-cols-${gridSize}`">
                <LabelValue
                    v-for="(detail, index) in personalDetails"
                    :key="index"
                    :label="`${detail?.transKey ? $t(detail.transKey) : $tChoice(detail.transChoiceKey ?? '', 1)}`"
                    :value="detail.value"
                />
            </div>
        </div>
    </BaseCard>
</template>
