<script lang="ts" setup>
import { computed, onMounted } from 'vue';
import { SelectOption } from '@/types/utils';
import { useTradingStatuses } from '@/composables/statuses/useTradingStatuses';
import BaseCombobox from '@/components/core/form/combobox/BaseCombobox.vue';
import { clearFormErrors } from '@/lib/forms';
import { InertiaForm } from '@inertiajs/vue3';
import { debounce } from 'lodash';
import { TradingStatus } from '@/types/settings';

interface Props {
	form: InertiaForm<any>,
}

const { isLoading, tradingStatuses, listTradingStatuses } = useTradingStatuses();
onMounted(async () => {
	await listTradingStatuses();
});
const props = defineProps<Props>();
const options = computed(() => {
	return tradingStatuses.value.map((tradingStatus: TradingStatus) => <SelectOption>{
		value: Number(tradingStatus.id),
		label: tradingStatus?.attributes?.title
	});
});

const whenSearch = debounce(async (search: string) => {
	clearFormErrors(props.form, 'tradingStatus');
	await listTradingStatuses(search);
}, 600);
</script>

<template>
	<BaseCombobox
		:label="$tChoice('trans.trading_status', 1)"
		:options="options"
		:on-search="async (search: string) => await whenSearch(search)"
		:is-loading="isLoading"
		v-bind="$attrs"
	/>
</template>

