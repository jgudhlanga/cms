<script setup lang="ts">
import { Check } from '@lucide/vue';
import type { Component } from 'vue';

interface Props {
    label: string;
    count?: string | number | null;
    active?: boolean;
    icon?: Component;
    ariaLabel?: string;
}

withDefaults(defineProps<Props>(), {
    count: null,
    active: false,
    icon: undefined,
    ariaLabel: undefined,
});

defineEmits<{
    (e: 'click'): void;
}>();
</script>

<template>
    <button
        type="button"
        :aria-label="ariaLabel ?? label"
        :aria-pressed="active || undefined"
        class="inline-flex h-6 shrink-0 cursor-pointer items-center gap-1 rounded-md border px-2 text-[11px] font-medium transition focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none"
        :class="
            active
                ? 'border-foreground/25 bg-muted text-foreground'
                : 'border-border bg-card text-foreground hover:border-foreground/30'
        "
        @click="$emit('click')"
    >
        <component :is="icon" v-if="icon" class="h-3 w-3 shrink-0 text-muted-foreground" />
        <span class="whitespace-nowrap">{{ label }}</span>
        <span
            v-if="count !== null && count !== undefined"
            class="tabular-nums text-muted-foreground"
            :class="{ 'opacity-80': active }"
        >
            {{ count }}
        </span>
        <Check v-if="active" class="h-3 w-3 shrink-0" />
    </button>
</template>
