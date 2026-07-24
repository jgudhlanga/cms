<script setup lang="ts">
import TransText from '@/components/core/util/TransText.vue';
import {
	SidebarGroup,
	SidebarGroupContent,
	SidebarGroupLabel,
	SidebarMenu,
	SidebarMenuButton,
	SidebarMenuItem,
	SidebarSeparator,
} from '@/components/ui/sidebar';
import { useCloseMobileSidebar } from '@/composables/core/useCloseMobileSidebar';
import { provideSidebarAccordion } from '@/composables/core/useSidebarAccordion';
import { getMenuItemKey, useSidebarMenu } from '@/composables/core/useSidebarMenu';
import { useSidebarNavActive } from '@/composables/core/useSidebarNavActive';
import { Link } from '@inertiajs/vue3';
import MenuIcon from './MenuIcon.vue';
import NavMainNestedItem from './NavMainNestedItem.vue';

provideSidebarAccordion();

const { menuGroups, getTranslation, getGroupLabel } = useSidebarMenu();
const { isActive } = useSidebarNavActive();
const closeMobileSidebar = useCloseMobileSidebar();
</script>
<template>
	<template v-for="group in menuGroups" :key="group.key">
		<SidebarSeparator v-if="group.showSeparatorBefore" />
		<SidebarGroup>
			<SidebarGroupLabel class="uppercase">{{ getGroupLabel(group.key) }}</SidebarGroupLabel>
			<SidebarGroupContent>
				<SidebarMenu>
					<template v-for="item in group.items" :key="getMenuItemKey(item)">
						<NavMainNestedItem
							v-if="item.items?.length && item.show"
							:item="item"
							:translation="getTranslation(item)"
						/>
						<SidebarMenuItem
							v-else-if="item.show && !item.items?.length"
							class="group-data-[collapsible=icon]:flex group-data-[collapsible=icon]:justify-center"
						>
							<SidebarMenuButton as-child :is-active="isActive(item.url)" :tooltip="getTranslation(item)">
								<Link :href="item.url ?? ''" @click="closeMobileSidebar">
									<MenuIcon :icon="item.icon" />
									<TransText :item="item" variant="nav" />
								</Link>
							</SidebarMenuButton>
						</SidebarMenuItem>
					</template>
				</SidebarMenu>
			</SidebarGroupContent>
		</SidebarGroup>
	</template>
</template>
