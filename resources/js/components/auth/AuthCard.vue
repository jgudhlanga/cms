<script setup lang="ts">
import AppLogo from '@/components/core/image/AppLogo.vue';

defineProps<{
    title: string;
    subtitle?: string;
}>();
</script>

<template>
    <div
        class="text-card-foreground relative flex w-full flex-col gap-6 rounded-3xl border border-white/70 bg-slate-50/95 p-6 shadow-2xl shadow-slate-950/25 backdrop-blur-xl sm:p-8 dark:border-slate-700/80 dark:bg-slate-950/95"
    >
        <div class="flex flex-col items-center gap-3 text-center">
            <AppLogo classes="size-14 object-contain" />
            <div class="space-y-1">
                <h1 class="text-foreground text-2xl font-semibold tracking-tight">
                    {{ title }}
                </h1>
                <p v-if="subtitle" class="text-sm leading-relaxed text-slate-600 dark:text-slate-300">
                    {{ subtitle }}
                </p>
            </div>
        </div>

        <slot />
    </div>
</template>
