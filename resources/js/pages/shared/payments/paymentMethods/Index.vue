<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { AuthObject, DataFilters, DataListProps } from '@/types/data-pagination';
import DataTable from '@/components/core/table/DataTable.vue';
import { usePaymentMethods } from '@/composables/shared/usePaymentMethods';
import PageContainer from '@/components/core/page/PageContainer.vue';
import CreateEdit from '@/pages/shared/payments/paymentMethods/partials/CreateEdit.vue';

const { createPaymentMethodColumns, breadcrumbs, onOpenModal } = usePaymentMethods();

const props = defineProps<{
	paymentMethods: DataListProps,
	trashedCount: any,
	filters: DataFilters,
	auth: AuthObject,
	errors: Object
}>();
const can = props?.auth?.can;
</script>

<template>
	<Head :title="$tChoice('trans.payment_method', 2)" />
	<PageContainer :breadcrumbs="breadcrumbs">
		<DataTable
			:data="paymentMethods.data"
			:trashed-count="trashedCount"
			:filters="filters"
			:search-url="route('payment-methods.index')"
			:pagination="{...paymentMethods.links, ...paymentMethods.meta}"
			:columns="createPaymentMethodColumns()"
			:on-create="() => onOpenModal(can['create:settings']) "
			:disable-create="!can['create:settings']"
		/>
		<CreateEdit />
	</PageContainer>
</template>
