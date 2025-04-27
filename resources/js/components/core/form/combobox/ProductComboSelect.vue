<script lang="ts" setup>
import { computed, onMounted } from 'vue';
import { SelectOption } from '@/types/utils';
import { Product } from '@/types/products';
import { useProducts } from '@/composables/products/useProducts';
import { InertiaForm } from '@inertiajs/vue3';
import { debounce } from 'lodash';
import { clearFormErrors } from '@/lib/forms';
import BaseCombobox from '@/components/core/form/combobox/BaseCombobox.vue';

interface Props {
	form: InertiaForm<any>;
}

const props = defineProps<Props>();
const { isLoading, listProducts, products } = useProducts();

onMounted(async () => {
	await listProducts();
});

const options = computed(() => {
	return products.value.map((product: Product) => <SelectOption>{
		value: Number(product.id),
		label: product?.attributes?.title
	});
});

const whenSearch = debounce(async (search: string) => {
	clearFormErrors(props.form, 'product');
	await listProducts(search);
}, 600);
</script>

<template>
	<BaseCombobox
		:label="$tChoice('trans.product', 1)"
		:options="options"
		:is-loading="isLoading"
		:on-search="async (search: string) => await whenSearch(search)"
		v-bind="$attrs"
	/>
</template>

