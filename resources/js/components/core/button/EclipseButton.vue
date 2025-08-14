<script lang="ts" setup>
import {
	DropdownMenu,
	DropdownMenuContent,
	DropdownMenuGroup,
	DropdownMenuItem,
	DropdownMenuTrigger
} from '@/components/ui/dropdown-menu';
import MoreButton from './MoreButton.vue';
import TransText from '../util/TransText.vue';
import { IconName, icons } from '@/lib/icons';
import BaseButton from './BaseButton.vue';
import { ColorVariant } from '@/enums/colors';
import { ButtonSize } from '@/enums/buttons';

 withDefaults(defineProps<{
	groupTitle?: string;
	icon?: IconName;
    showOnlyIcon?: boolean;
    showGroupIcon?: boolean;
	options: Array<any>;
}>(), {

});

</script>

<template>
	<DropdownMenu>
		<DropdownMenuTrigger as-child>
            <MoreButton :icon="icon" v-if="showOnlyIcon" />
            <BaseButton v-else="groupTitle" :title="groupTitle" :variant="ColorVariant.primary_outline" :size="ButtonSize.xs" classes="rounded-full">
                <MoreButton :icon="icon" v-if="showGroupIcon" />
            </BaseButton>
		</DropdownMenuTrigger>
		<DropdownMenuContent>
			<DropdownMenuGroup>
				<DropdownMenuItem v-for="option in options" :key="option.key">
					<button class="flex w-full items-center space-x-2" @click="() => option.action()">
						<component :is="icons[option.icon as IconName]" size="12" />
						<TransText :item="option" :key-index="1" />
					</button>
				</DropdownMenuItem>
			</DropdownMenuGroup>
		</DropdownMenuContent>
	</DropdownMenu>
</template>
