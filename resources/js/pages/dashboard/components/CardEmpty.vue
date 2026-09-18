<script setup lang="ts">
// Dense stand-in for the app-wide Empty component, whose 50px illustration dominates a compact panel.
defineProps<{ message: string }>();
</script>

<template>
    <div
        class="flex items-center justify-center rounded-md border border-dashed border-border/60 bg-muted/20 px-2 py-3 text-center text-[10px] text-muted-foreground"
    >
        {{ message }}
    </div>
</template>
