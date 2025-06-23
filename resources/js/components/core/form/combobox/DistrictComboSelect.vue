<script lang="ts" setup>
import { computed, onMounted } from 'vue';
import { SelectOption } from '@/types/utils';
import BaseCombobox from '@/components/core/form/combobox/BaseCombobox.vue';
import { clearFormErrors } from '@/lib/forms';
import { InertiaForm } from '@inertiajs/vue3';
import { debounce } from 'lodash';
import { useDistricts } from '@/composables/shared/useDistricts';
import { District } from '@/types/settings';

interface Props {
	form: InertiaForm<any>,
}
const { isLoading, districts, listDistricts } = useDistricts();
onMounted(async () => {
	await listDistricts();
});
const props = defineProps<Props>();
const options = computed(() => {
	return districts.value.map((district: District) => <SelectOption>{
		value: Number(district.id),
		label: district?.attributes?.name
	});
});

const whenSearch = debounce(async (search: string) => {
	clearFormErrors(props.form, 'district');
	await listDistricts(search);
}, 600);
</script>

<template>
	<BaseCombobox
		:label="$tChoice('trans.district', 1)"
		:options="options"
		:on-search="async (search: string) => await whenSearch(search)"
		:is-loading="isLoading"
		v-bind="$attrs"
	/>
</template>

