<script setup lang="ts">
defineProps({
    label: { type: String, required: false },
    value: { type: String, required: false },
    labelClasses: { type: String, required: false },
    valueClasses: { type: String, required: false }
});
</script>
<template>
    <div class="flex items-center text-sm space-x-3 text-accent-foreground">
        <div :class="`${labelClasses} flex w-3/7 font-medium`">{{ label }}:</div>
        <div :class="`${valueClasses} flex w-4/7 font-extralight`">{{ !value ? '---' : value }}</div>
    </div>
</template>
