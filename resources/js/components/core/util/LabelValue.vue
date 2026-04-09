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
    <div class="flex w-full items-start gap-3 text-sm text-accent-foreground">
        <div :class="`${labelClasses} shrink-0 font-medium whitespace-normal wrap-break-word`">{{ label }}:</div>
        <div :class="`${valueClasses} min-w-0 flex-1 font-extralight whitespace-normal wrap-anywhere`">{{ !value ? '---' : value }}</div>
    </div>
</template>
