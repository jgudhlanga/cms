<script setup lang="ts">
import { Head } from '@inertiajs/vue3';

import PageContainer from '@/components/core/page/PageContainer.vue';
import DataTable from '@/components/core/table/DataTable.vue';
import { useFinanceExchangeRates } from '@/composables/finance/useFinanceExchangeRates';
import { AuthObject, DataFilters, DataListProps } from '@/types/data-pagination';
import CreateEdit from './partials/CreateEdit.vue';

const { createExchangeRateColumns, breadcrumbs, onOpenModal } = useFinanceExchangeRates();

const props = defineProps<{
    exchangeRates: DataListProps;
    trashedCount: any;
    filters: DataFilters;
    auth: AuthObject;
    errors: object;
}>();

const can = props?.auth?.can;
</script>

<template>
    <Head :title="$tChoice('finance.exchange_rate', 2)" />

    <PageContainer :breadcrumbs="breadcrumbs">
        <DataTable
            :data="exchangeRates.data"
            :trashed-count="trashedCount"
            :filters="filters"
            :search-url="route('finance.exchange-rates.index')"
            :pagination="{ ...exchangeRates.links, ...exchangeRates.meta }"
            :columns="createExchangeRateColumns()"
            :on-create="() => onOpenModal(can['create:finance-settings'])"
            :disable-create="!can['create:finance-settings']"
        />

        <CreateEdit />
    </PageContainer>
</template>
