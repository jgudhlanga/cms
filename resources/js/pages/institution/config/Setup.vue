<script setup lang="ts">
import BaseAlert from '@/components/core/alert/BaseAlert.vue';
import PageContainer from '@/components/core/page/PageContainer.vue';
import AvatarTitleList from '@/components/core/util/AvatarTitleList.vue';
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

const tabs: Array<Link> = [
    {
        transChoiceKey: 'intake_period',
        url: route('intake-periods.index'),
    },
    {
        transChoiceKey: 'document_template',
        url: route('document-templates.index'),
    },
    {
        transChoiceKey: 'fee_levy_structure',
        url: route('fee-structures.index'),
    },
];
const allowed = hasAbility('view:institution-settings');
</script>

<template>
    <Head :title="$t('trans.institution_setup')" />
    <PageContainer :breadcrumbs="breadcrumbs">
        <template v-if="allowed">
            <AvatarTitleList :tabs="tabs" />
        </template>
        <BaseAlert v-if="!allowed" :title="$t('trans.forbidden')" :description="$t('trans.forbidden_message')" />
    </PageContainer>
</template>
