<script setup lang="ts">
import { Head } from '@inertiajs/vue3';

import PageContainer from '@/components/core/page/PageContainer.vue';
import DataTable from '@/components/core/table/DataTable.vue';
import { useIntakePeriods } from '@/composables/institution/useIntakePeriods';
import { hasAbility } from '@/lib/permissions';
import { AuthObject, DataFilters, DataListProps } from '@/types/data-pagination';
import type { Link } from '@/types/ui';
import CreateEdit from './partials/CreateEdit.vue';

const { createIntakePeriodColumns, onOpenModal } = useIntakePeriods();

defineProps<{
    intakePeriods: DataListProps;
    trashedCount: any;
    filters: DataFilters;
    auth: AuthObject;
    errors: object;
}>();
const breadcrumbs: Array<Link> = [
    { transChoiceKey: 'institution', href: route('institution.index') },
    { transKey: 'config', href: route('institution.setup') },
    { transChoiceKey: 'intake_period' },
];
const allowed = hasAbility('create:institution-settings');
</script>

<template>
    <Head :title="$tChoice('trans.intake_period', 2)" />
    <PageContainer :breadcrumbs="breadcrumbs">
        <DataTable
            :data="intakePeriods.data"
            :trashed-count="trashedCount"
            :filters="filters"
            :search-url="route('intake-periods.index')"
            :pagination="{ ...intakePeriods.links, ...intakePeriods.meta }"
            :columns="createIntakePeriodColumns()"
            :on-create="() => onOpenModal(allowed)"
            :disable-create="!allowed"
        />
        <CreateEdit />
    </PageContainer>
</template>
