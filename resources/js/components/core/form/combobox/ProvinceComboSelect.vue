<script lang="ts" setup>
import { computed, onMounted } from 'vue';
import { SelectOption } from '@/types/utils';
import BaseCombobox from '@/components/core/form/combobox/BaseCombobox.vue';
import { clearFormErrors } from '@/lib/forms';
import { InertiaForm } from '@inertiajs/vue3';
import { debounce } from 'lodash';
import { useProvinces } from '@/composables/provinces/useProvinces';
import { Province } from '@/types/settings';

interface Props {
	form: InertiaForm<any>,
}
const { isLoading, provinces, listProvinces } = useProvinces();
onMounted(async () => {
	await listProvinces();
});
const props = defineProps<Props>();
const options = computed(() => {
	return provinces.value.map((province: Province) => <SelectOption>{
		value: Number(province.id),
		label: province?.attributes?.title
	});
});

const whenSearch = debounce(async (search: string) => {
	clearFormErrors(props.form, 'province');
	await listProvinces(search);
}, 600);
</script>

<template>
	<BaseCombobox
		:label="$tChoice('trans.province', 1)"
		:options="options"
		:on-search="async (search: string) => await whenSearch(search)"
		:is-loading="isLoading"
		v-bind="$attrs"
	/>
</template>

