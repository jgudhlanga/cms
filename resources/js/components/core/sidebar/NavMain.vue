<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { Collapsible, CollapsibleContent, CollapsibleTrigger } from '@/components/ui/collapsible';
import {
	SidebarGroup,
	SidebarGroupContent,
	SidebarGroupLabel,
	SidebarMenu,
	SidebarMenuButton,
	SidebarMenuItem,
	SidebarMenuSub,
	SidebarMenuSubButton,
	SidebarMenuSubItem,
	SidebarSeparator,
} from '@/components/ui/sidebar';
import { useCloseMobileSidebar } from '@/composables/core/useCloseMobileSidebar';
import { getMenuItemKey, useSidebarMenu } from '@/composables/core/useSidebarMenu';
import { useSidebarNavActive } from '@/composables/core/useSidebarNavActive';
import { icons } from '@/lib/icons';
import { IconName } from '@/enums/icons';
import TransText from '@/components/core/util/TransText.vue';
import MenuIcon from './MenuIcon.vue';

const { menuGroups, getTranslation, getGroupLabel } = useSidebarMenu();
const { isActive, isAnyActive } = useSidebarNavActive();
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
						<Collapsible v-if="item.items && item.show" as-child :default-open="item.isActive" class="group/collapsible">
							<SidebarMenuItem class="group-data-[collapsible=icon]:flex group-data-[collapsible=icon]:justify-center">
								<CollapsibleTrigger as-child>
									<SidebarMenuButton
										:is-active="isAnyActive(item.items?.map((sub) => sub.url))"
										:tooltip="getTranslation(item)"
									>
										<MenuIcon :icon="item.icon" />
										<TransText :item="item" variant="nav" />
										<component :is="icons[IconName.chevron_right]"
										           class="ml-auto transition-transform duration-200 group-data-[state=open]/collapsible:rotate-90" />
									</SidebarMenuButton>
								</CollapsibleTrigger>
								<CollapsibleContent>
									<SidebarMenuSub>
										<SidebarMenuSubItem v-for="subItem in item.items" :key="getMenuItemKey(subItem)">
											<SidebarMenuSubButton as-child :is-active="isActive(subItem.url)">
												<Link :href="subItem.url ?? ''" @click="closeMobileSidebar">
													<TransText :item="subItem" variant="nav" />
												</Link>
											</SidebarMenuSubButton>
										</SidebarMenuSubItem>
									</SidebarMenuSub>
								</CollapsibleContent>
							</SidebarMenuItem>
						</Collapsible>
						<SidebarMenuItem v-if="item.show && !item.items" class="group-data-[collapsible=icon]:flex group-data-[collapsible=icon]:justify-center">
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
