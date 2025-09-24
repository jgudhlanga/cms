<script setup lang="ts">
interface Props {
    headerTitle: string;
    description?: string;
}

defineProps<Props>();
</script>

<template>
    <div class="px-4 sm:px-0">
        <h1 class="text-xs font-bold text-gray-800 uppercase">{{ headerTitle }}</h1>
        <p class="mt-1 text-xs text-gray-600" v-if="description">{{ description }}</p>
    </div>
</template>
