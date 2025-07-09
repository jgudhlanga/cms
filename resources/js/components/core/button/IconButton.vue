<script setup lang="ts">
import BaseButton from '@/components/core/button/BaseButton.vue';
import BaseIcon from '@/components/core/icon/BaseIcon.vue';
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
    <BaseButton type="button" :variant="variant" class="size-8 rounded-full p-3">
        <BaseIcon :name="icon" :color="ColorVariant.white" />
    </BaseButton>
</template>
