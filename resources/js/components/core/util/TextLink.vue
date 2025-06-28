<script setup lang="ts">
import { Link } from '@inertiajs/vue3';

interface Props {
	href: string;
	tabindex?: number;
	method?: "get" | "post" | "put" | "patch" | "delete";
	as?: string;
	title?: string;
}

defineProps<Props>();
</script>

<template>
	<Link
		:href="href"
        :method="method"
		:tabindex="tabindex"
		:as="as"
		class="hover:text-accent-foreground cursor-pointer text-primary decoration-persian-200 underline-offset-4 transition-colors duration-300 ease-out"
	>
		<slot />
		<span v-if="title">{{ title }}</span>
	</Link>
</template>
