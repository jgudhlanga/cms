<script setup lang="ts">
import BaseButton from '@/components/core/button/BaseButton.vue';
import BaseIcon from '@/components/core/icon/BaseIcon.vue';
import { IconName } from '@/enums/icons';
import { ColorVariant } from '@/enums/colors';

defineProps<{
	icon: IconName,
	iconColor?: ColorVariant,
	iconSize?: string,
	variant?: ColorVariant
}>();
</script>

<template>
	<BaseButton type="button" :variant="variant" class="rounded-full size-8 p-3">
		<BaseIcon :name="icon" :color="ColorVariant.white" />
	</BaseButton>
</template>
