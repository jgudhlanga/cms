<script setup lang="ts">
import { Head } from '@inertiajs/vue3';

import PageContainer from '@/components/core/page/PageContainer.vue';
import DataTable from '@/components/core/table/DataTable.vue';
import { useEnrolments } from '@/composables/students/useEnrolments';
import { AuthObject, DataFilters, DataListProps } from '@/types/data-pagination';
import { Link } from '@/types/ui';

const { enrolmentColumns } = useEnrolments();

interface Props {
    enrolments: DataListProps;
    trashedCount: any;
    filters: DataFilters;
    auth: AuthObject;
    errors: object;
}

defineProps<Props>();
const breadcrumbs: Array<Link> = [{ transKey: 'dashboard', href: route('dashboard') }, { transChoiceKey: 'enrolment' }];
</script>

<template>
    <Head :title="$tChoice('enrolment', 2)" />
    <PageContainer :breadcrumbs="breadcrumbs">
        <DataTable
            :data="enrolments.data"
            :trashed-count="trashedCount"
            :filters="filters"
            :show-archived-filter="false"
            :search-url="route('enrolments.index')"
            :pagination="{ ...enrolments.links, ...enrolments.meta }"
            :columns="enrolmentColumns()"
        />
    </PageContainer>
</template>
