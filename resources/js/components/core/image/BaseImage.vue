<script setup lang="ts">
import { computed } from 'vue';
import { useDefaults } from '@/composables/core/useDefaults';
import { twMerge } from 'tailwind-merge';

const props = defineProps({
	isPerson: { type: Boolean, required: false, default: false },
	src: { type: String, required: false },
	classes: { type: String, required: false }
});
const { defaultAvatarImage, defaultObjectImage } = useDefaults();

const placeHolderImage = computed(() => {
	return props.isPerson ? defaultAvatarImage.value : defaultObjectImage.value;
});

</script>
<template>
	<img
		:class="twMerge('', classes)"
		:src="src !== '' ? src : placeHolderImage"
		alt="image"
	/>
</template>
