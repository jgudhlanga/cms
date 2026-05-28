<script setup lang="ts">
import type { Component } from 'vue';

interface Props {
    titleKey: string;
    hintKey: string;
    value: string;
    icon: Component;
    iconClass?: string;
    valueClass?: string;
}

withDefaults(defineProps<Props>(), {
    iconClass: 'bg-muted text-muted-foreground',
    valueClass: 'text-foreground',
});
</script>

<template>
    <div
        class="flex items-start justify-between gap-2 rounded-xl border border-border bg-card p-3 shadow-sm"
    >
        <div class="min-w-0 flex-1">
            <div class="flex items-center gap-2 text-xs font-medium text-muted-foreground">
                <component :is="icon" class="h-3.5 w-3.5 shrink-0" />
                <span>{{ $t(titleKey) }}</span>
            </div>
            <p class="mt-1.5 font-serif text-xl font-black tracking-tight" :class="valueClass">
                {{ value }}
            </p>
            <p class="mt-0.5 text-[10px] text-muted-foreground">
                {{ $t(hintKey) }}
            </p>
        </div>
        <div
            class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full"
            :class="iconClass"
        >
            <component :is="icon" class="h-4 w-4" />
        </div>
    </div>
</template>
