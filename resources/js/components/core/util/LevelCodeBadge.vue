<script setup lang="ts">
withDefaults(
    defineProps<{
        label: string;
        title?: string;
    }>(),
    {
        title: undefined,
    },
);
</script>

<template>
    <span
        class="inline-flex h-7 min-w-9 shrink-0 items-center justify-center rounded-md border border-primary/20 bg-card px-1.5 text-[11px] font-bold uppercase tracking-wide text-primary"
        :title="title ?? label"
    >
        {{ label }}
    </span>
</template>
