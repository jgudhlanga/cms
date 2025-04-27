<script setup lang="ts">
import { FlexRender, type Table } from '@tanstack/vue-table';

defineProps<{ table: Table<any> }>();
</script>

<template>
	<thead class="hava-thead">
	<tr
		v-for="headerGroup in table.getHeaderGroups()"
		:key="headerGroup.id"
	>
		<th
			v-for="header in headerGroup.headers"
			:key="header.id"
			scope="col"
			:colSpan="header.colSpan"
			class="hava-th"
			:align="(header.column.columnDef.meta)?.align ?? 'left'"
			@click="header.column.getToggleSortingHandler()?.($event)"
			:class="{
                    'cursor-pointer select-none': header.column.getCanSort(),
                  }"
		>
			<template v-if="!header.isPlaceholder">
				<FlexRender
					:render="header.column.columnDef.header"
					:props="header.getContext()"
				/>
				{{ { asc: ' ↑', desc: ' ↓' }[header.column.getIsSorted() as string] }}
			</template>
		</th>
	</tr>
	</thead>
</template>

