<script lang="ts" setup>
import { computed, onMounted } from 'vue';
import { SelectOption } from '@/types/utils';
import BaseCombobox from '@/components/core/form/combobox/BaseCombobox.vue';
import { clearFormErrors } from '@/lib/forms';
import { InertiaForm } from '@inertiajs/vue3';
import { debounce } from 'lodash';
import { useRoles } from '@/composables/acl/useRoles';
import { Role } from '@/types/acl';

interface Props {
    url: string;
	form?: InertiaForm<any>,
    labelUppercase?: boolean,
    isRequired?: boolean,
}
const { isLoading, roles, listRoles } = useRoles();
const props = defineProps<Props>();

const { url } = props;
onMounted(async () => {
	await listRoles(url);
});

const options = computed(() => {
	return roles?.value?.data?.map((role: Role) => <SelectOption>{
		value: Number(role.id),
		label: role?.attributes?.name
	});
});

const whenSearch = debounce(async (search: string) => {
	clearFormErrors(props.form, 'role');
	await listRoles(`${url}&search=${search}`);
}, 600);
</script>

<template>
	<BaseCombobox
		:label="$tChoice('trans.role', 1)"
		:options="options"
		:on-search="async (search: string) => await whenSearch(search)"
		:is-loading="isLoading"
        :label-uppercase="labelUppercase"
		v-bind="$attrs"
        :is-required="isRequired"
	/>
</template>

