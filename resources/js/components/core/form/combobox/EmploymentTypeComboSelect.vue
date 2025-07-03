<script lang="ts" setup>
import { computed, onMounted } from 'vue';
import { SelectOption } from '@/types/utils';
import BaseCombobox from '@/components/core/form/combobox/BaseCombobox.vue';
import { clearFormErrors } from '@/lib/forms';
import { InertiaForm } from '@inertiajs/vue3';
import { debounce } from 'lodash';
import { useTitles } from '@/composables/shared/useTitles';
import { EmploymentType, Title } from '@/types/settings';
import { useEmploymentTypes } from '@/composables/shared/useEmploymentTypes';

interface Props {
	form: InertiaForm<any>,
    labelUppercase?: boolean,
    isRequired?: boolean,
}
const { isLoading, employmentTypes, listEmploymentTypes } = useEmploymentTypes();
onMounted(async () => {
	await listEmploymentTypes();
});
const props = defineProps<Props>();
const options = computed(() => {
	return employmentTypes.value.map((type: EmploymentType) => <SelectOption>{
		value: Number(type.id),
		label: type?.attributes?.name
	});
});

const whenSearch = debounce(async (search: string) => {
	clearFormErrors(props.form, 'employmentType');
	await listEmploymentTypes(search);
}, 600);
</script>

<template>
	<BaseCombobox
		:label="$tChoice('trans.employment_type', 1)"
		:options="options"
		:on-search="async (search: string) => await whenSearch(search)"
		:is-loading="isLoading"
        :label-uppercase="labelUppercase"
		v-bind="$attrs"
        :is-required="isRequired"
	/>
</template>

