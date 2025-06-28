<script lang="ts" setup>
import { computed, onMounted } from 'vue';
import { SelectOption } from '@/types/utils';
import BaseCombobox from '@/components/core/form/combobox/BaseCombobox.vue';
import { clearFormErrors } from '@/lib/forms';
import { InertiaForm } from '@inertiajs/vue3';
import { debounce } from 'lodash';
import { useCountries } from '@/composables/shared/useCountries';
import { Country } from '@/types/countries';

interface Props {
	form: InertiaForm<any>,
}
const { isLoading, countries, listCountries } = useCountries();
onMounted(async () => {
	await listCountries();
});
const props = defineProps<Props>();
const options = computed(() => {
	return countries.value.map((country: Country) => <SelectOption>{
		value: Number(country.id),
		label: country?.attributes?.name
	});
});

const whenSearch = debounce(async (search: string) => {
	clearFormErrors(props.form, 'country');
	await listCountries(search);
}, 600);
</script>

<template>
	<BaseCombobox
		:label="$tChoice('trans.country', 1)"
		:options="options"
		:on-search="async (search: string) => await whenSearch(search)"
		:is-loading="isLoading"
		v-bind="$attrs"
	/>
</template>

