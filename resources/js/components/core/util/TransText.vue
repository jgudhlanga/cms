<script setup lang="ts">
import { computed } from 'vue';

const props = withDefaults(defineProps<{
	item: any;
	variant?: 'default' | 'nav';
}>(), {
	variant: 'default',
});

const styles = computed(() =>
	props.variant === 'nav'
		? 'text-sm font-medium'
		: 'uppercase text-xs font-normal',
);
</script>

<template>
	<span :class="styles" v-if="item?.transChoiceKey != null">{{ $tChoice(item?.transChoiceKey, item?.transChoiceKeyIndex ?? 2) }}</span>
	<span :class="styles" v-else-if="item?.transKey != null">{{ $t(item?.transKey) }}</span>
	<span :class="styles" v-else>{{ item?.title }}</span>
</template>
