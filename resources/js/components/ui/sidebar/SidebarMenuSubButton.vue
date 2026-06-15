<script setup lang="ts">
import { cn } from '@/lib/utils';
import type { PrimitiveProps } from 'radix-vue';
import { Primitive } from 'radix-vue';
import type { HTMLAttributes } from 'vue';

const props = withDefaults(
	defineProps<
		PrimitiveProps & {
		size?: 'sm' | 'md';
		isActive?: boolean;
		class?: HTMLAttributes['class'];
	}
	>(),
	{
		as: 'a',
		size: 'md'
	}
);
</script>

<template>
	<Primitive
		data-sidebar="menu-sub-button"
		:as="as"
		:as-child="asChild"
		:data-size="size"
		:data-active="isActive"
		:class="
            cn(
                'flex min-h-8 min-w-0 -translate-x-px items-center gap-2 overflow-hidden rounded-md border border-transparent px-3 py-1.5 text-sidebar-foreground/90 outline-hidden ring-sidebar-ring transition-colors duration-150 hover:bg-sidebar-accent/10 hover:text-sidebar-foreground focus-visible:ring-2 focus-visible:ring-sidebar-ring/50 active:bg-sidebar-accent/15 disabled:pointer-events-none disabled:opacity-50 aria-disabled:pointer-events-none aria-disabled:opacity-50 [&>span:last-child]:truncate [&>svg]:size-4 [&>svg]:shrink-0 [&>svg]:text-sidebar-foreground/75',
                'data-[active=true]:bg-sidebar-primary/12 data-[active=true]:font-medium data-[active=true]:text-sidebar-foreground data-[active=true]:shadow-[inset_3px_0_0_0_hsl(var(--sidebar-primary))] data-[active=true]:[&>svg]:text-sidebar-primary',
                size === 'sm' && 'text-xs',
                size === 'md' && 'text-sm',
                'group-data-[collapsible=icon]:hidden',
                props.class,
            )
        "
	>
		<slot />
	</Primitive>
</template>
