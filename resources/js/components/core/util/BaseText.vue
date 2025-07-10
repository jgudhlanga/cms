<script setup lang="ts">
import { ColorVariant } from '@/enums/colors';
import { cn } from '@/lib/utils';
import { computed } from 'vue';

interface Props {
    title: string;
    classes?: string;
    variant?: ColorVariant;
}

const props = withDefaults(defineProps<Props>(), {
    variant: ColorVariant.fuchsia,
});

const extendedVariants: Record<ColorVariant | any, string> = {
    [ColorVariant.danger]: 'text-red-500 hover:text-red-600',
    [ColorVariant.fuchsia]: 'tex-purple-500 hover:text-purple-600',
    [ColorVariant.info]: 'text-blue-500 hover:text-blue-600',
    [ColorVariant.primary]: 'text-persian-600 hover:text-persian-700',
    [ColorVariant.shade]: 'text-accent-foreground hover:text-accent-foreground',
    [ColorVariant.success]: 'text-green-500 hover:text-green-600',
    [ColorVariant.warning]: 'text-amber-500 hover:text-amber-600',
};

const computedClass = computed(() => cn(extendedVariants[props.variant as ColorVariant] || ''));
</script>

<template>
    <span :class="cn('w-auto cursor-pointer rounded-full uppercase', computedClass, classes)">
        {{ title }}
    </span>
</template>
