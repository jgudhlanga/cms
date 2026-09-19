<script setup lang="ts">
import { Head } from '@inertiajs/vue3';

import PageContainer from '@/components/core/page/PageContainer.vue';
import DataTable from '@/components/core/table/DataTable.vue';
import { useOfferLetterTemplates } from '@/composables/institution/useOfferLetterTemplates';
import { useUtils } from '@/composables/core/useUtils';
import { getIdParams } from '@/lib/utils';
import { hasAbility } from '@/lib/permissions';
import { AuthObject, DataFilters, DataListProps } from '@/types/data-pagination';
import { IntakePeriod, OfferLetterTemplate } from '@/types/institution';
import type { Link } from '@/types/ui';

const props = defineProps<{
    intakePeriod: IntakePeriod;
    offerLetterTemplates: DataListProps<OfferLetterTemplate>;
    trashedCount: any;
    filters: DataFilters;
    auth: AuthObject;
    errors: object;
}>();

const { createOfferLetterTemplateColumns } = useOfferLetterTemplates();
const { navigateTo } = useUtils();
const intakeId = getIdParams(props.intakePeriod.id?.toString() ?? '');

const breadcrumbs: Array<Link> = [
    { transChoiceKey: 'institution', href: route('institution.index') },
    { transKey: 'config', href: route('institution.setup') },
    { transChoiceKey: 'intake_period', href: route('intake-periods.index') },
    { title: props.intakePeriod.attributes?.name ?? '' },
    { transKey: 'offer_letter_templates' },
];
</script>

<template>
    <Head :title="$t('trans.offer_letter_templates')" />
    <PageContainer :breadcrumbs="breadcrumbs">
        <p class="mb-4 max-w-4xl text-sm text-muted-foreground">{{ $t('trans.offer_letter_template_page_help') }}</p>
        <DataTable
            :data="offerLetterTemplates.data"
            :trashed-count="trashedCount"
            :filters="filters"
            :search-url="route('intake-periods.offer-letter-templates.index', intakeId)"
            :pagination="{ ...offerLetterTemplates.links, ...offerLetterTemplates.meta }"
            :columns="createOfferLetterTemplateColumns(intakePeriod)"
            :on-create="() => navigateTo(route('intake-periods.offer-letter-templates.create', intakeId))"
            :disable-create="!hasAbility('update:intake-periods')"
        />
    </PageContainer>
</template>
