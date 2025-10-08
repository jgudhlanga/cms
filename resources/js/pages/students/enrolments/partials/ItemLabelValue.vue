<script setup lang="ts">
interface Props {
    label: string;
    value: string;
}
defineProps<Props>();
</script>

<template>
    <div class="flex justify-between">
        <span class="">{{ label }}</span>
        <span class="font-semibold">{{ value }}</span>
    </div>
</template>
