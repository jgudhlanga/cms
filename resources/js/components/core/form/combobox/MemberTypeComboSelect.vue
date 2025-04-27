<script lang="ts" setup>
import { computed, onMounted } from 'vue';
import { SelectOption } from '@/types/utils';
import { MemberType } from '@/types/products';
import { useMemberTypes } from '@/composables/products/useMemberTypes';
import { InertiaForm } from '@inertiajs/vue3';
import { debounce } from 'lodash';
import { clearFormErrors } from '@/lib/forms';
import BaseCombobox from '@/components/core/form/combobox/BaseCombobox.vue';

interface Props {
	form: InertiaForm<any>;
}

const props = defineProps<Props>();
const {isLoading, listMemberTypes, memberTypes} = useMemberTypes()

onMounted( async ()  => {
	await listMemberTypes()
})

const whenSearch = debounce(async (search: string) => {
	clearFormErrors(props.form, 'memberType');
	await listMemberTypes(search);
}, 600);

const options = computed(() => {
	return memberTypes.value.map((type: MemberType) => <SelectOption>{value: Number(type.id), label: type?.attributes?.title})
})
</script>

<template>
	<BaseCombobox
		:label="$tChoice('trans.member_type', 1)"
		:options="options"
		:is-loading="isLoading"
		:on-search="async (search: string) => await whenSearch(search)"
		v-bind="$attrs"
	/>
</template>
