<script setup lang="ts">
import BaseIcon from '@/components/core/icon/BaseIcon.vue';
import { Badge } from '@/components/ui/badge';
import { IconName } from '@/enums/icons';

interface Props {
    icon: IconName;
    title: string;
    description?: string;
    count?: number;
    countLabel?: string;
}

defineProps<Props>();
</script>

<template>
    <section class="border-border bg-card overflow-hidden rounded-2xl border">
        <header class="border-border bg-muted/30 flex flex-wrap items-center justify-between gap-3 border-b px-4 py-3 sm:px-5">
            <div class="flex min-w-0 items-center gap-2.5">
                <span class="bg-primary/10 text-primary flex h-8 w-8 shrink-0 items-center justify-center rounded-lg">
                    <BaseIcon :name="icon" class="h-4 w-4" />
                </span>
                <div class="min-w-0">
                    <h2 class="text-foreground truncate text-sm font-bold tracking-tight">{{ title }}</h2>
                    <p v-if="description" class="text-muted-foreground mt-0.5 text-xs leading-relaxed">{{ description }}</p>
                </div>
            </div>
            <Badge v-if="count !== undefined" variant="secondary" class="shrink-0 font-normal">
                {{ count }} {{ countLabel }}
            </Badge>
        </header>
        <div class="px-4 py-3 sm:px-5">
            <slot />
        </div>
    </section>
</template>
