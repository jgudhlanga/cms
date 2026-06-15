<script setup lang="ts">
import { ColorVariant } from '@/enums/colors';
import { IconName } from '@/enums/icons';

interface Props {
    icon: IconName;
    iconColor?: ColorVariant;
    iconSize?: string;
    variant?: ColorVariant;
}

defineProps<Props>();
</script>

<template>
    <BaseButton type="button" :variant="variant" classes="w-8 h-8 p-0 rounded-full flex items-center justify-center flex-shrink-0">
        <BaseIcon :name="icon" :color="iconColor ?? ColorVariant.white" :size="iconSize" />
    </BaseButton>
</template>
