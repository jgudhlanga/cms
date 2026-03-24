<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { Collapsible, CollapsibleContent, CollapsibleTrigger } from '@/components/ui/collapsible';
import {
	SidebarGroup,
	SidebarMenu,
	SidebarMenuButton,
	SidebarMenuItem,
	SidebarMenuSub,
	SidebarMenuSubButton,
	SidebarMenuSubItem
} from '@/components/ui/sidebar';
import { useSidebarMenu } from '@/composables/core/useSidebarMenu';
import { useSidebarNavActive } from '@/composables/core/useSidebarNavActive';
import { icons } from '@/lib/icons';
import { IconName } from '@/enums/icons';
import TransText from '@/components/core/util/TransText.vue';
import MenuIcon from './MenuIcon.vue';

const { menuOptions, getTranslation } = useSidebarMenu();
const { isActive, isAnyActive } = useSidebarNavActive();
</script>
<template>
	<SidebarGroup>
		<SidebarMenu>
			<template v-for="item in menuOptions" :key="item.title">
				<Collapsible v-if="item.items && item.show" as-child :default-open="item.isActive" class="group/collapsible">
					<SidebarMenuItem class="group-data-[collapsible=icon]:flex group-data-[collapsible=icon]:justify-center">
						<CollapsibleTrigger as-child>
							<SidebarMenuButton
								:is-active="isAnyActive(item.items?.map((sub) => sub.url))"
								:tooltip="getTranslation(item)"
							>
								<MenuIcon :icon="item.icon" />
								<TransText :item="item" />
								<component :is="icons[IconName.chevron_right]"
								           class="ml-auto transition-transform duration-200 group-data-[state=open]/collapsible:rotate-90" />
							</SidebarMenuButton>
						</CollapsibleTrigger>
						<CollapsibleContent>
							<SidebarMenuSub>
								<SidebarMenuSubItem v-for="subItem in item.items" :key="subItem.title">
									<SidebarMenuSubButton as-child :is-active="isActive(subItem.url)">
										<Link :href="subItem.url ?? ''">
											<TransText :item="subItem" />
										</Link>
									</SidebarMenuSubButton>
								</SidebarMenuSubItem>
							</SidebarMenuSub>
						</CollapsibleContent>
					</SidebarMenuItem>
				</Collapsible>
				<SidebarMenuItem v-if="item.show && !item.items" class="group-data-[collapsible=icon]:flex group-data-[collapsible=icon]:justify-center">
					<SidebarMenuButton as-child :is-active="isActive(item.url)" :tooltip="getTranslation(item)">
						<Link :href="item.url ?? ''">
							<MenuIcon :icon="item.icon" />
							<TransText :item="item" />
						</Link>
					</SidebarMenuButton>
				</SidebarMenuItem>
			</template>
		</SidebarMenu>
	</SidebarGroup>
</template>
