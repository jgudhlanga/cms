<script setup lang="ts">
import type { Component } from 'vue';

interface Props {
    icon: Component;
    title: string;
    description?: string;
}

defineProps<Props>();
</script>

<template>
    <div class="flex flex-col items-center justify-center rounded-xl border border-dashed border-border px-4 py-10 text-center">
        <span class="flex size-10 items-center justify-center rounded-lg bg-muted text-muted-foreground">
            <component :is="icon" class="size-5" />
        </span>
        <p class="mt-3 text-sm font-semibold text-foreground">
            {{ title }}
        </p>
        <p
            v-if="description"
            class="mt-1 max-w-xs wrap-break-word text-sm text-muted-foreground"
        >
            {{ description }}
        </p>
    </div>
</template>
