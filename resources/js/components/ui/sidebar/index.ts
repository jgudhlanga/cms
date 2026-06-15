import { cva, type VariantProps } from 'class-variance-authority';

export { default as Sidebar } from './Sidebar.vue';
export { default as SidebarContent } from './SidebarContent.vue';
export { default as SidebarFooter } from './SidebarFooter.vue';
export { default as SidebarGroup } from './SidebarGroup.vue';
export { default as SidebarGroupAction } from './SidebarGroupAction.vue';
export { default as SidebarGroupContent } from './SidebarGroupContent.vue';
export { default as SidebarGroupLabel } from './SidebarGroupLabel.vue';
export { default as SidebarHeader } from './SidebarHeader.vue';
export { default as SidebarInput } from './SidebarInput.vue';
export { default as SidebarInset } from './SidebarInset.vue';
export { default as SidebarMenu } from './SidebarMenu.vue';
export { default as SidebarMenuAction } from './SidebarMenuAction.vue';
export { default as SidebarMenuBadge } from './SidebarMenuBadge.vue';
export { default as SidebarMenuButton } from './SidebarMenuButton.vue';
export { default as SidebarMenuItem } from './SidebarMenuItem.vue';
export { default as SidebarMenuSkeleton } from './SidebarMenuSkeleton.vue';
export { default as SidebarMenuSub } from './SidebarMenuSub.vue';
export { default as SidebarMenuSubButton } from './SidebarMenuSubButton.vue';
export { default as SidebarMenuSubItem } from './SidebarMenuSubItem.vue';
export { default as SidebarProvider } from './SidebarProvider.vue';
export { default as SidebarRail } from './SidebarRail.vue';
export { default as SidebarSeparator } from './SidebarSeparator.vue';
export { default as SidebarTrigger } from './SidebarTrigger.vue';

export { useSidebar } from './utils';

export const sidebarMenuButtonVariants = cva(
	'peer/menu-button flex w-full items-center gap-2.5 overflow-hidden rounded-md border border-transparent px-3 py-2 text-left text-sm text-sidebar-foreground/90 outline-hidden ring-sidebar-ring transition-colors duration-150 hover:bg-sidebar-accent/10 hover:text-sidebar-foreground focus-visible:ring-2 focus-visible:ring-sidebar-ring/50 active:bg-sidebar-accent/15 disabled:pointer-events-none disabled:opacity-50 group-has-data-[sidebar=menu-action]/menu-item:pr-8 aria-disabled:pointer-events-none aria-disabled:opacity-50 data-[active=true]:bg-sidebar-primary/12 data-[active=true]:font-medium data-[active=true]:text-sidebar-foreground data-[active=true]:shadow-[inset_3px_0_0_0_hsl(var(--sidebar-primary))] data-[active=true]:[&>svg]:text-sidebar-primary data-[state=open]:bg-sidebar-accent/10 data-[state=open]:text-sidebar-foreground group-data-[collapsible=icon]:mx-auto group-data-[collapsible=icon]:size-9! group-data-[collapsible=icon]:justify-center group-data-[collapsible=icon]:gap-0 group-data-[collapsible=icon]:p-2! group-data-[collapsible=icon]:shadow-none group-data-[collapsible=icon]:data-[active=true]:bg-sidebar-primary/15 group-data-[collapsible=icon]:data-[active=true]:ring-1 group-data-[collapsible=icon]:data-[active=true]:ring-sidebar-primary/50 group-data-[collapsible=icon]:[&>*:not(:first-child)]:hidden [&>span:last-child]:truncate [&>svg]:size-4 [&>svg]:shrink-0 [&>svg]:text-sidebar-foreground/75',
	{
		variants: {
			variant: {
				default: 'hover:bg-sidebar-accent/10 hover:text-sidebar-foreground',
				outline:
					'bg-background shadow-[0_0_0_1px_hsl(var(--sidebar-border))] hover:bg-sidebar-accent/10 hover:text-sidebar-foreground hover:shadow-[0_0_0_1px_hsl(var(--sidebar-accent))]'
			},
			size: {
				default: 'min-h-9 text-sm',
				sm: 'min-h-8 text-xs',
				lg: 'min-h-10 text-sm group-data-[collapsible=icon]:p-0!'
			}
		},
		defaultVariants: {
			variant: 'default',
			size: 'default'
		}
	}
);

export type SidebarMenuButtonVariants = VariantProps<typeof sidebarMenuButtonVariants>;
