<script setup lang="ts">
interface Props {
	title: string;
	description?: string;
}

defineProps<Props>();
</script>

<template>
	<div class="flex flex-col">
		<div class="flex mb-0.5 text-sm uppercase text-accent-foreground font-bold">{{ title }}</div>
		<div v-if="description" class="hidden md:flex text-xs text-muted-foreground">
			{{ description }}
		</div>
	</div>
</template>
