<script setup lang="ts">
import { cn } from '@/lib/utils';
import { computed } from 'vue';

const props = withDefaults(
    defineProps<{
        title?: string;
        description?: string;
        bordered?: boolean;
        columns?: 1 | 2 | 3;
        class?: string;
    }>(),
    {
        bordered: false,
        columns: 2,
    },
);

const gridClass = computed(() => {
    const columnClasses: Record<1 | 2 | 3, string> = {
        1: 'grid-cols-1',
        2: 'grid-cols-1 sm:grid-cols-2',
        3: 'grid-cols-1 sm:grid-cols-2 lg:grid-cols-3',
    };

    return cn('grid gap-3', columnClasses[props.columns]);
});
</script>

<template>
    <section
        :class="
            cn(
                bordered && 'rounded-xl border border-border bg-card/50 p-4',
                $props.class,
            )
        "
    >
        <div v-if="title || description" class="mb-3 space-y-1">
            <h3 v-if="title" class="text-sm font-semibold text-foreground">
                {{ title }}
            </h3>
            <p v-if="description" class="text-sm text-muted-foreground">
                {{ description }}
            </p>
        </div>
        <div :class="gridClass">
            <slot />
        </div>
    </section>
</template>
