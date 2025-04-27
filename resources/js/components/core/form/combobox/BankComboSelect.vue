<script lang="ts" setup>
import { computed, onMounted } from 'vue';
import { SelectOption } from '@/types/utils';
import BaseCombobox from '@/components/core/form/combobox/BaseCombobox.vue';
import { clearFormErrors } from '@/lib/forms';
import { InertiaForm } from '@inertiajs/vue3';
import { debounce } from 'lodash';
import { useBanks } from '@/composables/banks/useBanks';
import { Bank } from '@/types/banks';
interface Props {
	form: InertiaForm<any>,
}
const props = defineProps<Props>();
const { isLoading, banks, listBanks } = useBanks();
onMounted(async () => {
	await listBanks();
});
const options = computed(() => {
	return banks.value.map((bank: Bank) => <SelectOption>{
		value: Number(bank.id),
		label: bank?.attributes?.name,
		auxLabel: bank?.attributes?.mainBranchCode,
		relationshipOneValue: bank?.relationships?.mainBranch?.id,
		relationshipOneLabel: bank?.relationships?.mainBranch?.attributes?.name,
	});
});

const whenSearch = debounce(async (search: string) => {
	clearFormErrors(props.form, 'bank');
	clearFormErrors(props.form, 'bankBranch');
	await listBanks(search);
}, 600);
</script>

<template>
	<BaseCombobox
		:label="$tChoice('trans.bank', 1)"
		:options="options"
		:on-search="async (search: string) => await whenSearch(search)"
		:is-loading="isLoading"
		v-bind="$attrs"
	/>
</template>

