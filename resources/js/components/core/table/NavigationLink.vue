<script setup lang="ts">
import { IconName, icons } from '@/lib/icons';
import { cn } from '@/lib/utils';
import { Link } from '@inertiajs/vue3';

const props = defineProps<{
	iconName: IconName;
	href: string;
	disabled: boolean;
	useApi?: boolean;
	apiFetchAction?: (url: string) => void | Promise<void>;
}>();

const linkStyle = "flex items-center bg-accent rounded-full border-[1px] border-border p-2 hover:bg-secondary hover:border-secondary";
const iconStyle = "text-accent-foreground h-5 w-5";

const onApiClick = () => {
	if (props.disabled || !props.href || !props.apiFetchAction) {
		return;
	}
	void props.apiFetchAction(props.href);
};
</script>

<template>
	<button
		v-if="!disabled && useApi && apiFetchAction && href"
		type="button"
		:class="linkStyle"
		@click.prevent="onApiClick">
		<component :is="icons[iconName]" :class="iconStyle" />
	</button>
	<Link v-else-if="!disabled" :href="href" :class="linkStyle">
		<component :is="icons[iconName]" :class="iconStyle" />
	</Link>
	<span v-else :class="cn(linkStyle, 'cursor-not-allowed')">
		<component :is="icons[iconName]" :class="iconStyle" />
	</span>
</template>
