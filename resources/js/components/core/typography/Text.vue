<script setup lang="ts">
import { cn } from '@/lib/utils';

defineProps<{ title: string, classes?: string }>();
const defaultClasses = 'flex space-x-2 font-bold text-slate-700';
</script>

<template>
	<div :class="`${cn(defaultClasses, classes)}`">
		<slot name="tag-left" />
		<div>{{ title }}</div>
		<slot name="tag-right" />
	</div>
</template>
