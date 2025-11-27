<script setup lang="ts">
defineProps<{ deficit: string | number }>();
</script>

<template>
    <div class="bg-orange-100 text-orange-500 flex space-x-3 rounded-full px-3 py-1 text-sm font-medium">
        <div>Deficit:</div>
        <div class="font-bold">{{ deficit }}</div>
    </div>
</template>
