<script setup lang="ts">
import AppLogo from '@/components/core/image/AppLogo.vue';

type LogoMarkSize = 'sm' | 'md' | 'lg' | 'xl';

withDefaults(
	defineProps<{
		size?: LogoMarkSize;
	}>(),
	{ size: 'md' },
);

const sizeClasses: Record<LogoMarkSize, string> = {
	sm: 'size-10',
	md: 'size-20',
	lg: 'size-24 sm:size-28',
	xl: 'size-25',
};
</script>

<template>
	<div
		class="flex aspect-square shrink-0 items-center justify-center overflow-hidden rounded-full bg-white"
		:class="sizeClasses[size]"
	>
		<AppLogo classes="size-full rounded-full border-2 border-white object-contain object-center" />
	</div>
</template>
