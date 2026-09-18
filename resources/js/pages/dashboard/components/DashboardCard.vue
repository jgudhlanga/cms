<script setup lang="ts">
defineProps<{ title: string }>();
</script>

<template>
    <div
        class="group flex flex-col overflow-hidden rounded-lg border border-border/60 bg-card shadow-xs transition-colors duration-200 hover:border-border"
    >
        <div class="flex items-center justify-between gap-2 border-b border-border/50 bg-muted/20 px-2.5 py-1.5">
            <h3 class="truncate text-[9px] font-semibold tracking-[0.06em] text-muted-foreground uppercase">
                {{ title }}
            </h3>
            <div v-if="$slots.action" class="shrink-0">
                <slot name="action"></slot>
            </div>
        </div>
        <div class="flex-1 px-2.5 py-2">
            <slot></slot>
        </div>
    </div>
</template>
