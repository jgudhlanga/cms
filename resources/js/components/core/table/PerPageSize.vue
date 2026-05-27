<script setup lang="ts">
import { ref } from 'vue';
import BaseSelect from '../form/select/BaseSelect.vue';
import ItemTitle from '@/components/core/util/ItemTitle.vue';
import { PAGINATION_MAX_LIMIT } from '@/lib/constants';


const pageSizeOptions = ref([
	{ value: 5, label: '5' },
	{ value: 10, label: '10' },
	{ value: 15, label: '15' },
	{ value: 20, label: '20' },
	{ value: 50, label: '50' },
	{ value: PAGINATION_MAX_LIMIT, label: 'All' },
]);


</script>

<template>
	<div class="flex w-full  items-center space-x-2">
		<ItemTitle :title="`${$t('trans.rows_per_page')}:`" :uppercase="false" />
		<BaseSelect
            class="w-[200px]"
			label=""
			v-bind="$attrs"
			placeholder=""
			:options="pageSizeOptions"
			:is-searchable="false"
			:is-clearable="false"
			:vertical-layout="false"
		/>
	</div>
</template>

