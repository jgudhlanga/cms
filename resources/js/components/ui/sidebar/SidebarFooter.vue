<script setup lang="ts">
import { cn } from '@/lib/utils';
import type { HTMLAttributes } from 'vue';

const props = defineProps<{
	class?: HTMLAttributes['class'];
}>();
</script>

<template>
	<div data-sidebar="footer" :class="cn('flex flex-col gap-1 px-2 py-1.5', props.class)">
		<slot />
	</div>
</template>
