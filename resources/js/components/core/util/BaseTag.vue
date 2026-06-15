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
    variant: ColorVariant.primary,
});

const extendedVariants: Record<ColorVariant | any, string> = {
    [ColorVariant.danger]: 'bg-red-100 text-red-600 hover:bg-red-600 hover:text-red-100',
    [ColorVariant.danger_outline]: 'bg-transparent border-[1px] border-red-500 text-red-500 hover:bg-red-300 hover:border-red-300',
    [ColorVariant.fuchsia]: 'bg-purple-200 text-purple-600 hover:bg-purple-600 hover:text-purple-200',
    [ColorVariant.fuchsia_outline]: 'bg-transparent border-[1px] border-purple-500 text-purple-500 hover:bg-purple-300 hover:border-purple-300',
    [ColorVariant.info]: 'bg-blue-200 text-blue-600 hover:bg-blue-600 hover:text-blue-200',
    [ColorVariant.info_outline]: 'bg-transparent border-[1px] border-blue-500 text-blue-500 hover:bg-blue-300 hover:border-blue-300',
    [ColorVariant.primary]: 'bg-primary/20 text-primary hover:bg-primary hover:text-primary-foreground',
    [ColorVariant.primary_outline]: 'border border-primary bg-transparent text-primary hover:border-primary hover:bg-primary/15 hover:text-primary',
    [ColorVariant.shade]: 'bg-accent text-accent-foreground hover:bg-secondary hover:text-accent-foreground',
    [ColorVariant.shade_outline]: 'bg-transparent border-[1px] border-accent text-accent-foreground hover:bg-secondary hover:border-secondary',
    [ColorVariant.success]: 'bg-green-200 text-green-600 hover:bg-green-600 hover:text-green-200',
    [ColorVariant.success_outline]: 'bg-transparent border-[1px] border-green-500 text-green-500 hover:bg-green-300 hover:border-green-300',
    [ColorVariant.warning]: 'bg-amber-100 text-amber-600 hover:bg-amber-600 hover:text-amber-100',
    [ColorVariant.warning_outline]: 'bg-transparent border-[1px] border-amber-500 text-amber-500 hover:bg-amber-300 hover:border-amber-300',
    [ColorVariant.transparent]: 'bg-transparent',
};

const computedClass = computed(() => cn(extendedVariants[props.variant as ColorVariant] || ''));
</script>

<template>
    <span :class="cn('w-auto cursor-pointer rounded-full px-2 py-0.5', computedClass, classes)">
        {{ title }}
    </span>
</template>
