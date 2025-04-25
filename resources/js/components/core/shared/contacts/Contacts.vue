<script setup lang="ts">

import GeneralContainer from '@/components/core/page/GeneralContainer.vue';
import GeneralPadding from '@/components/core/page/GeneralPadding.vue';
import BaseAccordion from '@/components/core/accordion/BaseAccordion.vue';
import DataTable from '@/components/core/table/DataTable.vue';
import { useContacts } from '@/composables/shared/useContacts';
import { Contact } from '@/types/shared';
import { usePage } from '@inertiajs/vue3';
import { PageProps } from '@/types';

const { createContactColumns, onOpenModal } = useContacts();

interface Props {
	contacts: Contact[],
}

defineProps<Props>();

const { props } = usePage<PageProps>();
const { can } = props?.auth;
</script>

<template>
	<GeneralContainer>
		<template #body>
			<GeneralPadding>
				<BaseAccordion value="contact_details" :title="`${$tChoice('trans.contact', 1)} ${$t('trans.details')}`"
				               default-value="contact_details">
					<DataTable
						:data="contacts"
						:columns="createContactColumns()"
						:on-create="() => onOpenModal(can['create:schemes']) "
						:disable-create="!can['create:schemes']"
						:show-archived-filter="false"
					/>
				</BaseAccordion>
			</GeneralPadding>
		</template>
	</GeneralContainer>
</template>
