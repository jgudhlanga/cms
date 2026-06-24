<script setup lang="ts">
import AppearanceCycleToggle from '@/components/core/util/AppearanceCycleToggle.vue';
import { cn } from '@/lib/utils';

withDefaults(
    defineProps<{
        transparentBackground?: boolean;
        showAppearanceToggle?: boolean;
    }>(),
    {
        showAppearanceToggle: true,
    },
);
</script>

<template>
    <div :class="cn('relative min-h-svh text-foreground', transparentBackground ? 'bg-transparent' : 'bg-background')">
        <div v-if="showAppearanceToggle" class="pointer-events-none fixed right-4 top-4 z-50 md:right-6 md:top-6">
            <div class="pointer-events-auto">
                <AppearanceCycleToggle />
            </div>
        </div>
        <slot />
    </div>
</template>
