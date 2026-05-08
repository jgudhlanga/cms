<script setup lang="ts">
interface Props {
    title: string;
    description?: string;
}

defineProps<Props>();
</script>

<template>
    <div class="flex flex-col">
        <div class="text-accent-foreground mb-0.5 flex text-xs font-bold uppercase">{{ title }}</div>
        <div v-if="description" class="text-muted-foreground hidden text-xs md:flex">
            {{ description }}
        </div>
    </div>
</template>
