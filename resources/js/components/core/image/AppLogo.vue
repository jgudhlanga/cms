<script setup lang="ts">
import BaseImage from '@/components/core/image/BaseImage.vue';

import { useDefaults } from '@/composables/core/useDefaults';
import { twMerge } from 'tailwind-merge';
interface Props {
	classes?: string;
}

defineProps<Props>();
const { appLogo } = useDefaults();
</script>

<template>
	<BaseImage :src="appLogo" :classes="twMerge('rounded-3xl overflow-hidden object-contain', classes)" />
</template>
