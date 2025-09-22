<script lang="ts" setup>
import { IconName, icons } from '@/lib/icons';

withDefaults(defineProps<{ icon?: IconName }>(), {
	icon: IconName.dots_vertical
});

</script>
<template>
	<button class="rounded-full p-1">
		<component :is="icons[icon]" size="24" stroke-width="1" :class="$attrs.class" />
	</button>
</template>
