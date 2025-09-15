<script setup lang="ts">
import { TypeVariant } from '@/enums/type-variants';
import { IconName, icons } from '@/lib/icons';
import { cn } from '@/lib/utils';
import { computed } from 'vue';

const props = withDefaults(defineProps<{ description: string; type?: TypeVariant; title?: string }>(), {
    type: TypeVariant.warning,
});

const variants: Record<TypeVariant, string> = {
    [TypeVariant.info]: 'border-persian-500 text-persian-600',
    [TypeVariant.warning]: 'border-amber-500 text-amber-500',
    [TypeVariant.danger]: 'border-red-600 text-red-600',
    [TypeVariant.success]: 'border-green-600 text-green-600',
    [TypeVariant.primary]: 'border-persian-600 text-persian-600',
};

const iconVariants: Record<TypeVariant, string> = {
    [TypeVariant.info]: IconName.info,
    [TypeVariant.warning]: IconName.warning,
    [TypeVariant.danger]: IconName.danger,
    [TypeVariant.success]: IconName.check_box,
    [TypeVariant.primary]: IconName.check_box,
};

const computedClass = computed(() =>
    cn('flex w-full space-x-3 rounded-md border-l-4 p-3 shadow-sm bg-gray-50', variants[props.type]),
);
</script>

<template>
    <div :class="computedClass">
        <component :is="icons[iconVariants[type] as IconName]" class="size-6 shrink-0" />
        <div class="font-bold text-sm">{{ description }}</div>
    </div>
</template>
