<script setup lang="ts">

import { FlexRender, type Table } from '@tanstack/vue-table';
import Empty from '@/components/core/util/Empty.vue';

defineProps<{ table: Table<any> }>();
</script>

<template>
	<tbody class="hava-tbody">
	<tr
		v-for="row in table.getRowModel().rows" :key="row.id"
		:class="`${row.original.attributes.deletedAt ? 'hava-tr hava-tr-highlight-archived' : 'hava-tr'}`"
	>
		<td
			v-for="cell in row.getVisibleCells()"
			:key="cell.id"
			:align="(cell.column.columnDef.meta)?.align ?? 'left'"
			class="hava-td"
		>
			<FlexRender
				:render="cell.column.columnDef.cell"
				:props="cell.getContext()"
			/>
		</td>
	</tr>
	<tr v-if="table.getRowModel().rows.length == 0">
		<td :colspan="table.getFlatHeaders().length" class="whitespace-nowrap">
			<div class="flex flex-col w-full items-center italic  px-3 pt-6">
				<Empty />
			</div>
		</td>
	</tr>
	</tbody>
</template>
