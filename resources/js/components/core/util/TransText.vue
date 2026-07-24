<script setup lang="ts">
import { useUtils } from '@/composables/core/useUtils';
import { computed } from 'vue';

const props = withDefaults(defineProps<{
	item: any;
	variant?: 'default' | 'nav';
}>(), {
	variant: 'default',
});

const { getTransFile } = useUtils();

const styles = computed(() =>
	props.variant === 'nav'
		? 'text-sm font-medium'
		: 'uppercase text-xs font-normal',
);

const resolvedKey = computed(() => getTransFile(props.item ?? {}));
</script>

<template>
	<span :class="styles" v-if="item?.transChoiceKey != null">{{ $tChoice(resolvedKey, item?.transChoiceKeyIndex ?? 2) }}</span>
	<span :class="styles" v-else-if="item?.transKey != null">{{ $t(resolvedKey) }}</span>
	<span :class="styles" v-else>{{ item?.title }}</span>
</template>
