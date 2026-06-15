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
        class="flex items-center gap-4 rounded-[14px] border border-slate-200 bg-white p-4 transition-shadow hover:shadow-md"
    >
        <div
            class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl"
            :class="iconClass"
        >
            <component :is="icon" class="h-5 w-5" />
        </div>
        <div>
            <div class="font-serif text-2xl font-black leading-none tracking-tight" :class="valueClass">
                {{ value }}
            </div>
            <div class="mt-1 text-xs text-slate-400">{{ label }}</div>
        </div>
    </div>
</template>
