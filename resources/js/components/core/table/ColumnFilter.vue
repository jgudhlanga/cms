<script setup lang="ts">
import type { Table } from '@tanstack/vue-table';
import { ColorVariant } from '@/enums/colors';
import { IconName, icons } from '@/lib/icons';
import ColumnName from '@/components/core/table/ColumnName.vue';
import BaseButton from '@/components/core/button/BaseButton.vue';
import {
	DropdownMenu,
	DropdownMenuContent,
	DropdownMenuGroup,
	DropdownMenuItem,
	DropdownMenuTrigger
} from '@/components/ui/dropdown-menu';

defineProps<{ table: Table<any>, toggleColumnVisibility: Function }>();

</script>

<template>
	<DropdownMenu>
		<DropdownMenuTrigger as-child>
			<BaseButton :variant="ColorVariant.shade_outline" class="rounded-full">
				<component :is="icons[IconName.filter]" />
				{{ $tChoice('trans.column', 2) }}
			</BaseButton>
		</DropdownMenuTrigger>
		<DropdownMenuContent>
			<DropdownMenuGroup>
				<DropdownMenuItem v-for="column in table.getAllLeafColumns()" :key="column.id">
					<button class="flex w-full items-center" @click="toggleColumnVisibility(column)">
						<ColumnName
							:title="column?.columnDef?.header ?? ''"
							:iconName="column.getIsVisible() ?  IconName.check : IconName.close"
							:color="ColorVariant.shade"
						/>
					</button>
				</DropdownMenuItem>
			</DropdownMenuGroup>
		</DropdownMenuContent>
	</DropdownMenu>
</template>

