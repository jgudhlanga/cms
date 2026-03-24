<script setup lang="ts">

import { IconName, icons } from '@/lib/icons';
import { cn } from '@/lib/utils';
import { Link } from '@inertiajs/vue3';

defineProps<{ iconName: IconName, href: string, disabled: boolean }>();
const linkStyle = "flex items-center bg-accent rounded-full border-[1px] border-border p-2 hover:bg-secondary hover:border-secondary";
const iconStyle = "text-accent-foreground h-5 w-5";
</script>

<template>
	<Link v-if="!disabled" :href="href" :class="linkStyle">
		<component :is="icons[iconName]" :class="iconStyle" />
	</Link>
	<span v-else :class="cn(linkStyle, 'cursor-not-allowed')">
		<component :is="icons[iconName]" :class="iconStyle" />
	</span>
</template>
