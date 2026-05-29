<script setup lang="ts">
import BaseIcon from '@/components/core/icon/BaseIcon.vue';
import { IconName } from '@/lib/icons';

defineProps<{
    icon: IconName;
    title: string;
    description: string;
}>();
</script>

<template>
    <div class="flex min-h-[min(320px,50vh)] items-center justify-center px-4 py-10 sm:py-14">
        <div
            class="flex w-full max-w-lg flex-col items-center rounded-2xl border border-dashed border-border/70 bg-muted/25 px-6 py-10 text-center sm:px-10"
        >
            <div class="mb-5 rounded-2xl bg-primary/10 p-4 text-primary">
                <BaseIcon :name="icon" class="size-8" />
            </div>

            <span
                class="mb-3 inline-flex items-center rounded-full border border-border/60 bg-background px-3 py-1 text-[0.65rem] font-semibold uppercase tracking-[0.12em] text-muted-foreground"
            >
                {{ $t('trans.coming_soon') }}
            </span>

            <h2 class="text-lg font-semibold text-foreground sm:text-xl">
                {{ title }}
            </h2>

            <p class="mt-2 max-w-md text-sm leading-relaxed text-muted-foreground">
                {{ description }}
            </p>
        </div>
    </div>
</template>
