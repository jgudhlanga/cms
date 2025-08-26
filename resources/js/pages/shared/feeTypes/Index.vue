<script setup lang="ts">
import { Head } from '@inertiajs/vue3';

import PageContainer from '@/components/core/page/PageContainer.vue';
import DataTable from '@/components/core/table/DataTable.vue';
import { useFeeTypes } from '@/composables/shared/useFeeTypes';
import { hasAbility } from '@/lib/permissions';
import { AuthObject, DataFilters, DataListProps } from '@/types/data-pagination';
import CreateEdit from './partials/CreateEdit.vue';

const { createFeeTypeColumns, breadcrumbs, onOpenModal } = useFeeTypes();

defineProps<{
    feeTypes: DataListProps;
    trashedCount: any;
    filters: DataFilters;
    auth: AuthObject;
    errors: object;
}>();
const allowed = hasAbility('create:settings');
</script>

<template>
    <Head :idType="$tChoice('trans.fee_type', 2)" />
    <PageContainer :breadcrumbs="breadcrumbs">
        <DataTable
            :data="feeTypes.data"
            :trashed-count="trashedCount"
            :filters="filters"
            :search-url="route('fee-types.index')"
            :pagination="{ ...feeTypes.links, ...feeTypes.meta }"
            :columns="createFeeTypeColumns()"
            :on-create="() => onOpenModal(allowed)"
            :disable-create="!allowed"
        />
        <CreateEdit />
    </PageContainer>
</template>
