<script setup lang="ts">
import HeadingSmall from '@/components/core/util/HeadingSmall.vue';
import { IconName } from '@/enums/icons';
import { icons } from '@/lib/icons';

defineProps<{
    icon: IconName;
    title: string;
    description: string;
}>();
</script>

<template>
    <section class="border-border bg-card rounded-xl border">
        <header class="border-border/60 flex items-start gap-3 border-b px-4 py-3">
            <span class="bg-muted text-muted-foreground flex size-8 shrink-0 items-center justify-center rounded-lg">
                <component :is="icons[icon]" class="size-4" aria-hidden="true" />
            </span>
            <HeadingSmall :title="title" :description="description" />
            <div class="ml-auto shrink-0">
                <slot name="meta" />
            </div>
        </header>
        <div class="px-4 py-4">
            <slot />
        </div>
    </section>
</template>
