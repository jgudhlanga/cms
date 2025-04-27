<script lang="ts" setup>
import { computed, onMounted } from 'vue';
import { SelectOption } from '@/types/utils';
import BaseCombobox from '@/components/core/form/combobox/BaseCombobox.vue';
import { clearFormErrors } from '@/lib/forms';
import { InertiaForm } from '@inertiajs/vue3';
import { debounce } from 'lodash';
import { useProductTypes } from '@/composables/products/useProductTypes';
import { ProductType } from '@/types/products';

interface Props {
	form: InertiaForm<any>,
}

const { isLoading, productTypes, listProductTypes } = useProductTypes();
onMounted(async () => {
	await listProductTypes();
});
const props = defineProps<Props>();
const options = computed(() => {
	return productTypes.value.map((productType: ProductType) => <SelectOption>{
		value: Number(productType.id),
		label: productType?.attributes?.title,
		auxLabel: productType?.attributes?.maximumAgeLimit
	});
});

const whenSearch = debounce(async (search: string) => {
	clearFormErrors(props.form, 'productType');
	await listProductTypes(search);
}, 600);
</script>

<template>
	<BaseCombobox
		:label="$tChoice('trans.product_type', 1)"
		:options="options"
		:on-search="async (search: string) => await whenSearch(search)"
		:is-loading="isLoading"
		v-bind="$attrs"
	/>
</template>

