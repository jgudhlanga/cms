<script setup lang="ts">
import BaseCard from '@/components/core/card/BaseCard.vue';
import LabelValue from '@/components/core/util/LabelValue.vue';
import { ContactDetailView } from '@/types/students';
import { ValueAndLabel } from '@/types/utils';

interface Props {
    contacts: ContactDetailView;
    title?: string;
    gridSize?: string;
}

const props = withDefaults(defineProps<Props>(), {
    gridSize: '4',
});
const { contacts } = props;

const contactDetails: ValueAndLabel[] = [
    { transKey: 'trans.phone_number', value: contacts?.phoneNumber ?? '' },
    { transKey: 'trans.alt_phone_number', value: contacts?.altPhoneNumber ?? '' },
    { transKey: 'trans.email_address', value: contacts?.emailAddress ?? '' },
    { transKey: 'trans.address_1', value: contacts?.address1 ?? '' },
    { transKey: 'trans.address_2', value: contacts?.address2 ?? '' },
    { transKey: 'trans.address_3', value: contacts?.address3 ?? '' },
    { transKey: 'trans.address_4', value: contacts?.address4 ?? '' },
];
</script>

<template>
    <BaseCard :title="title ? title : ''">
        <div :class="`grid grid-cols-1 gap-2 md:grid-cols-${gridSize}`">
            <LabelValue
                v-for="(contact, index) in contactDetails"
                :key="index"
                :label="`${contact?.transKey ? $t(contact.transKey) : $tChoice(contact.transChoiceKey ?? '', 1)}`"
                :value="contact.value"
            />
        </div>
    </BaseCard>
</template>

<style scoped></style>
