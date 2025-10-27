<script setup lang="ts">
defineProps<{ classSize: string | number }>();
</script>

<template>
    <div class="bg-persian-100 text-primary flex space-x-3 rounded-full px-3 py-1 text-sm font-medium">
        <div>Class size:</div>
        <div class="font-bold">{{ classSize }}</div>
    </div>
</template>
