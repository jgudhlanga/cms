<script setup lang="ts">
import TransText from '@/components/core/util/TransText.vue';
import { Collapsible, CollapsibleContent, CollapsibleTrigger } from '@/components/ui/collapsible';
import {
	HoverCard,
	HoverCardContent,
	HoverCardTrigger,
} from '@/components/ui/hover-card';
import {
	SidebarMenuButton,
	SidebarMenuItem,
	SidebarMenuSub,
	SidebarMenuSubButton,
	SidebarMenuSubItem,
	useSidebar,
} from '@/components/ui/sidebar';
import { useCloseMobileSidebar } from '@/composables/core/useCloseMobileSidebar';
import { useSidebarAccordion } from '@/composables/core/useSidebarAccordion';
import { getMenuItemKey } from '@/composables/core/useSidebarMenu';
import { useSidebarNavActive } from '@/composables/core/useSidebarNavActive';
import { IconName } from '@/enums/icons';
import { icons } from '@/lib/icons';
import type { MenuItemInterface } from '@/types/ui';
import { Link } from '@inertiajs/vue3';
import { computed, watch } from 'vue';
import MenuIcon from './MenuIcon.vue';

const props = defineProps<{
	item: MenuItemInterface;
	translation: string;
}>();

const { isMobile, state } = useSidebar();
const { isActive, isAnyActive } = useSidebarNavActive();
const closeMobileSidebar = useCloseMobileSidebar();

const itemKey = computed(() => getMenuItemKey(props.item));
const { isOpen, setOpen } = useSidebarAccordion(itemKey);

const visibleChildren = computed(() => (props.item.items ?? []).filter((child) => child.show !== false));

const childUrls = computed(() => visibleChildren.value.map((child) => child.url));

const sectionActive = computed(
	() => isActive(props.item.url) || isAnyActive(childUrls.value),
);

const overviewActive = computed(
	() => Boolean(props.item.url) && isActive(props.item.url) && !isAnyActive(childUrls.value),
);

watch(
	sectionActive,
	(active) => {
		if (active) {
			setOpen(true);
		}
	},
	{ immediate: true },
);

const isIconCollapsed = computed(() => state.value === 'collapsed' && !isMobile.value);

function onChildNavigate() {
	closeMobileSidebar();
}
</script>

<template>
	<!-- Collapsed icon rail: HoverCard avoids gap flicker from portaled menus -->
	<SidebarMenuItem
		v-if="isIconCollapsed"
		class="group-data-[collapsible=icon]:flex group-data-[collapsible=icon]:justify-center"
	>
		<HoverCard :open-delay="100" :close-delay="280">
			<HoverCardTrigger as-child>
				<SidebarMenuButton
					:is-active="sectionActive"
					class="data-[state=open]:bg-sidebar-accent/10 data-[state=open]:text-sidebar-foreground"
				>
					<MenuIcon :icon="item.icon" />
					<span class="sr-only">{{ translation }}</span>
				</SidebarMenuButton>
			</HoverCardTrigger>
			<HoverCardContent
				side="right"
				align="start"
				:side-offset="6"
				class="w-auto min-w-52 border-sidebar-border bg-sidebar p-1 text-sidebar-foreground shadow-md data-[state=closed]:fade-out-0 data-[state=open]:fade-in-0 data-[state=closed]:zoom-out-100 data-[state=open]:zoom-in-100"
			>
				<div class="px-2 py-1.5 text-xs font-medium text-muted-foreground uppercase tracking-wide">
					{{ translation }}
				</div>
				<Link
					v-if="item.url"
					:href="item.url"
					class="flex cursor-pointer select-none items-center rounded-sm px-2 py-1.5 text-sm outline-hidden transition-colors hover:bg-sidebar-accent/10 focus:bg-sidebar-accent/10"
					:class="overviewActive ? 'bg-sidebar-primary/12 font-medium text-sidebar-foreground shadow-[inset_-1px_0_0_0_hsl(var(--sidebar-primary))]' : ''"
					@click="onChildNavigate"
				>
					{{ $t('trans.overview') }}
				</Link>
				<div
					v-if="item.url && visibleChildren.length"
					class="my-1 h-px bg-sidebar-border"
				/>
				<Link
					v-for="subItem in visibleChildren"
					:key="getMenuItemKey(subItem)"
					:href="subItem.url ?? ''"
					class="flex cursor-pointer select-none items-center rounded-sm px-2 py-1.5 text-sm outline-hidden transition-colors hover:bg-sidebar-accent/10 focus:bg-sidebar-accent/10"
					:class="isActive(subItem.url) ? 'bg-sidebar-primary/12 font-medium text-sidebar-foreground shadow-[inset_-1px_0_0_0_hsl(var(--sidebar-primary))]' : ''"
					@click="onChildNavigate"
				>
					<TransText :item="subItem" variant="nav" />
				</Link>
			</HoverCardContent>
		</HoverCard>
	</SidebarMenuItem>

	<!-- Expanded: whole row toggles submenu (single open accordion) -->
	<Collapsible
		v-else
		:open="isOpen"
		as-child
		class="group/collapsible"
		@update:open="setOpen"
	>
		<SidebarMenuItem>
			<CollapsibleTrigger as-child>
				<SidebarMenuButton :is-active="sectionActive">
					<MenuIcon :icon="item.icon" />
					<TransText :item="item" variant="nav" />
					<component
						:is="icons[IconName.chevron_right]"
						class="ml-auto size-4 shrink-0 transition-transform duration-200"
						:class="isOpen ? 'rotate-90' : ''"
					/>
				</SidebarMenuButton>
			</CollapsibleTrigger>
			<CollapsibleContent>
				<SidebarMenuSub class="mt-0.5 mb-1">
					<SidebarMenuSubItem v-if="item.url">
						<SidebarMenuSubButton as-child :is-active="overviewActive">
							<Link :href="item.url" @click="onChildNavigate">
								{{ $t('trans.overview') }}
							</Link>
						</SidebarMenuSubButton>
					</SidebarMenuSubItem>
					<SidebarMenuSubItem
						v-for="subItem in visibleChildren"
						:key="getMenuItemKey(subItem)"
					>
						<SidebarMenuSubButton as-child :is-active="isActive(subItem.url)">
							<Link :href="subItem.url ?? ''" @click="onChildNavigate">
								<TransText :item="subItem" variant="nav" />
							</Link>
						</SidebarMenuSubButton>
					</SidebarMenuSubItem>
				</SidebarMenuSub>
			</CollapsibleContent>
		</SidebarMenuItem>
	</Collapsible>
</template>
