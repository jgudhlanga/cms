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
    [TypeVariant.primary]: 'border-primary text-primary',
};

const iconVariants: Record<TypeVariant, string> = {
    [TypeVariant.info]: IconName.info,
    [TypeVariant.warning]: IconName.warning,
    [TypeVariant.danger]: IconName.danger,
    [TypeVariant.success]: IconName.check_box,
    [TypeVariant.primary]: IconName.check_box,
};

const computedClass = computed(() => cn('flex w-full flex-col rounded-md border-l-4 bg-gray-50 p-3 shadow-sm', variants[props.type]));
</script>

<template>
    <div :class="computedClass">
        <div class="flex w-full items-start space-x-3">
            <component :is="icons[iconVariants[type] as IconName]" class="size-6 shrink-0" />
            <div class="space-y-1 text-sm">
                <p v-if="title" class="font-semibold">{{ title }}</p>
                <p>{{ description }}</p>
            </div>
        </div>
        <slot />
    </div>
</template>
