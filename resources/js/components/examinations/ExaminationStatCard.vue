<script setup lang="ts">
import type { Component } from 'vue';

interface Props {
    label: string;
    value: number | string;
    icon: Component;
    iconClass?: string;
    valueClass?: string;
}

withDefaults(defineProps<Props>(), {
    iconClass: 'bg-indigo-50 text-indigo-600',
    valueClass: 'text-indigo-600',
});
</script>

<template>
    <div
        class="flex items-center gap-2.5 rounded-lg border border-border/60 bg-card px-3 py-2.5 shadow-sm"
    >
        <div
            class="flex h-8 w-8 shrink-0 items-center justify-center rounded-md"
            :class="iconClass"
        >
            <component :is="icon" class="h-3.5 w-3.5" />
        </div>
        <div class="min-w-0">
            <div class="truncate text-xs font-medium text-muted-foreground">{{ label }}</div>
            <div class="mt-0.5 text-lg font-semibold leading-none tracking-tight" :class="valueClass">
                {{ value }}
            </div>
        </div>
    </div>
</template>
