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
    <section class="border-border bg-card overflow-hidden rounded-xl border">
        <header class="border-border bg-muted/30 flex flex-wrap items-center justify-between gap-x-3 gap-y-1 border-b px-3 py-2 sm:px-4">
            <div class="flex min-w-0 flex-1 items-baseline gap-2">
                <span class="bg-primary/10 text-primary flex h-6 w-6 shrink-0 translate-y-0.5 items-center justify-center rounded-md">
                    <BaseIcon :name="icon" class="h-3.5 w-3.5" />
                </span>
                <h2 class="text-foreground shrink-0 text-sm font-bold tracking-tight">{{ title }}</h2>
                <p v-if="description" class="text-muted-foreground hidden min-w-0 truncate text-xs md:block">{{ description }}</p>
            </div>
            <Badge v-if="count !== undefined" variant="secondary" class="h-5 shrink-0 px-2 text-[11px] font-normal">
                {{ count }} {{ countLabel }}
            </Badge>
        </header>
        <div class="px-3 py-2.5 sm:px-4">
            <slot />
        </div>
    </section>
</template>
