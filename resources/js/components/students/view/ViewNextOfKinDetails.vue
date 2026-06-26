<script setup lang="ts">
import BaseCard from '@/components/core/card/BaseCard.vue';
import LabelValue from '@/components/core/util/LabelValue.vue';
import { NextOfKinDetailView } from '@/types/students';
import { ValueAndLabel } from '@/types/utils';

interface Props {
    nextOfKin: NextOfKinDetailView;
    title?: string;
    gridSize?: string;
}

const props = withDefaults(defineProps<Props>(), {
    gridSize: '4',
});
const { nextOfKin } = props;

const nextOfKinDetails: ValueAndLabel[] = [
    { transChoiceKey: 'trans.name', value: nextOfKin?.name ?? '' },
    { transKey: 'trans.phone_number', value: nextOfKin?.phoneNumber ?? '' },
    { transChoiceKey: 'trans.relationship', value: nextOfKin?.relationship ?? '' },
    { transKey: 'trans.address_house_number', value: nextOfKin?.address1 ?? '' },
    { transKey: 'trans.address_street_name', value: nextOfKin?.address2 ?? '' },
    { transKey: 'trans.address_suburb', value: nextOfKin?.address3 ?? '' },
    { transKey: 'trans.address_city_town', value: nextOfKin?.address4 ?? '' },
];
</script>

<template>
    <BaseCard :title="title ? title : ''">
        <div :class="`grid grid-cols-1 gap-2 md:grid-cols-${gridSize}`">
            <LabelValue
                v-for="(detail, index) in nextOfKinDetails"
                :key="index"
                :label="`${detail?.transKey ? $t(detail.transKey) : $tChoice(detail.transChoiceKey ?? '', 1)}`"
                :value="detail.value"
            />
        </div>
    </BaseCard>
</template>

<style scoped></style>
