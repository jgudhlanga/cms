<script setup lang="ts">
import PageContainer from '@/components/core/page/PageContainer.vue';
import DataTable from '@/components/core/table/DataTable.vue';
import { usePaymentDays } from '@/composables/shared/usePaymentDays';
import CreateEdit from '@/pages/shared/payments/paymentDays/partials/CreateEdit.vue';
import { AuthObject, DataFilters, DataListProps } from '@/types/data-pagination';
import { Head } from '@inertiajs/vue3';

const { createPaymentDayColumns, breadcrumbs, onOpenModal } = usePaymentDays();

const props = defineProps<{
    paymentDays: DataListProps;
    trashedCount: any;
    filters: DataFilters;
    auth: AuthObject;
    errors: object;
}>();
const can = props?.auth?.can;
</script>

<template>
    <Head :title="$tChoice('trans.payment_day', 2)" />
    <PageContainer :breadcrumbs="breadcrumbs">
        <DataTable
            :data="paymentDays.data"
            :trashed-count="trashedCount"
            :filters="filters"
            :search-url="route('payment-days.index')"
            :pagination="{ ...paymentDays.links, ...paymentDays.meta }"
            :columns="createPaymentDayColumns()"
            :on-create="() => onOpenModal(can['create:settings'])"
            :disable-create="!can['create:settings']"
        />
        <CreateEdit />
    </PageContainer>
</template>
