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
    <BaseButton type="button" :variant="variant" class="size-6 rounded-full p-1">
        <BaseIcon :name="icon" :color="ColorVariant.white" />
    </BaseButton>
</template>
