<script lang="ts" setup>
import { computed, onMounted } from 'vue';
import { SelectOption } from '@/types/utils';
import { Package } from '@/types/products';
import { usePackages } from '@/composables/products/usePackages';
import BaseCombobox from '@/components/core/form/combobox/BaseCombobox.vue';
import { InertiaForm } from '@inertiajs/vue3';
import { debounce } from 'lodash';
import { clearFormErrors } from '@/lib/forms';

interface Props {
	form: InertiaForm<any>,
}

const props = defineProps<Props>();

const { isLoading, listPackages, packages } = usePackages();

onMounted(async () => {
	await listPackages();
});

const whenSearch = debounce(async (search: string) => {
	clearFormErrors(props.form, 'package');
	await listPackages(search);
}, 600);

const options = computed(() => {
	return packages.value.map((model: Package) => <SelectOption>{
		value: Number(model.id),
		label: model?.attributes?.title
	});
});
</script>

<template>
	<BaseCombobox
		:label="$tChoice('trans.package', 1)"
		:options="options"
		:is-loading="isLoading"
		:on-search="async (search: string) => await whenSearch(search)"
		v-bind="$attrs"
	/>
</template>
