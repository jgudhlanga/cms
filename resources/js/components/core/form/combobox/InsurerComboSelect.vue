<script lang="ts" setup>
import { computed, onMounted } from 'vue';
import { SelectOption } from '@/types/utils';
import { Insurer } from '@/types/insurers';
import { useInsurers } from '@/composables/insurers/useInsurers';
import BaseCombobox from '@/components/core/form/combobox/BaseCombobox.vue';
import { InertiaForm } from '@inertiajs/vue3';
import { debounce } from 'lodash';
import { clearFormErrors } from '@/lib/forms';

interface Props {
	form: InertiaForm<any>,
}

const props = defineProps<Props>();
const { isLoading, listInsurers, insurers } = useInsurers();

onMounted(async () => {
	await listInsurers();
});
const whenSearch = debounce(async (search: string) => {
	clearFormErrors(props.form, 'insurer');
	await listInsurers(search);
}, 600);
const options = computed(() => {
	return insurers.value.map((insurer: Insurer) => <SelectOption>{
		value: Number(insurer.id),
		label: insurer?.attributes?.registeredName
	});
});
</script>

<template>
	<BaseCombobox
		:label="$tChoice('trans.insurer', 1)"
		:options="options"
		:is-loading="isLoading"
		:on-search="async (search: string) => await whenSearch(search)"
		v-bind="$attrs"
	/>
</template>

