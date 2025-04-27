<script setup lang="ts">
import { ModalsContainer } from 'vue-final-modal';
import AppSidebar from '@/components/core/sidebar/AppSidebar.vue';
import {
	SidebarInset,
	SidebarProvider
} from '@/components/ui/sidebar';
import { BreadcrumbItemType } from '@/types/ui';

defineProps<{
	breadcrumbs?: BreadcrumbItemType[];
}>();
</script>
<template>
	<SidebarProvider>
		<AppSidebar />
		<SidebarInset>
			<div class="flex h-full flex-1 flex-col gap-4 rounded-xl px-8">
				<slot />
			</div>
		</SidebarInset>
	</SidebarProvider>
	<ModalsContainer />
</template>
