<script setup lang="ts">
import PageContainer from '@/components/core/page/PageContainer.vue';
import CreateEditForm from '@/pages/institution/document-templates/partials/CreateEditForm.vue';
import { AuthObject } from '@/types/data-pagination';
import type { Link } from '@/types/ui';
import { Head } from '@inertiajs/vue3';

interface Props {
    documentTemplate: any;
    auth: AuthObject;
    errors: object;
}

const props = defineProps<Props>();

const breadcrumbs: Array<Link> = [
    { transChoiceKey: 'institution', href: route('institution.index') },
    { transKey: 'config', href: route('institution.setup') },
    { transChoiceKey: 'document_template', href: route('document-templates.index') },
    { title: props.documentTemplate.name ?? '' },
    { transKey: 'edit' },
];
</script>

<template>
    <Head :title="documentTemplate.name ?? ''" />
    <PageContainer :breadcrumbs="breadcrumbs">
        <CreateEditForm :documentTemplate="documentTemplate" />
    </PageContainer>
</template>
