<script setup lang="ts">
import HeadingSmall from '@/components/core/util/HeadingSmall.vue';
import { useInstitutionSetup } from '@/composables/settings/useInstitutionSetup';
import { hasAbility } from '@/lib/permissions';
import type { Link } from '@/types/ui';
import { Head } from '@inertiajs/vue3';

const breadcrumbs: Array<Link> = [
    {
        transChoiceKey: 'institution',
        href: route('institution.index'),
    },
    { transKey: 'institution_setup' },
];

const { configTabs, dropdownTabs } = useInstitutionSetup();
const tabs = [...configTabs, ...dropdownTabs];
const allowed = hasAbility('view:institution-settings');
</script>

<template>
    <Head :title="$t('trans.institution_setup')" />
    <PageContainer :breadcrumbs="breadcrumbs">
        <template v-if="allowed">
            <HeadingSmall :title="$t('trans.institution_config')" :description="$t('trans.institution_config_description')" />
            <AvatarTitleList :tabs="tabs" />
        </template>
        <BaseAlert v-if="!allowed" :title="$t('trans.forbidden')" :description="$t('trans.forbidden_message')" />
    </PageContainer>
</template>
