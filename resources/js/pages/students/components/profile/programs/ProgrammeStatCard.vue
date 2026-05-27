<script setup lang="ts">
interface Props {
    labelKey: string;
    value: string | number;
}

defineProps<Props>();
</script>

<template>
    <div class="rounded-xl border border-border bg-muted/40 px-4 py-3">
        <p class="mb-1 text-[0.65rem] font-medium text-muted-foreground">
            {{ $t(labelKey) }}
        </p>
        <p class="text-[0.95rem] font-bold tracking-tight text-foreground">
            {{ value }}
        </p>
    </div>
</template>
