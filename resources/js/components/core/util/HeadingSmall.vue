<script setup lang="ts">
interface Props {
	title: string;
	description?: string;
}

defineProps<Props>();
</script>

<template>
	<div class="flex min-w-0 flex-col">
		<div class="mb-0.5 wrap-break-word text-xs font-bold uppercase text-accent-foreground">{{ title }}</div>
		<div v-if="description" class="hidden md:flex text-xs text-muted-foreground">
			{{ description }}
		</div>
	</div>
</template>
