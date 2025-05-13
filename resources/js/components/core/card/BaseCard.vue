<script setup lang='ts'>
import HeadingSmall from '../util/HeadingSmall.vue';


interface Props {
	title?: string;
}

defineProps<Props>()
</script>

<template>
  <div class="flex flex-col w-full p-4 rounded-md shadow-sm space-y-3">
		<HeadingSmall class="font-bold" v-if="title" :title="title"/>
		<slot/>
	</div>
</template>
