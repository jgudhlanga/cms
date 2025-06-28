<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { AuthObject, DataFilters, DataListProps } from '@/types/data-pagination';
import DataTable from '@/components/core/table/DataTable.vue';
import { usePaymentDays } from '@/composables/shared/usePaymentDays';
import PageContainer from '@/components/core/page/PageContainer.vue';
import CreateEdit from '@/pages/shared/payments/paymentDays/partials/CreateEdit.vue';

const { createPaymentDayColumns, breadcrumbs, onOpenModal } = usePaymentDays();

const props = defineProps<{
	paymentDays: DataListProps,
	trashedCount: any,
	filters: DataFilters,
	auth: AuthObject,
	errors: Object
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
			:pagination="{...paymentDays.links, ...paymentDays.meta}"
			:columns="createPaymentDayColumns()"
			:on-create="() => onOpenModal(can['create:settings'])"
			:disable-create="!can['create:settings']"
		/>
		<CreateEdit />
	</PageContainer>
</template>
