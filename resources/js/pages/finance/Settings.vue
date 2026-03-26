<script setup lang="ts">
import BaseAlert from '@/components/core/alert/BaseAlert.vue';
import PageContainer from '@/components/core/page/PageContainer.vue';
import AvatarTitleList from '@/components/core/util/AvatarTitleList.vue';
import HeadingSmall from '@/components/core/util/HeadingSmall.vue';
import { useFinanceSettings } from '@/composables/finance/useFinanceSettings';
import { AuthObject } from '@/types/data-pagination';
import { Link } from '@/types/ui';
import { Head } from '@inertiajs/vue3';

const props = defineProps<{
    auth: AuthObject;
    errors: object;
}>();
const can = props?.auth?.can;
const { tabs } = useFinanceSettings();

const breadcrumbs: Array<Link> = [
    { transChoiceKey: 'finance.finance', transChoiceKeyIndex: 1, href: route('finance.index') },
    { transChoiceKey: 'finance.setting' },
];
</script>

<template>
    <Head :title="$t('finance.setting')" />
    <PageContainer :breadcrumbs="breadcrumbs">
        <HeadingSmall :title="$tChoice('finance.setting', 2)" :description="$t('trans.general_settings_description')" />
        <AvatarTitleList v-if="can['view:finance-settings']" :tabs="tabs" />
        <BaseAlert
            v-else
            :title="$t('trans.forbidden')"
            :description="$t('trans.forbidden_message')"
        />
    </PageContainer>
</template>
