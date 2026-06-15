<script setup lang="ts">
interface Props {
    title?: string;
}
defineProps<Props>();
</script>
<template>
    <div class="overflow-hidden rounded-lg border border-border bg-card text-card-foreground shadow">
        <div class="bg-card border-b border-border px-4 py-2">
            <div class="flex items-center justify-between">
                <h3 v-if="title" class="text-accent-foreground text-xs font-semibold uppercase">
                    {{ title }}
                </h3>
                <slot name="title" />
                <div class="flex space-x-2">
                    <slot name="header-buttons" />
                </div>
            </div>
        </div>
        <div class="p-4">
            <slot name="body" />
        </div>
        <slot name="footer" />
    </div>
</template>
