<script setup lang="ts">
import { Head } from '@inertiajs/vue3';

import PageContainer from '@/components/core/page/PageContainer.vue';
import DataTable from '@/components/core/table/DataTable.vue';
import { useUtils } from '@/composables/core/useUtils';
import { useDocumentTemplates } from '@/composables/institution/useDocumentTemplates';
import { hasAbility } from '@/lib/permissions';
import { AuthObject, DataFilters, DataListProps } from '@/types/data-pagination';
import { DocumentTemplate } from '@/types/institution';
import type { Link } from '@/types/ui';

defineProps<{
    documentTemplates: DataListProps<DocumentTemplate>;
    trashedCount: any;
    filters: DataFilters;
    auth: AuthObject;
    errors: object;
}>();
const breadcrumbs: Array<Link> = [
    { transChoiceKey: 'institution', href: route('institution.index') },
    { transKey: 'config', href: route('institution.setup') },
    { transChoiceKey: 'document_template' },
];

const { createDocumentTemplateColumns } = useDocumentTemplates();
const { navigateTo } = useUtils();
</script>

<template>
    <Head :title="$tChoice('trans.document_template', 2)" />
    <PageContainer :breadcrumbs="breadcrumbs">
        <DataTable
            :data="documentTemplates.data"
            :trashed-count="trashedCount"
            :filters="filters"
            :search-url="route('document-templates.index')"
            :pagination="{ ...documentTemplates.links, ...documentTemplates.meta }"
            :columns="createDocumentTemplateColumns()"
            :on-create="() => navigateTo(route('document-templates.create'))"
            :disable-create="!hasAbility('create:document-templates')"
        />
    </PageContainer>
</template>
