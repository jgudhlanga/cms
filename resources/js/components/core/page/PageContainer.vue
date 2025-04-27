<script setup lang="ts">
import { IconButton } from '@/components/core/button';
import Breadcrumbs from '@/components/core/util/Breadcrumbs.vue';
import { Separator } from '@/components/ui/separator';
import { SidebarTrigger } from '@/components/ui/sidebar';
import { useUtils } from '@/composables/core/useUtils';
import { ColorVariant } from '@/enums/colors';
import { IconName } from '@/lib/icons';
import { BreadcrumbItemInterface } from '@/types/ui';

defineProps<{
	breadcrumbs?: BreadcrumbItemInterface[];
}>();

const { goBack } = useUtils();
</script>
<template>
	<header
		class="flex h-16 shrink-0 items-center justify-between gap-2 transition-[width,height] ease-linear group-has-data-[collapsible=icon]/sidebar-wrapper:h-12"
	>
		<div class="flex items-center gap-2 px-4">
			<SidebarTrigger class="-ml-4" />
			<Separator orientation="vertical" class="mr-2 h-4" />
			<Breadcrumbs :breadcrumbs="breadcrumbs ?? []" />
		</div>
		<IconButton :icon="IconName.back" :variant="ColorVariant.shade_outline" class="px-4" @click="goBack" />
	</header>
	<slot />
</template>
