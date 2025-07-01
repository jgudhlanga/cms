<script setup lang="ts">
import PageContainer from '@/components/core/page/PageContainer.vue';
import type { Link } from '@/types/ui';
import { Head } from '@inertiajs/vue3';

const breadcrumbs: Array<Link> = [
    { transChoiceKey: 'institution', href: route('institution.index') },
    { transKey: 'portal_setup', href: route('portal.setup') },
    { transKey: 'application_workflows' },
];
</script>

<template>
    <Head :title="$t('trans.application_workflows')" />
    <PageContainer :breadcrumbs="breadcrumbs"> No data! </PageContainer>
</template>
