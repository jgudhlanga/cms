<script setup lang="ts">
import { Head } from '@inertiajs/vue3';

import PageContainer from '@/components/core/page/PageContainer.vue';
import CustomSeparator from '@/components/core/util/CustomSeparator.vue';
import DataListTable from '@/pages/enrolments/partials/DataListTable.vue';
import { AuthObject, DataListProps } from '@/types/data-pagination';
import { Enrolment } from '@/types/enrolments';
import { Link } from '@/types/ui';

interface Props {
    enrolmentWithoutOLevel: DataListProps<Enrolment>;
    enrolmentWithFewerThanFive: DataListProps<Enrolment>;
    noApplicationsFeePaid: DataListProps<Enrolment>;
    auth: AuthObject;
    errors: object;
}

defineProps<Props>();
const breadcrumbs: Array<Link> = [
    { transKey: 'dashboard', href: route('dashboard') },
    { transChoiceKey: 'enrolment', href: route('enrolments.index') },
    { title: 'Faulty Applications' },
];
</script>

<template>
    <Head :title="$tChoice('enrolment', 2)" />
    <PageContainer :breadcrumbs="breadcrumbs">
        <DataListTable :enrolments="enrolmentWithoutOLevel.data" :title="$t('trans.ui_entry_level_applications_without_olevel_results')" />
        <CustomSeparator classes="h-1 my-5" />
        <DataListTable
            :enrolments="enrolmentWithFewerThanFive.data"
            :title="$t('trans.ui_entry_level_applications_with_olevel_results_less_than_requi')"
        />
        <CustomSeparator classes="h-1 my-5" />
        <DataListTable :enrolments="noApplicationsFeePaid.data" :title="$t('trans.ui_applications_without_application_fee_paid')" />
        <CustomSeparator classes="h-1 my-5" />
    </PageContainer>
</template>
