<script setup lang="ts">
import { cn } from '@/lib/utils';
import { AvatarFallback, type AvatarFallbackProps } from 'radix-vue';
import type { HTMLAttributes } from 'vue';

const props = defineProps<AvatarFallbackProps & { class?: HTMLAttributes['class'] }>();
</script>

<template>
	<AvatarFallback
		:as="props.as"
		:as-child="props.asChild"
		:delay-ms="props.delayMs"
		:class="cn('flex size-full items-center justify-center rounded-full', props.class)"
	>
		<slot />
	</AvatarFallback>
</template>
