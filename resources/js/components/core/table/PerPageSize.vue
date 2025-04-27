<script setup lang="ts">
import { ref } from 'vue';
import BaseSelect from '../form/select/BaseSelect.vue';
import ItemTitle from '@/components/core/util/ItemTitle.vue';


const pageSizeOptions = ref([
	{ value: 5, label: '5' },
	{ value: 10, label: '10' },
	{ value: 15, label: '15' },
	{ value: 20, label: '20' },
	{ value: 25, label: '25' },
	{ value: 50, label: '50' }
]);


</script>

<template>
	<div class="flex  items-center space-x-2">
		<ItemTitle :title="`${$t('trans.rows_per_page')}:`" :uppercase="false" />
		<BaseSelect
			label=""
			v-bind="$attrs"
			placeholder=""
			:options="pageSizeOptions"
			:is-searchable="false"
			:is-clearable="false"
		/>
	</div>
</template>

