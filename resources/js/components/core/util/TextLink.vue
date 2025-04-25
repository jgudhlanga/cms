<script setup lang="ts">
import { Link } from '@inertiajs/vue3';

interface Props {
	href: string;
	tabindex?: number;
	method?: string;
	as?: string;
	title?: string;
}

defineProps<Props>();
</script>

<template>
	<Link
		:href="href"
		:tabindex="tabindex"
		:as="as"
		class="hover:text-accent-foreground text-primary decoration-picton-200 underline-offset-4 transition-colors duration-300 ease-out"
	>
		<slot />
		<span v-if="title">{{ title }}</span>
	</Link>
</template>
