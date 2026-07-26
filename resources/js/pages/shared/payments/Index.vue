<script setup lang="ts">
import BaseAlert from '@/components/core/alert/BaseAlert.vue';
import PageContainer from '@/components/core/page/PageContainer.vue';
import AvatarTitleList from '@/components/core/util/AvatarTitleList.vue';
import { usePaymentSettings } from '@/composables/settings/usePaymentSettings';
import { AuthObject } from '@/types/data-pagination';
import { Link } from '@/types/ui';
import { Head } from '@inertiajs/vue3';

const props = defineProps<{ auth: AuthObject; errors: object }>();
const can = props?.auth?.can;
const { tabs } = usePaymentSettings();
const breadcrumbs: Array<Link> = [{ transChoiceKey: 'settings', href: route('settings.index') }, { transChoiceKey: 'payment' }];
</script>

<template>
    <Head :title="$tChoice('trans.payment', 2)" />
    <PageContainer :breadcrumbs="breadcrumbs">
        <AvatarTitleList
            v-if="can['viewAny:payment-methods'] || can['view:payment-methods'] || can['viewAny:payment-days'] || can['view:payment-days'] || can['viewAny:payment-frequencies'] || can['view:payment-frequencies']"
            :tabs="tabs"
        />
        <BaseAlert v-else :description="$t('trans.forbidden_message')" :title="$t('trans.forbidden')" />
    </PageContainer>
</template>
