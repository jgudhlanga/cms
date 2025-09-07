<script setup lang="ts">
import { TypeVariant } from '@/enums/type-variants';
import { cn } from '@/lib/utils';
import { computed } from 'vue';

const props = withDefaults(defineProps<{ description: string; type?: TypeVariant; title?: string }>(), {
    type: TypeVariant.warning,
});

const variants: Record<TypeVariant, string> = {
    [TypeVariant.info]: 'text-persian-600',
    [TypeVariant.warning]: 'text-amber-600 ',
    [TypeVariant.danger]: 'text-red-600 dark:border-red-600/50 [&>svg]:text-red-600',
    [TypeVariant.success]: 'text-green-600 dark:border-green-600/50 [&>svg]:text-green-600',
    [TypeVariant.primary]: 'text-persian-600 dark:border-persian-600/50 [&>svg]:text-persian-600',
};

const computedClass = computed(() => cn('p-6', variants[props.type]));
</script>

<template>
    <div :class="computedClass">{{ description }}</div>
</template>
