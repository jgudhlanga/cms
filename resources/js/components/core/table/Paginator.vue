<script setup lang="ts">
import NavigationLink from '@/components/core/table/NavigationLink.vue';
import { IconName } from '@/enums/icons';
import type { PaginationMeta, PaginationRootLink } from '@/types/data-pagination';

withDefaults(
	defineProps<{
		meta: PaginationRootLink & PaginationMeta;
		useApi?: boolean;
		apiFetchAction?: (url: string) => void | Promise<void>;
	}>(),
	{ useApi: false },
);

</script>

<template>
	<div class="flex w-full items-center justify-end gap-2">
		<NavigationLink
			:href="meta.first ?? ''"
			:icon-name="IconName.chevron_double_left"
			:disabled="meta.current_page === 1"
			:use-api="useApi"
			:api-fetch-action="apiFetchAction" />
		<NavigationLink
			:href="meta.prev ?? ''"
			:icon-name="IconName.chevron_left"
			:disabled="!meta.prev"
			:use-api="useApi"
			:api-fetch-action="apiFetchAction" />
		<NavigationLink
			:href="meta.next ?? ''"
			:icon-name="IconName.chevron_right"
			:disabled="!meta.next"
			:use-api="useApi"
			:api-fetch-action="apiFetchAction" />
		<NavigationLink
			:href="meta.last ?? ''"
			:icon-name="IconName.chevron_double_right"
			:disabled="meta.current_page === meta.last_page"
			:use-api="useApi"
			:api-fetch-action="apiFetchAction" />
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
