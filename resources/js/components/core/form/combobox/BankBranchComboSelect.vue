<script lang="ts" setup>
import { computed, onMounted, watch } from 'vue';
import { SelectOption } from '@/types/utils';
import BaseCombobox from '@/components/core/form/combobox/BaseCombobox.vue';
import { clearFormErrors } from '@/lib/forms';
import { InertiaForm } from '@inertiajs/vue3';
import { debounce } from 'lodash';
import { BankBranch } from '@/types/banks';
import { useBankBranches } from '@/composables/banks/useBankBranches';
import { useUtils } from '@/composables/core/useUtils';

interface Props {
	form: InertiaForm<any>,
	bankId: string,
	triggerSearch?: boolean
}

const { isLoading, bankBranches, listBankBranches } = useBankBranches();
const { isItTrue } = useUtils();
onMounted(async () => {
	if (props.bankId) {
		await listBankBranches(props.bankId);
	}
});
const props = withDefaults(defineProps<Props>(), {
	triggerSearch: true
});
const options = computed(() => {
	return bankBranches.value.map((bankBranch: BankBranch) => <SelectOption>{
		value: Number(bankBranch.id),
		label: bankBranch?.attributes?.name,
		code: bankBranch?.attributes?.code
	});
});

const whenSearch = debounce(async (search: string) => {
	clearFormErrors(props.form, 'bankBranch');
	if (props.bankId) {
		await listBankBranches(props.bankId, search);
	}
}, 600);

watch(() => props.bankId, async (newValue) => {
	if (isItTrue(props.triggerSearch)) {
		await listBankBranches(newValue?.toString() ?? '');
	}
});
</script>

<template>
	<BaseCombobox
		:label="$tChoice('trans.branch', 1)"
		:options="options"
		:placeholder="$t('trans.select_dependency_description',
		{field: $tChoice('trans.bank', 1).toLowerCase()})"
		:on-search="async (search: string) => await whenSearch(search)"
		:is-loading="isLoading"
		v-bind="$attrs"
	/>
</template>

