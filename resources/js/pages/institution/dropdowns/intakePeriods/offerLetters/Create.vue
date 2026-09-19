<script setup lang="ts">
import PageContainer from '@/components/core/page/PageContainer.vue';
import CreateEditForm from '@/pages/institution/dropdowns/intakePeriods/offerLetters/CreateEditForm.vue';
import { AuthObject } from '@/types/data-pagination';
import { IntakePeriod } from '@/types/institution';
import type { Link } from '@/types/ui';
import { Head } from '@inertiajs/vue3';

interface Props {
    intakePeriod: IntakePeriod;
    auth: AuthObject;
    errors: object;
}

const props = defineProps<Props>();

const breadcrumbs: Array<Link> = [
    { transChoiceKey: 'institution', href: route('institution.index') },
    { transKey: 'config', href: route('institution.setup') },
    { transChoiceKey: 'intake_period', href: route('intake-periods.index') },
    { title: props.intakePeriod.attributes?.name ?? '', href: route('intake-periods.offer-letter-templates.index', props.intakePeriod.id) },
    { transKey: 'create' },
];
</script>

<template>
    <Head :title="$tChoice('trans.offer_letter_template', 1)" />
    <PageContainer :breadcrumbs="breadcrumbs">
        <CreateEditForm :intake-period="intakePeriod" />
    </PageContainer>
</template>
