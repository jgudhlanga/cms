<script setup lang="ts">
import type { PaginationRootLink, PaginationMeta } from '@/types/data-pagination';
import { IconName } from '@/enums/icons';
import NavigationLink from '@/components/core/table/NavigationLink.vue';

defineProps<{ meta: PaginationRootLink & PaginationMeta }>();

</script>

<template>
	<div class="flex w-full justify-end gap-2">
		<NavigationLink
			:href="meta.first ?? ''"
			:icon-name="IconName.chevron_double_left"
			:disabled="meta.current_page === 1" />
		<NavigationLink
			:href="meta.prev ?? ''"
			:icon-name="IconName.chevron_left"
			:disabled="!meta.prev" />
		<NavigationLink
			:href="meta.next ?? ''"
			:icon-name="IconName.chevron_right"
			:disabled="!meta.next" />
		<NavigationLink
			:href="meta.last ?? ''"
			:icon-name="IconName.chevron_double_right"
			:disabled="meta.current_page === meta.last_page" />
		<div class="flex items-center gap-1 text-sm text-accent-foreground">
			<div>{{ $tChoice('trans.page', 1) }}</div>
			<strong>{{ meta.current_page }}</strong>
			<span>{{ $t('trans.of') }}</span>
			<strong>{{ meta.last_page }}</strong>
			<span>{{ $t('trans.of') }}</span>
			<strong>{{ meta.total }}</strong>
			<span>{{ $tChoice('trans.result', 2) }}</span>
		</div>
	</div>
</template>
