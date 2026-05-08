<script setup lang="ts">
defineProps<{ title: string; count: string | number }>();
</script>

<template>
    <div class="flex items-center space-x-2">
        <span class="text-xs font-bold uppercase">{{ title }}</span>
        <span class="bg-persian-200 rounded-full px-2 py-1 text-slate-800">
            {{ count }}
        </span>
    </div>
</template>
