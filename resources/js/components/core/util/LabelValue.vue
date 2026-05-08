<script setup lang="ts">
interface Props {
    label?: string;
    value?: string;
    labelClasses?: string;
    valueClasses?: string;
}
defineProps<Props>();
</script>
<template>
    <div class="text-accent-foreground flex w-full items-start gap-3 text-sm">
        <div :class="`${labelClasses} shrink-0 font-medium wrap-break-word whitespace-normal`">{{ label }}:</div>
        <div :class="`${valueClasses} min-w-0 flex-1 font-extralight wrap-anywhere whitespace-normal`">{{ !value ? '---' : value }}</div>
    </div>
</template>
