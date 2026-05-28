<script setup lang="ts">
import PageContainer from '@/components/core/page/PageContainer.vue';
import AvatarTitleList from '@/components/core/util/AvatarTitleList.vue';
import HeadingSmall from '@/components/core/util/HeadingSmall.vue';
import { AuthObject } from '@/types/data-pagination';
import { Link } from '@/types/ui';
import { Head } from '@inertiajs/vue3';

const props = defineProps<{
    auth: AuthObject;
    errors: object;
}>();

const breadcrumbs: Array<Link> = [{ transChoiceKey: 'finance.finance', transChoiceKeyIndex: 1 }];
const can = props?.auth?.can;
const tabs: Array<Link> = [
    {
        transChoiceKey: 'finance.reconciliation',
        url: route('finance.reconciliation'),
    },
    {
        transChoiceKey: 'finance.exchange_rate',
        url: route('finance.exchange-rates.index'),
    },
];
</script>

<template>
    <Head :title="$tChoice('finance.finance', 1)" />
    <PageContainer :breadcrumbs="breadcrumbs">
        <HeadingSmall :title="$tChoice('finance.finance', 1)" :description="$t('finance.finance_module_description')" />
        <AvatarTitleList v-if="can['view:finance-settings'] || can['view:finances']" :tabs="tabs" />
    </PageContainer>
</template>