<script setup lang="ts">

import GeneralContainer from '@/components/core/page/GeneralContainer.vue';
import GeneralPadding from '@/components/core/page/GeneralPadding.vue';
import BaseAccordion from '@/components/core/accordion/BaseAccordion.vue';
import DataTable from '@/components/core/table/DataTable.vue';
import { Address } from '@/types/shared';
import { useAddresses } from '@/composables/shared/useAddresses';
import { usePage } from '@inertiajs/vue3';
import { PageProps } from '@/types';

const { createAddressColumns, onOpenModal } = useAddresses();

interface Props {
	addresses: Address[],
}

defineProps<Props>();
const { props } = usePage<PageProps>();
const { can } = props?.auth;
</script>

<template>
	<GeneralContainer>
		<template #body>
			<GeneralPadding>
				<BaseAccordion value="address_details" :title="$tChoice('trans.address', 2)"
				               default-value="address_details">
					<DataTable
						:data="addresses"
						:columns="createAddressColumns()"
						:on-create="() => onOpenModal(can['create:schemes'])"
						:disable-create="!can['create:schemes']"
						:show-archived-filter="false"
					/>
				</BaseAccordion>
			</GeneralPadding>
		</template>
	</GeneralContainer>
</template>
