<script setup lang="ts">
interface Props {
    title: string;
    description?: string;
}

defineProps<Props>();
</script>

<template>
    <div class="flex flex-col space-y-0.5">
        <h2 class="text-[10px] font-semibold tracking-tight uppercase md:text-xl">{{ title }}</h2>
        <p v-if="description" class="text-muted-foreground text-sm">
            {{ description }}
        </p>
    </div>
</template>
