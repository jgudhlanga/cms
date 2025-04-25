<script lang="ts" setup>
import { computed, onMounted, ref, watch, } from 'vue';
import { SelectOption } from '@/types/utils';
import BaseSelect from './BaseSelect.vue';
import { usePremiumMainMemberAgeRanges } from '@/composables/premiums/usePremiumMainMemberAgeRanges';
import { PremiumMainMemberAgeRange } from '@/types/premiums';

const {isLoading, listPremiumMainMemberAgeRanges, premiumMainMemberAgeRanges} = usePremiumMainMemberAgeRanges()

onMounted( async ()  => {
	await listPremiumMainMemberAgeRanges()
})

const searchValue = ref('')

watch(searchValue, async (value) => {
	await listPremiumMainMemberAgeRanges(value)
})


const options = computed(() => {
	return premiumMainMemberAgeRanges.value.map((type: PremiumMainMemberAgeRange) => <SelectOption>{value: Number(type.id), label: type?.attributes?.title})
})
</script>

<template>
	<BaseSelect
		:label="$tChoice('trans.main_member_age_range', 1)"
		v-bind="$attrs"
		:placeholder="$t('trans.search')"
		:options="options"
		:is-searchable="false"
		:is-clearable="false"
		:loading="isLoading"
	/>
</template>
