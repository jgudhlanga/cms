<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { AuthObject, DataFilters, DataListProps } from '@/types/data-pagination';
import { usePaymentFrequencies } from '@/composables/payments/usePaymentFrequencies';
import PageContainer from '@/components/core/page/PageContainer.vue';
import DataTable from '@/components/core/table/DataTable.vue';
import CreateEdit from '@/pages/payments/paymentFrequencies/partials/CreateEdit.vue';

const { createPaymentFrequencyColumns, breadcrumbs, onOpenModal } = usePaymentFrequencies();

const props = defineProps<{
	paymentFrequencies: DataListProps,
	trashedCount: any,
	filters: DataFilters,
	auth: AuthObject,
	errors: Object
}>();
const can = props?.auth?.can;
</script>

<template>
	<Head :title="$tChoice('trans.payment_frequency', 2)" />
	<PageContainer :breadcrumbs="breadcrumbs">
		<DataTable
			:data="paymentFrequencies.data"
			:trashed-count="trashedCount"
			:filters="filters"
			:search-url="route('payment-frequencies.index')"
			:pagination="{...paymentFrequencies.links, ...paymentFrequencies.meta}"
			:columns="createPaymentFrequencyColumns()"
			:on-create="() => onOpenModal(can['create:settings']) "
			:disable-create="!can['create:settings']"
		/>
		<CreateEdit />
	</PageContainer>
</template>
