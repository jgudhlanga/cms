<script setup lang="ts">
import PageContainer from '@/components/core/page/PageContainer.vue';
import CreateEditForm from '@/pages/institution/dropdowns/intakePeriods/offerLetters/CreateEditForm.vue';
import { AuthObject } from '@/types/data-pagination';
import { IntakePeriod, OfferLetterTemplate } from '@/types/institution';
import type { Link } from '@/types/ui';
import { Head } from '@inertiajs/vue3';

interface Props {
    intakePeriod: IntakePeriod;
    offerLetterTemplate: OfferLetterTemplate;
    auth: AuthObject;
    errors: object;
}

const props = defineProps<Props>();

const breadcrumbs: Array<Link> = [
    { transChoiceKey: 'institution', href: route('institution.index') },
    { transKey: 'config', href: route('institution.setup') },
    { transChoiceKey: 'intake_period', href: route('intake-periods.index') },
    {
        title: props.intakePeriod.attributes?.name ?? '',
        href: route('intake-periods.offer-letter-templates.index', props.intakePeriod.id),
    },
    { title: props.offerLetterTemplate.attributes?.name ?? '' },
    { transKey: 'edit' },
];
</script>

<template>
    <Head :title="offerLetterTemplate.attributes?.name ?? ''" />
    <PageContainer :breadcrumbs="breadcrumbs">
        <CreateEditForm :intake-period="intakePeriod" :offer-letter-template="offerLetterTemplate" />
    </PageContainer>
</template>
