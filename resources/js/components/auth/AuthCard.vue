<script setup lang="ts">
import AppLogo from '@/components/core/image/AppLogo.vue';
import AppearanceCycleToggle from '@/components/core/util/AppearanceCycleToggle.vue';

defineProps<{
    title: string;
    subtitle?: string;
}>();
</script>

<template>
    <div
        class="relative flex w-full flex-col gap-6 rounded-3xl border border-white/40 bg-white/60 p-8 text-card-foreground shadow-xl backdrop-blur-xl dark:border-border/50 dark:bg-card/70"
    >
        <div class="absolute top-4 right-4">
            <AppearanceCycleToggle />
        </div>

        <div class="flex flex-col items-center gap-3 text-center">
            <AppLogo classes="size-14 object-contain" />
            <div class="space-y-1">
                <h1 class="text-xl font-semibold tracking-tight text-foreground">
                    {{ title }}
                </h1>
                <p v-if="subtitle" class="text-sm text-muted-foreground">
                    {{ subtitle }}
                </p>
            </div>
        </div>

        <slot />
    </div>
</template>
