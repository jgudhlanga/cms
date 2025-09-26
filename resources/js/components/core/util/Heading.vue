<script setup lang="ts">

interface Props {
	title: string;
	description?: string;
}

defineProps<Props>();
</script>

<template>
	<div class="flex flex-col space-y-0.5">
		<h2 class="text-[10px] md:text-xl uppercase font-semibold tracking-tight">{{ title }}</h2>
		<p v-if="description" class="text-sm text-muted-foreground">
			{{ description }}
		</p>
	</div>
</template>
