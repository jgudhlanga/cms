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
    iconClass: 'text-indigo-500',
    valueClass: 'text-indigo-700',
});
</script>

<template>
    <div class="flex items-center gap-2 rounded-2xl border border-border bg-card px-4 py-2 shadow-sm">
        <component :is="icon" class="h-4 w-4" :class="iconClass" />
        <span class="text-sm font-medium text-foreground">{{ label }}</span>
        <span class="font-bold" :class="valueClass">{{ value }}</span>
    </div>
</template>
