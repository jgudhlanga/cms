<script setup lang="ts">
interface Props {
    title: string;
    value: number;
}

defineProps<Props>();
</script>

<template>
    <div class="stat-card flex flex-col overflow-hidden rounded-lg bg-white p-2 shadow">
        <div class="truncate text-[11px] font-bold uppercase">{{ title }}</div>
        <div class="stat-number">{{ value }}</div>
    </div>
</template>
<style scoped>
.stat-number {
    font-size: 1.0rem;
    font-weight: 700;
    color: #2575fc;
    margin: 0;
}

.stat-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
}
</style>
