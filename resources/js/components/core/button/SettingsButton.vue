<script setup lang="ts">
import { BaseButton } from '@/components/core/button/index';
import { ColorVariant } from '@/enums/colors';
import { IconName, icons } from '@/lib/icons';
import { cn } from '@/lib/utils';

withDefaults(
    defineProps<{
        classes?: string;
        variant?: ColorVariant;
        title?: string;
    }>(),
    {
        variant: ColorVariant.primary_outline,
    },
);
</script>

<template>
    <BaseButton :variant="variant" :class="cn('h-8 w-30 rounded-full px-4 py-2', classes)" v-bind="$attrs">
        <component :is="icons[IconName.cogs]" />
        <slot />
        <span v-if="title">{{ title }}</span>
    </BaseButton>
</template>
