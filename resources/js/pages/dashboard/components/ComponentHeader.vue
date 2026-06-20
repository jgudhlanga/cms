<script setup lang="ts">
interface Props {
    headerTitle: string;
    description?: string;
}

defineProps<Props>();
</script>

<template>
    <div>
        <h1 class="text-xs font-bold text-foreground uppercase">{{ headerTitle }}</h1>
        <p class="mt-1 text-xs text-muted-foreground" v-if="description">{{ description }}</p>
    </div>
</template>
