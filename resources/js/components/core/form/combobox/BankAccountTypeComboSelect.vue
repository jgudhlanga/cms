<script lang="ts" setup>
import { computed, onMounted } from 'vue';
import { SelectOption } from '@/types/utils';
import BaseCombobox from '@/components/core/form/combobox/BaseCombobox.vue';
import { clearFormErrors } from '@/lib/forms';
import { InertiaForm } from '@inertiajs/vue3';
import { debounce } from 'lodash';
import { useBankAccountTypes } from '@/composables/banks/useBankAccountTypes';
import { BankAccountType } from '@/types/banks';


interface Props {
	form: InertiaForm<any>,
}

const { isLoading, bankAccountTypes, listBankAccountTypes } = useBankAccountTypes();
onMounted(async () => {
	await listBankAccountTypes();
});
const props = defineProps<Props>();
const options = computed(() => {
	return bankAccountTypes.value.map((bankAccountType: BankAccountType) => <SelectOption>{
		value: Number(bankAccountType.id),
		label: bankAccountType?.attributes?.title
	});
});

const whenSearch = debounce(async (search: string) => {
	clearFormErrors(props.form, 'bankAccountType');
	await listBankAccountTypes(search);
}, 600);
</script>

<template>
	<BaseCombobox
		:label="$tChoice('trans.account_type', 1)"
		:options="options"
		:on-search="async (search: string) => await whenSearch(search)"
		:is-loading="isLoading"
		v-bind="$attrs"
	/>
</template>

