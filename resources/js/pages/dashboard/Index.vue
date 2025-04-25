<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import PlaceholderPattern from '@/components/core/page/PlaceholderPattern.vue';
import PageContainer from '@/components/core/page/PageContainer.vue';
import { BreadcrumbItemInterface } from '@/types/ui';

const breadcrumbs: BreadcrumbItemInterface[] = [
	{ transChoiceKey: 'dashboard' }
];
</script>
<template>
	<Head :title="$tChoice('trans.dashboard', 2)" />
	<PageContainer :breadcrumbs="breadcrumbs">
		<div class="grid auto-rows-min gap-4 md:grid-cols-3">
			<div
				class="relative min-h-[100vh] flex-1 space-y-4 p-2 rounded-xl border border-sidebar-border/70 dark:border-sidebar-border md:min-h-min">

			</div>
			<div
				class="relative aspect-video overflow-hidden rounded-xl border border-sidebar-border/70 dark:border-sidebar-border">
				<PlaceholderPattern />
			</div>
			<div
				class="relative aspect-video overflow-hidden rounded-xl border border-sidebar-border/70 dark:border-sidebar-border">
				<PlaceholderPattern />
			</div>
		</div>
		<div
			class="relative min-h-[100vh] flex-1 rounded-xl border border-sidebar-border/70 dark:border-sidebar-border md:min-h-min">
			<PlaceholderPattern />
		</div>
	</PageContainer>
</template>
