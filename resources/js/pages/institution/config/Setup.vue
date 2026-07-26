<script setup lang="ts">
import BaseAlert from '@/components/core/alert/BaseAlert.vue';
import PageContainer from '@/components/core/page/PageContainer.vue';
import AvatarTitleList from '@/components/core/util/AvatarTitleList.vue';
import HeadingSmall from '@/components/core/util/HeadingSmall.vue';
import { useInstitutionSetup } from '@/composables/settings/useInstitutionSetup';
import type { Link } from '@/types/ui';
import { Head } from '@inertiajs/vue3';

const breadcrumbs: Array<Link> = [
    {
        transChoiceKey: 'institution',
        href: route('institution.index'),
    },
    { transKey: 'institution_setup' },
];

const { visibleTabs } = useInstitutionSetup();
const allowed = visibleTabs.length > 0;
</script>

<template>
    <Head :title="$t('trans.institution_setup')" />
    <PageContainer :breadcrumbs="breadcrumbs">
        <template v-if="allowed && visibleTabs.length > 0">
            <HeadingSmall :title="$t('trans.institution_config')" :description="$t('trans.institution_config_description')" />
            <AvatarTitleList :tabs="visibleTabs" />
        </template>
        <BaseAlert v-if="!allowed || visibleTabs.length === 0" :title="$t('trans.forbidden')" :description="$t('trans.forbidden_message')" />
    </PageContainer>
</template>
