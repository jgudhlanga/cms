<script setup lang="ts">
import AppearanceTabs from '@/components/core/util/AppearanceTabs.vue';
</script>

<template>
    <div class="relative min-h-svh bg-background text-foreground">
        <div class="pointer-events-none fixed right-4 top-4 z-50 md:right-6 md:top-6">
            <div class="pointer-events-auto rounded-lg bg-card/95 p-1 shadow-md ring-1 ring-border backdrop-blur-sm dark:shadow-sm">
                <AppearanceTabs />
            </div>
        </div>
        <slot />
    </div>
</template>
