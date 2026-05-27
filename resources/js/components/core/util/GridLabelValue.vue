<script setup lang="ts">
interface Props {
    label: string;
    value: string | number;
}

defineProps<Props>();
</script>

<template>
    <div>
        <p class="text-xs font-medium tracking-wide text-muted-foreground uppercase">{{ label }}:</p>
        <p class="mt-1 text-foreground">{{ value }}</p>
    </div>
</template>
