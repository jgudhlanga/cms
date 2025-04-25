<script setup lang="ts">
import { computed } from 'vue';
import BaseSelect from '../form/select/BaseSelect.vue';
import { PaginationLink, PaginationMeta } from '@/types/data-pagination';
import ItemTitle from '@/components/core/util/ItemTitle.vue';

const props = defineProps<{ meta: PaginationMeta | null }>();

const linksOptions = computed(() => props?.meta?.links ? props?.meta?.links
	.filter((row: PaginationLink) => Number(row.label) > 0)
	.map((row: PaginationLink) => <any>{
		value: row.label ? +row.label : null, label: row.label, active: row.active
	}) : []);
</script>

<template>
	<div class="grid grid-cols-5 items-center">
		<div class="col-span-5 flex items-center space-x-2">
			<ItemTitle :title="`${$t('trans.go_to_page')}:`" :uppercase="false" />
			<BaseSelect
				label=""
				v-bind="$attrs"
				placeholder=""
				:options="linksOptions"
				:is-searchable="false"
				:is-clearable="false"
			/>
		</div>
	</div>
</template>
