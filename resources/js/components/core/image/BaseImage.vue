<script setup lang="ts">
import { useDefaults } from '@/composables/core/useDefaults';
import { twMerge } from 'tailwind-merge';
import { computed } from 'vue';

interface Props {
    isPerson?: boolean;
    src?: string;
    classes?: string;
}

const props = defineProps<Props>();
const { defaultAvatarImage, defaultObjectImage } = useDefaults();

const placeHolderImage = computed(() => {
    return props.isPerson ? defaultAvatarImage.value : defaultObjectImage.value;
});
</script>
<template>
    <img v-bind="$attrs" :class="twMerge('', classes)" :src="!src ? placeHolderImage : src" alt="image" />
</template>
