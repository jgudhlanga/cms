<script setup lang='ts'>
import HeadingSmall from '../util/HeadingSmall.vue';


interface Props {
	title?: string;
	description?: string;
    colorVariant?: string;
}

withDefaults(defineProps<Props>(), {
    colorVariant: 'primary',
})
</script>

<template>
  <div :class="`flex flex-col w-full p-4 rounded-md shadow-sm space-y-3 border-l border-${colorVariant}`">
		<HeadingSmall v-if="title" :title="title" :description="description"/>
		<slot/>
	</div>
</template>
