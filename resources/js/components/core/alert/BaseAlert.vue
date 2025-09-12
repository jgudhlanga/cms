<script setup lang="ts">
import { TypeVariant } from '@/enums/type-variants';
import { IconName, icons } from '@/lib/icons';
import { cn } from '@/lib/utils';
import { computed } from 'vue';

const props = withDefaults(defineProps<{ description: string; type?: TypeVariant; title?: string }>(), {
    type: TypeVariant.warning,
});

const variants: Record<TypeVariant, string> = {
    [TypeVariant.info]: 'border-persian-600/50 text-persian-600',
    [TypeVariant.warning]: 'border-amber-500/50 text-amber-500',
    [TypeVariant.danger]: 'border-red-600/50 text-red-600',
    [TypeVariant.success]: 'border-green-600/50 text-green-600',
    [TypeVariant.primary]: 'border-persian-600/50 text-persian-600',
};

const iconVariants: Record<TypeVariant, string> = {
    [TypeVariant.info]: IconName.info,
    [TypeVariant.warning]: IconName.warning,
    [TypeVariant.danger]: IconName.danger,
    [TypeVariant.success]: IconName.check_box,
    [TypeVariant.primary]: IconName.check_box,
};

const computedClass = computed(() =>
    cn('flex w-full space-x-3 rounded-2xl border-t-1 border-r-1 border-b-1 border-l-4 p-3 shadow-sm', variants[props.type]),
);
</script>

<template>
    <div :class="computedClass">
        <component :is="icons[iconVariants[type] as IconName]" class="size-6 shrink-0" />
        <div>{{ description }}</div>
    </div>
</template>
