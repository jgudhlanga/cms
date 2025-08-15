<script setup lang="ts">
import LabelValue from '@/components/core/util/LabelValue.vue';
import { User } from '@/types/users';
import { ValueAndLabel } from '@/types/utils';

interface Props {
    user: User;
}

const props = defineProps<Props>();
const { user } = props;
const { attributes } = user;
const userDetails: ValueAndLabel[] = [
    { transKey: 'trans.first_name', value: attributes?.firstname ?? '' },
    { transKey: 'trans.middle_name', value: attributes?.middleName ?? '' },
    { transKey: 'trans.last_name', value: attributes?.lastname ?? '' },
    { transKey: 'trans.email_address', value: attributes?.email ?? '' },
    { transKey: 'trans.phone_number', value: '' },
];
</script>

<template>
    <div :class="`grid w-full grid-cols-1 gap-x-2 md:grid-cols-4`">
        <LabelValue
            v-for="(detail, index) in userDetails"
            :key="index"
            :label="`${detail?.transKey ? $t(detail.transKey) : $tChoice(detail.transChoiceKey ?? '', 1)}`"
            :value="detail.value"
        />
    </div>
</template>
