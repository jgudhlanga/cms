<script lang="ts" setup>
import { computed, onMounted, ref, watch } from 'vue';
import { SelectOption } from '@/types/utils';
import { useAddressTypes } from '@/composables/addressTypes/useAddressTypes';
import { AddressType } from '@/types/settings';
import BaseCombobox from '@/components/core/form/combobox/BaseCombobox.vue';
import { debounce } from 'lodash';
import { clearFormErrors } from '@/lib/forms';
import { InertiaForm } from '@inertiajs/vue3';

interface Props {
	form: InertiaForm<any>,
}

const props = defineProps<Props>();
const { isLoading, listAddressTypes, addressTypes } = useAddressTypes();

onMounted(async () => {
	await listAddressTypes();
});

const whenSearch = debounce(async (search: string) => {
	clearFormErrors(props.form, 'addressType');
	await listAddressTypes(search);
}, 600);

const options = computed(() => {
	return addressTypes.value.map((addressType: AddressType) => <SelectOption>{
		value: Number(addressType.id),
		label: addressType?.attributes?.title
	});
});
</script>

<template>
	<BaseCombobox
		:label="$tChoice('trans.address_type', 1)"
		:options="options"
		:is-loading="isLoading"
		:on-search="async (search: string) => await whenSearch(search)"
		v-bind="$attrs"
	/>
</template>

