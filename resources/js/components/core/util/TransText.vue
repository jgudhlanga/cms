<script setup lang="ts">
withDefaults(defineProps<{
	item: any;
	keyIndex?: number,
}>(), {
	keyIndex: 2
});
const styles = 'uppercase text-xs font-normal';
</script>

<template>
	<span :class="styles" v-if="item?.transChoiceKey != null">{{ $tChoice(item?.transChoiceKey, keyIndex ?? 1) }}</span>
	<span :class="styles" v-else-if="item?.transKey != null">{{ $t(item?.transKey) }}</span>
	<span :class="styles" v-else>{{ item?.title }}</span>
</template>
