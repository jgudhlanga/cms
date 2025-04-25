<script lang="ts" setup>
import { computed, onMounted } from 'vue';
import { SelectOption } from '@/types/utils';
import { usePremiumMainMemberAgeRanges } from '@/composables/premiums/usePremiumMainMemberAgeRanges';
import { PremiumMainMemberAgeRange } from '@/types/premiums';
import BaseCombobox from '@/components/core/form/combobox/BaseCombobox.vue';
import { debounce } from 'lodash';
import { clearFormErrors } from '@/lib/forms';
import { InertiaForm } from '@inertiajs/vue3';

interface Props {
	form: InertiaForm<any>;
}

const props = defineProps<Props>();
const { isLoading, listPremiumMainMemberAgeRanges, premiumMainMemberAgeRanges } = usePremiumMainMemberAgeRanges();

onMounted(async () => {
	await listPremiumMainMemberAgeRanges();
});

const whenSearch = debounce(async (search: string) => {
	clearFormErrors(props.form, 'premiumMainMemberAgeRange');
	await listPremiumMainMemberAgeRanges(search);
}, 600);

const options = computed(() => {
	return premiumMainMemberAgeRanges.value.map((type: PremiumMainMemberAgeRange) => <SelectOption>{
		value: Number(type.id),
		label: type?.attributes?.title
	});
});
</script>

<template>
	<BaseCombobox
		:label="$tChoice('trans.main_member_age_range', 1)"
		:options="options"
		:is-loading="isLoading"
		:on-search="async (search: string) => await whenSearch(search)"
		v-bind="$attrs"
	/>
</template>
