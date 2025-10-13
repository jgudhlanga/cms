<script setup lang="ts">
interface Props {
    title: string;
    value: number;
}

defineProps<Props>();
</script>

<template>
    <div class="flex flex-col">
        <div class="truncate text-sm font-medium text-gray-500 uppercase">{{ title }}</div>
        <div class="stat-number">{{ value }}</div>
    </div>
</template>
<style scoped>
.stat-number {
    font-size: 1.5rem;
    font-weight: 700;
    color: #2575fc;
    margin: 0;
}
</style>
