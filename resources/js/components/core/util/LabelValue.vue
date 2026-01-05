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
    <div class="flex items-center text-sm space-x-3 text-accent-foreground overflow-scroll text-nowrap">
        <div :class="`${labelClasses} flex w-3/7 font-medium`">{{ label }}:</div>
        <div :class="`${valueClasses} flex w-4/7 font-extralight`">{{ !value ? '---' : value }}</div>
    </div>
</template>
